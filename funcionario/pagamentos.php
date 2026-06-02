<?php
require_once '../config/auth.php';
Auth::cargo(['funcionario']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$mensagem = '';
$erro = '';

// Processar confirmação de pagamento via POST
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmar_id'])) {
    $id = (int)$_POST['confirmar_id'];
    $query = "UPDATE pagamentos SET estado = 'confirmado', data_pagamento = NOW() WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    if($stmt->execute()) {
        $mensagem = '<i class="fas fa-check-circle"></i> Pagamento confirmado com sucesso!';
    } else {
        $erro = '<i class="fas fa-exclamation-triangle"></i> Erro ao confirmar pagamento';
    }
}

// Processar cancelamento de pagamento
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancelar_id'])) {
    $id = (int)$_POST['cancelar_id'];
    $query = "UPDATE pagamentos SET estado = 'cancelado' WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    if($stmt->execute()) {
        $mensagem = '<i class="fas fa-check-circle"></i> Pagamento cancelado com sucesso!';
    } else {
        $erro = '<i class="fas fa-exclamation-triangle"></i> Erro ao cancelar pagamento';
    }
}

// Buscar pagamentos pendentes
$query = "SELECT p.*, u.nome as cliente_nome, u.email as cliente_email, u.telefone as cliente_telefone,
          CASE 
              WHEN p.reserva_id IS NOT NULL THEN 
                  (SELECT CONCAT(v.marca, ' ', v.modelo) FROM reservas r JOIN viaturas v ON r.viatura_id = v.id WHERE r.id = p.reserva_id)
              ELSE 
                  (SELECT CONCAT(v.marca, ' ', v.modelo) FROM alugueis a JOIN viaturas v ON a.viatura_id = v.id WHERE a.id = p.aluguer_id)
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
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$pagamentos_confirmados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$query = "SELECT 
          COUNT(CASE WHEN estado = 'pendente' THEN 1 END) as pendentes,
          SUM(CASE WHEN estado = 'pendente' THEN valor ELSE 0 END) as valor_pendente,
          COUNT(CASE WHEN estado = 'confirmado' AND DATE(data_pagamento) = CURDATE() THEN 1 END) as confirmados_hoje,
          SUM(CASE WHEN estado = 'confirmado' AND DATE(data_pagamento) = CURDATE() THEN valor ELSE 0 END) as valor_hoje,
          COUNT(CASE WHEN estado = 'confirmado' THEN 1 END) as total_confirmados,
          SUM(CASE WHEN estado = 'confirmado' THEN valor ELSE 0 END) as total_receita
          FROM pagamentos";
$stmt = $db->prepare($query);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Gestão de Pagamentos - SIGAV</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fb; overflow-x: hidden; }
        
        .container-app { display: flex; min-height: 100vh; width: 100%; }
        .barra-lateral { width: 280px; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: white; position: fixed; left: 0; top: 0; height: 100vh; overflow-y: auto; z-index: 100; transition: all 0.3s ease; }
        .conteudo-principal { flex: 1; margin-left: 280px; padding: 2rem; background: #f5f7fb; min-height: 100vh; width: calc(100% - 280px); }
        .barra-superior { background: white; border-radius: 1rem; padding: 1rem 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        
        .page-header { margin-bottom: 2rem; }
        .page-header h1 { font-size: 2rem; font-weight: 700; color: #1a1a2e; margin-bottom: 0.5rem; }
        .page-header p { color: #666; font-size: 0.95rem; }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 1.5rem;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .stat-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #FF8C00, #FFD700);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-icon.warning { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .stat-icon.danger { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
        .stat-icon.success { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .stat-icon.primary { background: rgba(255, 140, 0, 0.1); color: #FF8C00; }
        
        .stat-info h3 {
            font-size: 0.85rem;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: #999;
        }
        
        /* Card Principal */
        .card {
            background: white;
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            padding: 1.2rem 1.5rem;
            background: white;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .card-header h2 {
            font-size: 1rem;
            font-weight: 600;
            color: #1a1a2e;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            font-size: 0.8rem;
            text-decoration: none;
        }
        
        .btn-primary:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        /* Tabela */
        .table-container {
            overflow-x: auto;
        }
        
        .modern-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .modern-table th {
            padding: 1rem 0.8rem;
            text-align: left;
            font-size: 0.7rem;
            font-weight: 600;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #eee;
            background: white;
        }
        
        .modern-table td {
            padding: 0.8rem;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.85rem;
            vertical-align: middle;
            background: white;
        }
        
        .modern-table tr:last-child td {
            border-bottom: none;
        }
        
        .modern-table tr:hover td {
            background: #fef9e6;
        }
        
        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.3rem 0.8rem;
            border-radius: 2rem;
            font-size: 0.7rem;
            font-weight: 500;
        }
        
        .badge-pendente { background: #fff3cd; color: #856404; }
        .badge-confirmado { background: #d4edda; color: #155724; }
        
        .price {
            font-weight: 700;
            color: #FF8C00;
        }
        
        /* Botões de Ação */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-confirmar {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.7rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.3s ease;
        }
        
        .btn-confirmar:hover {
            background: #28a745;
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-cancelar {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.7rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.3s ease;
        }
        
        .btn-cancelar:hover {
            background: #dc3545;
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-recibo {
            background: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.7rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.3s ease;
        }
        
        .btn-recibo:hover {
            background: #17a2b8;
            color: white;
            transform: translateY(-2px);
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            justify-content: center;
            align-items: center;
        }
        
        .modal.active { display: flex; }
        
        .modal-content {
            background: white;
            border-radius: 1.5rem;
            width: 90%;
            max-width: 420px;
            animation: modalFadeIn 0.3s ease;
            overflow: hidden;
        }
        
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .modal-header {
            padding: 1.2rem 1.5rem;
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.3rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .modal-close:hover {
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 1.5rem;
            text-align: center;
        }
        
        .modal-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .modal-message {
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .modal-details {
            background: #f8f9fa;
            padding: 0.8rem;
            border-radius: 0.8rem;
            margin-top: 1rem;
            font-size: 0.85rem;
            color: #666;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem 1.5rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
            border-top: 1px solid #eee;
        }
        
        .btn-cancel-modal {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 0.8rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-cancel-modal:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .btn-confirm-modal {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 0.8rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-confirm-modal:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .btn-confirm-danger {
            background: #dc3545;
        }
        
        .btn-confirm-danger:hover {
            background: #c82333;
        }
        
        /* Toast */
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 0.8rem;
            z-index: 2000;
            animation: slideInRight 0.3s ease;
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .toast-success { border-left: 4px solid #28a745; }
        .toast-success i { color: #28a745; }
        .toast-error { border-left: 4px solid #dc3545; }
        .toast-error i { color: #dc3545; }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #999;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            opacity: 0.5;
        }
        
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .barra-lateral { width: 0; transform: translateX(-100%); }
            .conteudo-principal { margin-left: 0; width: 100%; padding: 1rem; }
            .stats-grid { grid-template-columns: 1fr; }
            .card-header { flex-direction: column; align-items: stretch; }
            .action-buttons { justify-content: center; }
            .table-container { overflow-x: auto; }
            .modern-table { min-width: 700px; }
        }
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <?php if($mensagem): ?>
            <div class="toast-notification toast-success" id="toast">
                <i class="fas fa-check-circle fa-lg"></i>
                <span><?= $mensagem ?></span>
            </div>
            <script>setTimeout(() => { const t = document.getElementById('toast'); if(t) t.style.display = 'none'; }, 3000);</script>
            <?php endif; ?>
            
            <?php if($erro): ?>
            <div class="toast-notification toast-error" id="toastErro">
                <i class="fas fa-exclamation-circle fa-lg"></i>
                <span><?= $erro ?></span>
            </div>
            <script>setTimeout(() => { const t = document.getElementById('toastErro'); if(t) t.style.display = 'none'; }, 3000);</script>
            <?php endif; ?>
            
            <div class="page-header">
                <h1>Gestão de Pagamentos</h1>
                <p>Gerencie todos os pagamentos do sistema</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Pagamentos Pendentes</h3>
                        <div class="stat-number"><?= $stats['pendentes'] ?? 0 ?></div>
                        <div class="stat-label">Aguardando confirmação</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon danger">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Valor Pendente</h3>
                        <div class="stat-number">MZN <?= number_format($stats['valor_pendente'] ?? 0, 2) ?></div>
                        <div class="stat-label">Total em aberto</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Confirmados Hoje</h3>
                        <div class="stat-number"><?= $stats['confirmados_hoje'] ?? 0 ?></div>
                        <div class="stat-label">Pagamentos do dia</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Valor Hoje</h3>
                        <div class="stat-number">MZN <?= number_format($stats['valor_hoje'] ?? 0, 2) ?></div>
                        <div class="stat-label">Receita do dia</div>
                    </div>
                </div>
            </div>
            
            <!-- Pagamentos Pendentes -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-hourglass-half"></i> Pagamentos Pendentes</h2>
                    <a href="registar_pagamento.php" class="btn-primary">
                        <i class="fas fa-plus"></i> Novo Pagamento
                    </a>
                </div>
                
                <?php if(count($pagamentos_pendentes) > 0): ?>
                <div class="table-container">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Descrição</th>
                                <th>Valor</th>
                                <th>Método</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pagamentos_pendentes as $p): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($p['cliente_nome']) ?></strong><br>
                                    <small style="color: #999;"><i class="fas fa-envelope"></i> <?= $p['cliente_email'] ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($p['descricao']) ?><br>
                                    <small><i class="fas fa-hashtag"></i> <?= $p['referencia_pagamento'] ?></small>
                                </td>
                                <td class="price">MZN <?= number_format($p['valor'], 2) ?></td>
                                <td>
                                    <span class="badge badge-pendente">
                                        <i class="fas fa-clock"></i> <?= ucfirst(str_replace('_', ' ', $p['metodo_pagamento'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= date('d/m/Y', strtotime($p['data_criacao'])) ?><br>
                                    <small><?= date('H:i', strtotime($p['data_criacao'])) ?></small>
                                </td>
                                <td class="action-buttons">
                                    <button class="btn-confirmar" onclick="abrirModalConfirmar(<?= $p['id'] ?>, '<?= addslashes($p['cliente_nome']) ?>', <?= $p['valor'] ?>)">
                                        <i class="fas fa-check"></i> Confirmar
                                    </button>
                                    <button class="btn-cancelar" onclick="abrirModalCancelar(<?= $p['id'] ?>, '<?= addslashes($p['cliente_nome']) ?>', <?= $p['valor'] ?>)">
                                        <i class="fas fa-times"></i> Cancelar
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <p>Não há pagamentos pendentes</p>
                    <small>Todos os pagamentos estão confirmados</small>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Últimos Pagamentos Confirmados -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-history"></i> Últimos Pagamentos Confirmados</h2>
                </div>
                
                <?php if(count($pagamentos_confirmados) > 0): ?>
                <div class="table-container">
                    <table class="modern-table">
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
                                <td>
                                    <strong><?= $p['referencia_pagamento'] ?></strong>
                                </td>
                                <td><?= htmlspecialchars($p['cliente_nome']) ?></td>
                                <td><?= htmlspecialchars($p['descricao']) ?></td>
                                <td class="price">MZN <?= number_format($p['valor'], 2) ?></td>
                                <td>
                                    <?= date('d/m/Y', strtotime($p['data_pagamento'])) ?><br>
                                    <small><?= date('H:i', strtotime($p['data_pagamento'])) ?></small>
                                </td>
                                <td>
                                    <button class="btn-recibo" onclick="emitirRecibo(<?= $p['id'] ?>)">
                                        <i class="fas fa-file-pdf"></i> Recibo
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Nenhum pagamento confirmado</p>
                    <small>Os pagamentos confirmados aparecerão aqui</small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- MODAL CONFIRMAÇÃO -->
    <div id="modalConfirmacao" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitulo"><i class="fas fa-question-circle"></i> Confirmar Ação</h3>
                <button class="modal-close" onclick="fecharModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-icon" id="modalIcone">
                    <i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i>
                </div>
                <div class="modal-message" id="modalMensagem">
                    Tem certeza que deseja realizar esta ação?
                </div>
                <div class="modal-details" id="modalDetalhes"></div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel-modal" onclick="fecharModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button class="btn-confirm-modal" id="btnConfirmarAcao">
                    <i class="fas fa-check"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
    
    <form id="formConfirmar" method="POST" style="display: none;">
        <input type="hidden" name="confirmar_id" id="confirmar_id">
    </form>
    <form id="formCancelar" method="POST" style="display: none;">
        <input type="hidden" name="cancelar_id" id="cancelar_id">
    </form>
    
    <script>
        let acaoPendente = null;
        let idPendente = null;
        
        function abrirModalConfirmar(id, nome, valor) {
            idPendente = id;
            acaoPendente = 'confirmar';
            
            document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-check-circle"></i> Confirmar Pagamento';
            document.getElementById('modalIcone').innerHTML = '<i class="fas fa-check-circle" style="color: #28a745; font-size: 3rem;"></i>';
            document.getElementById('modalMensagem').innerHTML = 'Confirmar este pagamento?';
            document.getElementById('modalDetalhes').innerHTML = '<strong>Cliente:</strong> ' + nome + '<br><strong>Valor:</strong> MZN ' + valor.toFixed(2);
            
            const btnConfirmar = document.getElementById('btnConfirmarAcao');
            btnConfirmar.className = 'btn-confirm-modal';
            btnConfirmar.innerHTML = '<i class="fas fa-check"></i> Sim, confirmar';
            
            document.getElementById('modalConfirmacao').classList.add('active');
        }
        
        function abrirModalCancelar(id, nome, valor) {
            idPendente = id;
            acaoPendente = 'cancelar';
            
            document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-times-circle"></i> Cancelar Pagamento';
            document.getElementById('modalIcone').innerHTML = '<i class="fas fa-times-circle" style="color: #dc3545; font-size: 3rem;"></i>';
            document.getElementById('modalMensagem').innerHTML = 'Cancelar este pagamento?';
            document.getElementById('modalDetalhes').innerHTML = '<strong>Cliente:</strong> ' + nome + '<br><strong>Valor:</strong> MZN ' + valor.toFixed(2);
            
            const btnConfirmar = document.getElementById('btnConfirmarAcao');
            btnConfirmar.className = 'btn-confirm-modal btn-confirm-danger';
            btnConfirmar.innerHTML = '<i class="fas fa-trash"></i> Sim, cancelar';
            
            document.getElementById('modalConfirmacao').classList.add('active');
        }
        
        function fecharModal() {
            document.getElementById('modalConfirmacao').classList.remove('active');
            idPendente = null;
            acaoPendente = null;
        }
        
        function executarAcao() {
            if (acaoPendente === 'confirmar' && idPendente) {
                document.getElementById('confirmar_id').value = idPendente;
                document.getElementById('formConfirmar').submit();
            } else if (acaoPendente === 'cancelar' && idPendente) {
                document.getElementById('cancelar_id').value = idPendente;
                document.getElementById('formCancelar').submit();
            }
        }
        
        function emitirRecibo(id) {
            window.open('../pagamentos/recibo.php?id=' + id, '_blank');
        }
        
        document.getElementById('btnConfirmarAcao').onclick = function() {
            executarAcao();
            fecharModal();
        };
        
        // Fechar modal com ESC
        document.addEventListener('keydown', function(event) {
            if(event.key === 'Escape') {
                fecharModal();
            }
        });
        
        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modal = document.getElementById('modalConfirmacao');
            if(event.target === modal) {
                fecharModal();
            }
        }
    </script>
</body>
</html>