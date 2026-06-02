<?php
require_once '../config/auth.php';
Auth::cargo(['cliente']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$utilizador = Auth::utilizador();

// Estatísticas
$estatisticas = [];

// Total de reservas ativas
$query = "SELECT COUNT(*) as total FROM reservas 
          WHERE utilizador_id = :utilizador_id AND status IN ('pendente', 'confirmada')";
$stmt = $db->prepare($query);
$stmt->bindParam(':utilizador_id', $utilizador['id']);
$stmt->execute();
$estatisticas['reservas_ativas'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total de aluguéis
$query = "SELECT COUNT(*) as total FROM alugueis WHERE utilizador_id = :utilizador_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':utilizador_id', $utilizador['id']);
$stmt->execute();
$estatisticas['total_alugueis'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total gasto
$query = "SELECT COALESCE(SUM(preco_total), 0) as total FROM alugueis 
          WHERE utilizador_id = :utilizador_id AND status = 'finalizado'";
$stmt = $db->prepare($query);
$stmt->bindParam(':utilizador_id', $utilizador['id']);
$stmt->execute();
$estatisticas['total_gasto'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Aluguéis ativos
$query = "SELECT COUNT(*) as total FROM alugueis 
          WHERE utilizador_id = :utilizador_id AND status = 'ativo'";
$stmt = $db->prepare($query);
$stmt->bindParam(':utilizador_id', $utilizador['id']);
$stmt->execute();
$estatisticas['alugueis_ativos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Reservas recentes
$query = "SELECT r.*, v.marca, v.modelo, v.imagem, v.matricula, v.preco_dia
          FROM reservas r 
          JOIN viaturas v ON r.viatura_id = v.id 
          WHERE r.utilizador_id = :utilizador_id 
          ORDER BY r.criado_em DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':utilizador_id', $utilizador['id']);
$stmt->execute();
$reservas_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Aluguéis recentes
$query = "SELECT a.*, v.marca, v.modelo, v.matricula, v.preco_dia
          FROM alugueis a 
          JOIN viaturas v ON a.viatura_id = v.id 
          WHERE a.utilizador_id = :utilizador_id 
          ORDER BY a.criado_em DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':utilizador_id', $utilizador['id']);
$stmt->execute();
$alugueis_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Próximas devoluções
$query = "SELECT a.*, v.marca, v.modelo, v.matricula
          FROM alugueis a 
          JOIN viaturas v ON a.viatura_id = v.id 
          WHERE a.utilizador_id = :utilizador_id 
          AND a.status = 'ativo' 
          AND a.data_fim >= CURDATE()
          ORDER BY a.data_fim ASC LIMIT 3";
$stmt = $db->prepare($query);
$stmt->bindParam(':utilizador_id', $utilizador['id']);
$stmt->execute();
$proximas_devolucoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Painel - Cliente SIGAV</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fb;
            overflow-x: hidden;
        }

        .container-app {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        .barra-lateral {
            width: 280px;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            transition: all 0.3s ease;
        }

        .conteudo-principal {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            background: #f5f7fb;
            min-height: 100vh;
            width: calc(100% - 280px);
        }

        .barra-superior {
            background: white;
            border-radius: 1rem;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #666;
            font-size: 0.95rem;
        }

        /* Stats Grid */
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

        .stat-icon.primary { background: rgba(255, 140, 0, 0.1); color: #FF8C00; }
        .stat-icon.success { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .stat-icon.info { background: rgba(23, 162, 184, 0.1); color: #17a2b8; }
        .stat-icon.warning { background: rgba(255, 193, 7, 0.1); color: #ffc107; }

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

        /* Cards */
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
            background: #FF8C00;
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
            background: #e67e00;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.3rem 0.8rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.7rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        /* Tabela */
        .table-container {
            overflow-x: auto;
            padding: 0 1rem 1rem 1rem;
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
        .badge-confirmada { background: #d4edda; color: #155724; }
        .badge-ativo { background: #d4edda; color: #155724; }
        .badge-finalizado { background: #cce5ff; color: #004085; }

        /* Devolução Card */
        .devolucao-card {
            background: #fef9e6;
            border-radius: 1rem;
            padding: 0.8rem;
            margin-bottom: 0.5rem;
            border-left: 4px solid #FF8C00;
        }

        .devolucao-card strong {
            display: block;
            color: #1a1a2e;
        }

        .devolucao-card small {
            color: #666;
            font-size: 0.7rem;
        }

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
            .barra-lateral {
                width: 0;
                transform: translateX(-100%);
            }
            .conteudo-principal {
                margin-left: 0;
                width: 100%;
                padding: 1rem;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .card-header {
                flex-direction: column;
                align-items: stretch;
            }
            .table-container {
                overflow-x: auto;
            }
            .modern-table {
                min-width: 550px;
            }
        }
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <div class="page-header">
                <h1>Bem-vindo, <?= htmlspecialchars($utilizador['nome']) ?>!</h1>
                <p>Gerencie as suas reservas e aluguéis</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Reservas Ativas</h3>
                        <div class="stat-number"><?= $estatisticas['reservas_ativas'] ?></div>
                        <div class="stat-label">Em andamento</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Aluguéis</h3>
                        <div class="stat-number"><?= $estatisticas['total_alugueis'] ?></div>
                        <div class="stat-label">Histórico completo</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Gasto</h3>
                        <div class="stat-number">MZN <?= number_format($estatisticas['total_gasto'], 2) ?></div>
                        <div class="stat-label">Em aluguéis</div>
                    </div>
                </div>
            </div>
            
            <!-- Próximas Devoluções -->
            <?php if(count($proximas_devolucoes) > 0): ?>
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-calendar-week"></i> Próximas Devoluções</h2>
                </div>
                <div class="table-container">
                    <?php foreach($proximas_devolucoes as $devolucao): ?>
                    <?php 
                    $dias_restantes = ceil((strtotime($devolucao['data_fim']) - time()) / 86400);
                    $classe_urgente = $dias_restantes <= 2 ? 'badge-pendente' : '';
                    ?>
                    <div class="devolucao-card">
                        <strong><i class="fas fa-car"></i> <?= htmlspecialchars($devolucao['marca'] . ' ' . $devolucao['modelo']) ?> - <?= $devolucao['matricula'] ?></strong>
                        <small>Devolução prevista: <?= date('d/m/Y', strtotime($devolucao['data_fim'])) ?></small>
                        <?php if($dias_restantes <= 2): ?>
                            <span class="badge <?= $classe_urgente ?>" style="margin-left: 0.5rem;"><i class="fas fa-exclamation-triangle"></i> Urgente</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Reservas Recentes -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-history"></i> Minhas Reservas Recentes</h2>
                    <a href="reservas.php" class="btn-primary">
                        <i class="fas fa-eye"></i> Ver Todas
                    </a>
                </div>
                
                <div class="table-container">
                    <?php if(count($reservas_recentes) > 0): ?>
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Viatura</th>
                                <th>Período</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($reservas_recentes as $reserva): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?></strong><br>
                                    <small><?= $reserva['matricula'] ?></small>
                                </td>
                                <td>
                                    <i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($reserva['data_inicio'])) ?><br>
                                    <i class="fas fa-arrow-right"></i> até <?= date('d/m/Y', strtotime($reserva['data_fim'])) ?>
                                </td>
                                <td class="price">MZN <?= number_format($reserva['preco_total'], 2) ?></td>
                                <td>
                                    <?php if($reserva['status'] == 'pendente'): ?>
                                        <span class="badge badge-pendente"><i class="fas fa-clock"></i> Pendente</span>
                                    <?php elseif($reserva['status'] == 'confirmada'): ?>
                                        <span class="badge badge-confirmada"><i class="fas fa-check-circle"></i> Confirmada</span>
                                    <?php else: ?>
                                        <span class="badge badge-finalizado"><i class="fas fa-times-circle"></i> Cancelada</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($reserva['status'] == 'pendente'): ?>
                                        <button class="btn-danger" onclick="cancelarReserva(<?= $reserva['id'] ?>, '<?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?>')">
                                            <i class="fas fa-times"></i> Cancelar
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>Nenhuma reserva encontrada</p>
                        <a href="reservar.php" class="btn-primary" style="margin-top: 1rem;">
                            <i class="fas fa-plus"></i> Fazer Reserva
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Aluguéis Recentes -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-car"></i> Meus Aluguéis Recentes</h2>
                    <a href="historico.php" class="btn-primary">
                        <i class="fas fa-history"></i> Ver Histórico
                    </a>
                </div>
                
                <div class="table-container">
                    <?php if(count($alugueis_recentes) > 0): ?>
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Viatura</th>
                                <th>Período</th>
                                <th>Valor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($alugueis_recentes as $aluguer): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($aluguer['marca'] . ' ' . $aluguer['modelo']) ?></strong><br>
                                    <small><?= $aluguer['matricula'] ?></small>
                                </td>
                                <td>
                                    <i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($aluguer['data_inicio'])) ?><br>
                                    <i class="fas fa-arrow-right"></i> até <?= date('d/m/Y', strtotime($aluguer['data_fim'])) ?>
                                </td>
                                <td class="price">MZN <?= number_format($aluguer['preco_total'], 2) ?></td>
                                <td>
                                    <?php if($aluguer['status'] == 'ativo'): ?>
                                        <span class="badge badge-confirmada"><i class="fas fa-play"></i> Ativo</span>
                                    <?php else: ?>
                                        <span class="badge badge-finalizado"><i class="fas fa-check-circle"></i> Finalizado</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>Nenhum aluguel encontrado</p>
                        <a href="../veiculos.php" class="btn-primary" style="margin-top: 1rem;">
                            <i class="fas fa-car"></i> Alugar Viatura
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- MODAL CONFIRMAÇÃO -->
    <div id="modalConfirmacao" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-question-circle"></i> Confirmar Cancelamento</h3>
                <button class="modal-close" onclick="fecharModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-icon">
                    <i class="fas fa-exclamation-triangle" style="color: #ffc107; font-size: 3rem;"></i>
                </div>
                <div class="modal-message" id="modalMensagem">
                    Tem certeza que deseja cancelar esta reserva?
                </div>
                <div class="modal-details" id="modalDetalhes"></div>
                <div class="alert-info" style="margin-top: 1rem;">
                    <i class="fas fa-info-circle"></i>
                    Esta ação não pode ser desfeita.
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel-modal" onclick="fecharModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button class="btn-confirm-modal" id="btnConfirmarAcao">
                    <i class="fas fa-check"></i> Sim, cancelar
                </button>
            </div>
        </div>
    </div>
    
    <style>
        .price {
            font-weight: 700;
            color: #FF8C00;
        }
        
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
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 1.5rem;
            width: 90%;
            max-width: 450px;
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
            font-size: 0.85rem;
            color: #666;
            background: #f8f9fa;
            padding: 0.8rem;
            border-radius: 0.8rem;
            margin-top: 0.5rem;
        }
        
        .alert-info {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 0.8rem;
            border-radius: 0.8rem;
            font-size: 0.8rem;
            text-align: left;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem 1.5rem;
            display: flex;
            justify-content: flex-end;
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
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 0.8rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-confirm-modal:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
    </style>
    
    <script>
        let reservaIdParaCancelar = null;
        
        function cancelarReserva(id, viatura) {
            reservaIdParaCancelar = id;
            document.getElementById('modalMensagem').innerHTML = 'Tem certeza que deseja cancelar esta reserva?';
            document.getElementById('modalDetalhes').innerHTML = '<i class="fas fa-car"></i> <strong>Viatura:</strong> ' + viatura;
            document.getElementById('modalConfirmacao').classList.add('active');
        }
        
        function fecharModal() {
            document.getElementById('modalConfirmacao').classList.remove('active');
            reservaIdParaCancelar = null;
        }
        
        function confirmarCancelamento() {
            if(reservaIdParaCancelar) {
                window.location.href = `cancelar_reserva.php?id=${reservaIdParaCancelar}`;
            }
        }
        
        document.getElementById('btnConfirmarAcao').onclick = function() {
            confirmarCancelamento();
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
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/cliente.js"></script>
</body>
</html>