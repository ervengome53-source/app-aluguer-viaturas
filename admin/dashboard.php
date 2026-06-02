<?php
require_once '../config/auth.php';
Auth::cargo(['admin']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$utilizador = Auth::utilizador();

// ============================================
// DADOS PARA O DASHBOARD (USANDO SUA ESTRUTURA)
// ============================================

// Totais principais
$query = "SELECT 
          (SELECT COUNT(*) FROM viaturas WHERE status != 'indisponivel') as total_viaturas,
          (SELECT COUNT(*) FROM viaturas WHERE status = 'manutencao') as em_manutencao,
          (SELECT COUNT(*) FROM alugueis WHERE status = 'ativo') as reservas_ativas,
          (SELECT COALESCE(SUM(valor), 0) FROM pagamentos WHERE estado = 'confirmado' AND MONTH(data_pagamento) = MONTH(CURDATE()) AND YEAR(data_pagamento) = YEAR(CURDATE())) as faturacao_mensal";
$stmt = $db->prepare($query);
$stmt->execute();
$totais = $stmt->fetch(PDO::FETCH_ASSOC);

// Novos clientes este mês (usando criado_em)
$query = "SELECT COUNT(*) as novos_clientes 
          FROM utilizadores 
          WHERE cargo = 'cliente' 
          AND MONTH(criado_em) = MONTH(CURDATE()) 
          AND YEAR(criado_em) = YEAR(CURDATE())";
$stmt = $db->prepare($query);
$stmt->execute();
$novos_clientes = $stmt->fetch(PDO::FETCH_ASSOC);

// Aluguéis que terminam hoje
$query = "SELECT COUNT(*) as terminam_hoje 
          FROM alugueis 
          WHERE status = 'ativo' 
          AND DATE(data_fim) = CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$terminam_hoje = $stmt->fetch(PDO::FETCH_ASSOC);

// Total de clientes ativos
$query = "SELECT COUNT(*) as total_clientes 
          FROM utilizadores 
          WHERE cargo = 'cliente' AND status = 'ativo'";
$stmt = $db->prepare($query);
$stmt->execute();
$total_clientes = $stmt->fetch(PDO::FETCH_ASSOC);

// Meta de ocupação
$total_viaturas = $totais['total_viaturas'] > 0 ? $totais['total_viaturas'] : 1;
$taxa_ocupacao = round(($totais['reservas_ativas'] / $total_viaturas) * 100, 1);
$meta_atingida = $taxa_ocupacao >= 85;

// ============================================
// DADOS PARA O GRÁFICO (Volume de Alugueres por dia da semana)
// ============================================

$query = "SELECT 
          DAYOFWEEK(data_inicio) as dia_semana,
          COUNT(*) as total_alugueis
          FROM alugueis 
          WHERE MONTH(data_inicio) = MONTH(CURDATE()) 
          AND YEAR(data_inicio) = YEAR(CURDATE())
          GROUP BY DAYOFWEEK(data_inicio)";
$stmt = $db->prepare($query);
$stmt->execute();
$alugueis_por_dia = $stmt->fetchAll(PDO::FETCH_ASSOC);

$dias_semana = [
    1 => 'DOM', 2 => 'SEG', 3 => 'TER', 
    4 => 'QUA', 5 => 'QUI', 6 => 'SEX', 7 => 'SAB'
];
$dados_grafico = [];
foreach($dias_semana as $num => $nome) {
    $encontrado = false;
    foreach($alugueis_por_dia as $item) {
        if($item['dia_semana'] == $num) {
            $dados_grafico[$nome] = $item['total_alugueis'];
            $encontrado = true;
            break;
        }
    }
    if(!$encontrado) {
        $dados_grafico[$nome] = 0;
    }
}

// ============================================
// ATIVIDADE RECENTE (Aluguéis)
// ============================================

$query = "SELECT a.id, a.preco_total as valor_total, a.status, a.data_inicio, a.data_fim,
          v.marca, v.modelo, v.matricula,
          u.nome as cliente_nome,
          p.estado as pagamento_estado, p.metodo_pagamento
          FROM alugueis a
          JOIN viaturas v ON a.viatura_id = v.id
          JOIN utilizadores u ON a.utilizador_id = u.id
          LEFT JOIN pagamentos p ON a.id = p.aluguer_id
          WHERE a.status IN ('ativo', 'pendente', 'finalizado')
          ORDER BY a.criado_em DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$alugueis_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// ALUGUÉIS MAIS RECENTES PARA TABELA
// ============================================

$query = "SELECT a.id, a.preco_total, a.status, a.data_inicio, a.data_fim,
          v.marca, v.modelo, v.matricula,
          u.nome as cliente_nome
          FROM alugueis a
          JOIN viaturas v ON a.viatura_id = v.id
          JOIN utilizadores u ON a.utilizador_id = u.id
          ORDER BY a.criado_em DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$ultimos_alugueis = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - RentCar</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .dashboard-container {
            padding: 25px;
        }

        .dashboard-header {
            margin-bottom: 30px;
        }

        .dashboard-header h1 {
            color: #1E3A5F;
            font-size: 28px;
            margin-bottom: 8px;
        }

        .dashboard-header p {
            color: #666;
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }

        .stat-card.primary::before { background: #FF8C00; }
        .stat-card.success::before { background: #28a745; }
        .stat-card.warning::before { background: #ffc107; }
        .stat-card.info::before { background: #17a2b8; }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .stat-title {
            color: #888;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .stat-icon.primary { background: rgba(255, 140, 0, 0.1); color: #FF8C00; }
        .stat-icon.success { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .stat-icon.warning { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .stat-icon.info { background: rgba(23, 162, 184, 0.1); color: #17a2b8; }

        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #1E3A5F;
            margin-bottom: 10px;
        }

        .stat-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            font-size: 12px;
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #28a745;
        }

        .stat-meta {
            color: #999;
        }

        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        .chart-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-header h3 {
            color: #1E3A5F;
            font-size: 18px;
        }

        .chart-badge {
            background: #f0f2f5;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            color: #666;
        }

        .chart-bars {
            display: flex;
            justify-content: space-around;
            align-items: flex-end;
            height: 200px;
            margin: 20px 0;
        }

        .bar-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            flex: 1;
        }

        .bar {
            width: 40px;
            background: linear-gradient(180deg, #FF8C00, #FFD700);
            border-radius: 8px;
            transition: height 0.5s ease;
            cursor: pointer;
        }

        .bar:hover {
            opacity: 0.8;
        }

        .bar-label {
            font-size: 12px;
            color: #666;
            font-weight: 500;
        }

        .bar-value {
            font-size: 11px;
            color: #999;
        }

        .chart-footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 12px;
            color: #999;
        }

        .activities-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .activities-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .activities-header h3 {
            color: #1E3A5F;
            font-size: 18px;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .activity-item {
            display: flex;
            gap: 15px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .activity-item:hover {
            background: #f0f2f5;
            transform: translateX(5px);
        }

        .activity-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .activity-icon.aluguer { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .activity-icon.pendente { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .activity-icon.finalizado { background: rgba(23, 162, 184, 0.1); color: #17a2b8; }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            color: #1E3A5F;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .activity-desc {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .activity-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
        }

        .activity-price {
            font-weight: bold;
            font-size: 14px;
            color: #FF8C00;
        }

        .activity-status {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 500;
        }

        .status-ativo { background: #d4edda; color: #155724; }
        .status-pendente { background: #fff3cd; color: #856404; }
        .status-finalizado { background: #cce5ff; color: #004085; }

        .tabela-alugueis {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .tabela-alugueis h3 {
            color: #1E3A5F;
            margin-bottom: 20px;
            font-size: 18px;
        }

        .tabela-alugueis table {
            width: 100%;
            border-collapse: collapse;
        }

        .tabela-alugueis th,
        .tabela-alugueis td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .tabela-alugueis th {
            background: #f8f9fa;
            color: #666;
            font-weight: 600;
            font-size: 13px;
        }

        .badge-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .badge-ativo { background: #d4edda; color: #155724; }
        .badge-pendente { background: #fff3cd; color: #856404; }
        .badge-finalizado { background: #cce5ff; color: #004085; }
        .badge-atrasado { background: #f8d7da; color: #721c24; }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .two-columns {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-value {
                font-size: 28px;
            }
            
            .tabela-alugueis {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <div class="dashboard-container">
                <div class="dashboard-header">
                    <h1>Painel de Controlo</h1>
                    <p>Bem-vindo de volta, <?= htmlspecialchars($utilizador['nome'] ?? 'Administrador') ?>.</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="stat-header">
                            <span class="stat-title">TOTAL DE CLIENTES</span>
                            <div class="stat-icon primary">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?= number_format($total_clientes['total_clientes'] ?? 0) ?></div>
                        <div class="stat-footer">
                            <div class="stat-trend">
                                <i class="fas fa-plus-circle"></i>
                                <span>+<?= $novos_clientes['novos_clientes'] ?? 0 ?> novos este mês</span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card success">
                        <div class="stat-header">
                            <span class="stat-title">RESERVAS ATIVAS</span>
                            <div class="stat-icon success">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?= $totais['reservas_ativas'] ?? 0 ?></div>
                        <div class="stat-footer">
                            <div class="stat-trend">
                                <i class="fas fa-clock"></i>
                                <span><?= $terminam_hoje['terminam_hoje'] ?? 0 ?> terminam hoje</span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card warning">
                        <div class="stat-header">
                            <span class="stat-title">EM MANUTENÇÃO</span>
                            <div class="stat-icon warning">
                                <i class="fas fa-tools"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?= str_pad($totais['em_manutencao'] ?? 0, 2, '0', STR_PAD_LEFT) ?></div>
                        <div class="stat-footer">
                            <div class="stat-meta">
                                <i class="fas fa-chart-line"></i> Meta: < 10%
                            </div>
                        </div>
                    </div>

                    <div class="stat-card info">
                        <div class="stat-header">
                            <span class="stat-title">FATURAÇÃO MENSAL</span>
                            <div class="stat-icon info">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                        <div class="stat-value">
                            <?php 
                            $faturacao = ($totais['faturacao_mensal'] ?? 0) / 1000000;
                            echo number_format($faturacao, 2) . 'M MT';
                            ?>
                        </div>
                        <div class="stat-footer">
                            <div class="stat-meta">
                                Ocupação: <?= $taxa_ocupacao ?>%
                            </div>
                            <div class="stat-trend">
                                <?php if($meta_atingida): ?>
                                <i class="fas fa-check-circle"></i>
                                <span>Meta 85% ✓</span>
                                <?php else: ?>
                                <span>Meta: 85%</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="two-columns">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>Volume de Alugueres</h3>
                            <span class="chart-badge">Análise semanal de movimentação</span>
                        </div>
                        <div class="chart-bars">
                            <?php 
                            $max_valor = max($dados_grafico) > 0 ? max($dados_grafico) : 1;
                            foreach($dados_grafico as $dia => $valor): 
                                $altura = ($valor / $max_valor) * 150;
                            ?>
                            <div class="bar-item">
                                <div class="bar-value"><?= $valor ?></div>
                                <div class="bar" style="height: <?= $altura ?>px;"></div>
                                <div class="bar-label"><?= $dia ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="chart-footer">
                            <i class="fas fa-chart-line"></i> Tendência de alugueres esta semana
                        </div>
                    </div>

                    <div class="activities-card">
                        <div class="activities-header">
                            <h3>Atividade Recente</h3>
                            <i class="fas fa-ellipsis-h" style="color: #999;"></i>
                        </div>
                        <div class="activity-list">
                            <?php 
                            $contador = 0;
                            foreach($alugueis_recentes as $aluguel): 
                                if($contador >= 4) break;
                                $numero_aluguel = str_pad($aluguel['id'], 4, '0', STR_PAD_LEFT);
                                $status_class = $aluguel['status'] == 'ativo' ? 'status-ativo' : ($aluguel['status'] == 'pendente' ? 'status-pendente' : 'status-finalizado');
                                $status_text = strtoupper($aluguel['status']);
                                $valor = number_format($aluguel['valor_total'] ?? 0, 0, ',', '.');
                                $pagamento_text = ($aluguel['pagamento_estado'] ?? '') == 'confirmado' ? 'LIQUIDADO VIA ' . strtoupper($aluguel['metodo_pagamento'] ?? 'DINHEIRO') : 'FATURA PENDENTE';
                            ?>
                            <div class="activity-item">
                                <div class="activity-icon <?= $aluguel['status'] ?>">
                                    <i class="fas fa-car"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">
                                        Aluguer #ALU-<?= $numero_aluguel ?>
                                    </div>
                                    <div class="activity-desc">
                                        <?= htmlspecialchars($aluguel['marca'] . ' ' . $aluguel['modelo']) ?> - 
                                        Cliente: <?= htmlspecialchars($aluguel['cliente_nome']) ?>
                                    </div>
                                    <div class="activity-footer">
                                        <span class="activity-price"><?= $valor ?> MT</span>
                                        <span class="activity-status <?= $status_class ?>"><?= $status_text ?></span>
                                    </div>
                                    <div style="font-size: 10px; color: #999; margin-top: 5px;">
                                        <?= $pagamento_text ?>
                                    </div>
                                </div>
                            </div>
                            <?php 
                            $contador++;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Tabela de Últimos Aluguéis -->
                <div class="tabela-alugueis">
                    <h3><i class="fas fa-list"></i> Últimos Aluguéis Registados</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Viatura</th>
                                <th>Matrícula</th>
                                <th>Data Início</th>
                                <th>Data Fim</th>
                                <th>Valor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($ultimos_alugueis as $aluguel): ?>
                            <tr>
                                <td>#<?= str_pad($aluguel['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars($aluguel['cliente_nome']) ?></td>
                                <td><?= htmlspecialchars($aluguel['marca'] . ' ' . $aluguel['modelo']) ?></td>
                                <td><?= $aluguel['matricula'] ?></td>
                                <td><?= date('d/m/Y', strtotime($aluguel['data_inicio'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($aluguel['data_fim'])) ?></td>
                                <td><?= number_format($aluguel['preco_total'] ?? 0, 2, ',', '.') ?> MT</td>
                                <td>
                                    <span class="badge-status badge-<?= $aluguel['status'] ?>">
                                        <?= strtoupper($aluguel['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>