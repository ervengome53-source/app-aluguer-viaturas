<?php
require_once '../config/auth.php';
Auth::cargo(['admin']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$utilizador = Auth::utilizador();

// ============================================
// KPIs PRINCIPAIS
// ============================================

$query = "SELECT 
          (SELECT COUNT(*) FROM utilizadores WHERE cargo = 'cliente' AND status = 'ativo') as total_clientes,
          (SELECT COUNT(*) FROM utilizadores WHERE cargo = 'funcionario' AND status = 'ativo') as total_funcionarios,
          (SELECT COUNT(*) FROM viaturas) as total_viaturas,
          (SELECT COUNT(*) FROM viaturas WHERE status = 'disponivel') as viaturas_disponiveis,
          (SELECT COUNT(*) FROM viaturas WHERE status = 'alugado') as viaturas_alugadas,
          (SELECT COUNT(*) FROM reservas WHERE status = 'pendente') as reservas_pendentes,
          (SELECT COUNT(*) FROM alugueis WHERE status = 'ativo') as alugueis_ativos";
$stmt = $db->prepare($query);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// ============================================
// RECEITA DE HOJE
// ============================================

$query = "SELECT COALESCE(SUM(valor), 0) as receita_hoje 
          FROM pagamentos WHERE estado = 'confirmado' AND DATE(data_pagamento) = CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$receita_hoje = $stmt->fetch(PDO::FETCH_ASSOC);

// ============================================
// ALERTAS (DEVOLUÇÕES ATRASADAS)
// ============================================

$query = "SELECT COUNT(*) as atrasadas, SUM(multa_atraso) as total_multas 
          FROM alugueis WHERE status = 'ativo' AND data_fim < CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$alertas = $stmt->fetch(PDO::FETCH_ASSOC);

// ============================================
// PRÓXIMAS DEVOLUÇÕES (próximos 3 dias)
// ============================================

$query = "SELECT a.*, v.marca, v.modelo, v.matricula, u.nome as cliente_nome
          FROM alugueis a
          JOIN viaturas v ON a.viatura_id = v.id
          JOIN utilizadores u ON a.utilizador_id = u.id
          WHERE a.status = 'ativo' AND a.data_fim BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
          ORDER BY a.data_fim ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$proximas_devolucoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// VIATURAS ALUGADAS (PARA O MAPA)
// ============================================

$query = "SELECT a.*, v.marca, v.modelo, v.matricula, u.nome as cliente_nome
          FROM alugueis a
          JOIN viaturas v ON a.viatura_id = v.id
          JOIN utilizadores u ON a.utilizador_id = u.id
          WHERE a.status = 'ativo'
          ORDER BY a.data_fim ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$viaturas_alugadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lista de locais para o mapa
$locais = [
    'Av. Marginal', 'Baixa', 'Sommerschield', 'Coop', 
    'Polana', 'Triunfo', 'Jardim', 'Malhangalene', 
    'Catembe', 'Costa do Sol', 'Matola', 'Liberdade'
];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - RentCar</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .alerta-atraso {
            background: #dc3545;
            color: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.8; }
            100% { opacity: 1; }
        }
        
        .mapa-container {
            background: white;
            border-radius: 12px;
            padding: 1.2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .mapa-container h3 {
            color: #1E3A5F;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #FF8C00;
        }
        
        .mapa-simulado {
            background: linear-gradient(135deg, #1a472a 0%, #2d6a4f 100%);
            border-radius: 12px;
            padding: 20px;
            min-height: 350px;
        }
        
        .mapa-titulo {
            text-align: center;
            color: white;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .mapa-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .mapa-card {
            background: rgba(255,255,255,0.95);
            border-radius: 10px;
            padding: 12px;
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        
        .mapa-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .mapa-card.alugado {
            border-left: 4px solid #dc3545;
        }
        
        .status-alugado {
            color: #dc3545;
            font-weight: bold;
            margin-top: 8px;
            font-size: 11px;
        }
        
        .dias-restantes {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .dias-1 { background: #dc3545; color: white; }
        .dias-2 { background: #ffc107; color: #333; }
        .dias-3 { background: #28a745; color: white; }
        
        .tabela-devolucoes {
            width: 100%;
            border-collapse: collapse;
        }
        
        .tabela-devolucoes th, .tabela-devolucoes td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .tabela-devolucoes th {
            background: #1E3A5F;
            color: white;
        }
        
        @media (max-width: 768px) {
            .mapa-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <!-- ============================================ -->
            <!-- ALERTA DE DEVOLUÇÕES ATRASADAS -->
            <!-- ============================================ -->
            <?php if($alertas['atrasadas'] > 0): ?>
            <div class="alerta-atraso">
                <span style="font-size: 2rem;">⚠️</span>
                <div>
                    <strong>ALERTA!</strong> Existem <strong><?= $alertas['atrasadas'] ?></strong> devolução(ões) atrasada(s).
                    Multa total acumulada: <strong>MZN <?= number_format($alertas['total_multas'] ?? 0, 2) ?></strong>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- ============================================ -->
            <!-- KPIs (CARDS) -->
            <!-- ============================================ -->
            <div class="grade-estatisticas">
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Total Clientes</h3>
                        <div class="estatistica-numero"><?= $stats['total_clientes'] ?? 0 ?></div>
                    </div>
                </div>
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Total Funcionários</h3>
                        <div class="estatistica-numero"><?= $stats['total_funcionarios'] ?? 0 ?></div>
                    </div>
                </div>
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Total Viaturas</h3>
                        <div class="estatistica-numero"><?= $stats['total_viaturas'] ?? 0 ?></div>
                    </div>
                </div>
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Viaturas Disponíveis</h3>
                        <div class="estatistica-numero"><?= $stats['viaturas_disponiveis'] ?? 0 ?></div>
                    </div>
                </div>
            </div>
            
            <div class="grade-estatisticas">
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Viaturas Alugadas</h3>
                        <div class="estatistica-numero"><?= $stats['viaturas_alugadas'] ?? 0 ?></div>
                    </div>
                </div>

                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Receita de Hoje</h3>
                        <div class="estatistica-numero">MZN <?= number_format($receita_hoje['receita_hoje'] ?? 0, 2) ?></div>
                    </div>
                </div>
            </div>
            
            <!-- ============================================ -->
            <!-- MAPA DE LOCALIZAÇÃO DAS VIATURAS -->
            <!-- ============================================ -->
            <div class="mapa-container">
                <h3>Mapa de Localização das Viaturas</h3>
                <div class="mapa-simulado">
                    <div class="mapa-titulo">
                         Mapa de Localização - Cidade de Maputo
                    </div>
                    <div class="mapa-grid">
                        <?php if(count($viaturas_alugadas) > 0): ?>
                            <?php foreach($viaturas_alugadas as $v): ?>
                            <?php $local = $locais[array_rand($locais)]; ?>
                            <div class="mapa-card alugado" onclick="window.location.href='../funcionario/devolucao.php?id=<?= $v['id'] ?>'">
                                <h4><?= htmlspecialchars($v['marca'] . ' ' . $v['modelo']) ?></h4>
                                <p> Matrícula: <?= $v['matricula'] ?></p>
                                <p> Cliente: <?= htmlspecialchars($v['cliente_nome']) ?></p>
                                <p> Devolução: <?= date('d/m/Y', strtotime($v['data_fim'])) ?></p>
                                <p> Localização atual: <strong><?= $local ?></strong></p>
                                <div class="status status-alugado">🔴 ALUGADO</div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="mapa-card" style="grid-column: span 3; text-align: center;">
                                <p> Todas as viaturas estão disponíveis no momento!</p>
                                <p style="font-size: 0.8rem;">Nenhuma viatura alugada no momento.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- ============================================ -->
            <!-- PRÓXIMAS DEVOLUÇÕES -->
            <!-- ============================================ -->
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo">Próximas Devoluções (próximos 3 dias)</h3>
                </div>
                <?php if(count($proximas_devolucoes) > 0): ?>
                <div class="container-tabela">
                    <table class="tabela-devolucoes">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Viatura</th>
                                <th>Matrícula</th>
                                <th>Data Devolução</th>
                                <th>Status</th>
                             </thead>
                        <tbody>
                            <?php foreach($proximas_devolucoes as $d): ?>
                            <?php 
                            $dias_restantes = ceil((strtotime($d['data_fim']) - time()) / 86400);
                            $classe_dias = $dias_restantes <= 1 ? 'dias-1' : ($dias_restantes <= 2 ? 'dias-2' : 'dias-3');
                            $texto_status = $dias_restantes <= 1 ? 'Urgente' : ($dias_restantes <= 2 ? 'Atenção' : 'Normal');
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($d['cliente_nome']) ?></td>
                                <td><?= htmlspecialchars($d['marca'] . ' ' . $d['modelo']) ?></td>
                                <td><?= $d['matricula'] ?></td>
                                <td><?= date('d/m/Y', strtotime($d['data_fim'])) ?></td>
                                <td><span class="dias-restantes <?= $classe_dias ?>"><?= $texto_status ?> (<?= $dias_restantes ?> dia(s))</span></td>
                            </table>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p style="text-align: center; padding: 20px;"> Nenhuma devolução prevista nos próximos 3 dias</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>