<?php
require_once '../config/auth.php';
Auth::cargo(['funcionario']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Processar confirmação de pagamento
if(isset($_GET['confirmar'])) {
    $id = (int)$_GET['confirmar'];
    $query = "UPDATE pagamentos SET estado = 'confirmado', data_pagamento = NOW() WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    header('Location: pagamentos.php');
    exit();
}

// Processar cancelamento de pagamento
if(isset($_GET['cancelar'])) {
    $id = (int)$_GET['cancelar'];
    $query = "UPDATE pagamentos SET estado = 'cancelado' WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    header('Location: pagamentos.php');
    exit();
}

// Buscar pagamentos pendentes
$query = "SELECT p.*, u.nome as cliente_nome, u.email as cliente_email, u.telefone as cliente_telefone,
          CASE 
              WHEN p.reserva_id IS NOT NULL THEN 
                  (SELECT CONCAT(v.marca, ' ', v.modelo, ' - ', r.data_inicio, ' a ', r.data_fim) 
                   FROM reservas r JOIN viaturas v ON r.viatura_id = v.id WHERE r.id = p.reserva_id)
              ELSE 
                  (SELECT CONCAT(v.marca, ' ', v.modelo, ' - ', a.data_inicio, ' a ', a.data_fim) 
                   FROM alugueis a JOIN viaturas v ON a.viatura_id = v.id WHERE a.id = p.aluguer_id)
          END as descricao
          FROM pagamentos p
          JOIN utilizadores u ON p.utilizador_id = u.id
          WHERE p.estado = 'pendente'
          ORDER BY p.data_criacao ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$pagamentos_pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar pagamentos confirmados recentes
$query = "SELECT p.*, u.nome as cliente_nome,
          CASE 
              WHEN p.reserva_id IS NOT NULL THEN 
                  (SELECT CONCAT(v.marca, ' ', v.modelo) FROM reservas r JOIN viaturas v ON r.viatura_id = v.id WHERE r.id = p.reserva_id)
              ELSE 
                  (SELECT CONCAT(v.marca, ' ', v.modelo) FROM alugueis a JOIN viaturas v ON a.viatura_id = v.id WHERE a.id = p.aluguer_id)
          END as descricao
          FROM pagamentos p
          JOIN utilizadores u ON p.utilizador_id = u.id
          WHERE p.estado = 'confirmado'
          ORDER BY p.data_pagamento DESC
          LIMIT 20";
$stmt = $db->prepare($query);
$stmt->execute();
$pagamentos_confirmados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$query = "SELECT 
          COUNT(CASE WHEN estado = 'pendente' THEN 1 END) as pendentes,
          SUM(CASE WHEN estado = 'pendente' THEN valor ELSE 0 END) as valor_pendente,
          COUNT(CASE WHEN estado = 'confirmado' AND DATE(data_pagamento) = CURDATE() THEN 1 END) as confirmados_hoje,
          SUM(CASE WHEN estado = 'confirmado' AND DATE(data_pagamento) = CURDATE() THEN valor ELSE 0 END) as valor_hoje,
          COUNT(CASE WHEN metodo_pagamento = 'mbway' AND estado = 'pendente' THEN 1 END) as mbway_pendentes,
          COUNT(CASE WHEN metodo_pagamento = 'transferencia' AND estado = 'pendente' THEN 1 END) as transferencia_pendentes
          FROM pagamentos";
$stmt = $db->prepare($query);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Pagamentos - Funcionário</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="../assets/css/funcionario.css">
    <style>
        .stats-pagamentos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #1E3A5F;
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: #666;
        }
        
        .pagamento-pendente {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #ffc107;
        }
        
        .pagamento-pendente.mbway {
            border-left-color: #17a2b8;
        }
        
        .pagamento-pendente.transferencia {
            border-left-color: #6c757d;
        }
        
        .btn-confirmar {
            background: #28a745;
            color: white;
            padding: 0.3rem 0.8rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .btn-cancelar {
            background: #dc3545;
            color: white;
            padding: 0.3rem 0.8rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .tabela-pagamentos {
            width: 100%;
            border-collapse: collapse;
        }
        
        .tabela-pagamentos th, .tabela-pagamentos td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .tabela-pagamentos th {
            background: #1E3A5F;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <!-- Estatísticas -->
            <div class="stats-pagamentos">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['pendentes'] ?? 0 ?></div>
                    <div class="stat-label">Pagamentos Pendentes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">MZN <?= number_format($stats['valor_pendente'] ?? 0, 2) ?></div>
                    <div class="stat-label">Valor Pendente</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['confirmados_hoje'] ?? 0 ?></div>
                    <div class="stat-label">Confirmados Hoje</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">MZN <?= number_format($stats['valor_hoje'] ?? 0, 2) ?></div>
                    <div class="stat-label">Valor Hoje</div>
                </div>
            </div>
            
            <!-- Pagamentos Pendentes -->
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo">Pagamentos Pendentes</h3>
                    <a href="registar_pagamento.php" class="btn btn-primario">+ Registar Pagamento Manual</a>
                </div>
                
                <?php if(count($pagamentos_pendentes) > 0): ?>
                    <?php foreach($pagamentos_pendentes as $p): ?>
                    <div class="pagamento-pendente <?= $p['metodo_pagamento'] ?>">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                            <div>
                                <strong><?= htmlspecialchars($p['cliente_nome']) ?></strong><br>
                                <small><?= $p['cliente_email'] ?></small><br>
                                <small>📞 <?= $p['cliente_telefone'] ?? '---' ?></small>
                            </div>
                            <div>
                                <span style="font-size: 1.2rem; font-weight: bold; color: #FF8C00;">MZN <?= number_format($p['valor'], 2) ?></span><br>
                                <small><?= ucfirst(str_replace('_', ' ', $p['metodo_pagamento'])) ?></small>
                            </div>
                            <div>
                                <small>Ref: <?= $p['referencia_pagamento'] ?></small><br>
                                <small><?= date('d/m/Y H:i', strtotime($p['data_criacao'])) ?></small>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <button class="btn-confirmar" onclick="confirmarPagamento(<?= $p['id'] ?>)">Confirmar</button>
                                <button class="btn-cancelar" onclick="cancelarPagamento(<?= $p['id'] ?>)">Cancelar</button>
                            </div>
                        </div>
                        <div style="margin-top: 0.5rem; font-size: 0.8rem; color: #666;">
                            <?= htmlspecialchars($p['descricao']) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div style="text-align: center; padding: 2rem;">
                    <p>Não há pagamentos pendentes</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Pagamentos Confirmados Recentes -->
            <div class="cartao" style="margin-top: 1.5rem;">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo">Últimos Pagamentos Confirmados</h3>
                </div>
                
                <?php if(count($pagamentos_confirmados) > 0): ?>
                <div class="container-tabela">
                    <table class="tabela-pagamentos">
                        <thead>
                            <tr>
                                <th>Referência</th>
                                <th>Cliente</th>
                                <th>Descrição</th>
                                <th>Valor</th>
                                <th>Data</th>
                                <th>Recibo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pagamentos_confirmados as $p): ?>
                            <tr>
                                <td><?= $p['referencia_pagamento'] ?></td>
                                <td><?= htmlspecialchars($p['cliente_nome']) ?></td>
                                <td><?= htmlspecialchars(substr($p['descricao'], 0, 40)) ?>...</td>
                                <td>MZN <?= number_format($p['valor'], 2) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($p['data_pagamento'])) ?></td>
                                <td>
                                    <button class="btn-recibo" onclick="emitirRecibo(<?= $p['id'] ?>)"></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 2rem;">
                    <p>Nenhum pagamento confirmado ainda</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function confirmarPagamento(id) {
            if(confirm('Confirmar este pagamento?')) {
                window.location.href = `?confirmar=${id}`;
            }
        }
        
        function cancelarPagamento(id) {
            if(confirm('Cancelar este pagamento?')) {
                window.location.href = `?cancelar=${id}`;
            }
        }
        
        function emitirRecibo(id) {
            window.open(`../pagamentos/recibo.php?id=${id}`, '_blank');
        }
    </script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/funcionario.js"></script>
</body>
</html>