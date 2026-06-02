<?php
session_start();
require_once '../config/auth.php';
Auth::cargo(['cliente']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$utilizador = Auth::utilizador();

// Mostrar mensagem de sucesso se existir
$mensagem_sucesso = $_SESSION['mensagem_sucesso'] ?? '';
unset($_SESSION['mensagem_sucesso']);

// Buscar reservas do cliente com informação de pagamento
$query = "SELECT r.*, v.marca, v.modelo, v.imagem, v.preco_dia, v.matricula,
          CASE 
              WHEN EXISTS (SELECT 1 FROM pagamentos p WHERE p.reserva_id = r.id AND p.estado IN ('confirmado', 'pendente')) 
              THEN 1 
              ELSE 0 
          END as tem_pagamento
          FROM reservas r 
          JOIN viaturas v ON r.viatura_id = v.id 
          WHERE r.utilizador_id = :utilizador_id 
          ORDER BY r.criado_em DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':utilizador_id', $utilizador['id']);
$stmt->execute();
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$query = "SELECT 
          COUNT(CASE WHEN status = 'pendente' THEN 1 END) as pendentes,
          COUNT(CASE WHEN status = 'confirmada' THEN 1 END) as confirmadas,
          COUNT(CASE WHEN status = 'cancelada' THEN 1 END) as canceladas
          FROM reservas WHERE utilizador_id = :utilizador_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':utilizador_id', $utilizador['id']);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Minhas Reservas - SIGAV</title>
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
            grid-template-columns: repeat(3, 1fr);
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

        .stat-icon.pendente { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .stat-icon.confirmada { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .stat-icon.cancelada { background: rgba(220, 53, 69, 0.1); color: #dc3545; }

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

        .btn-pagamento {
            background: #FF8C00;
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
            text-decoration: none;
        }

        .btn-pagamento:hover {
            background: #e67e00;
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
        .badge-cancelada { background: #f8d7da; color: #721c24; }
        .badge-pago { background: #d4edda; color: #155724; }

        .price {
            font-weight: 700;
            color: #FF8C00;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #999;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
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

        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }

        .toast-success { border-left: 4px solid #28a745; }
        .toast-success i { color: #28a745; }

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
            background: #f8f9fa;
            padding: 0.8rem;
            border-radius: 0.8rem;
            margin-top: 1rem;
            font-size: 0.85rem;
            color: #666;
        }

        .alert-info {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 0.8rem;
            border-radius: 0.8rem;
            margin-top: 1rem;
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

        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
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
            
            <?php if($mensagem_sucesso): ?>
            <div class="toast-notification toast-success" id="toast">
                <i class="fas fa-check-circle fa-lg"></i>
                <span><?= htmlspecialchars($mensagem_sucesso) ?></span>
            </div>
            <script>setTimeout(() => { const t = document.getElementById('toast'); if(t) t.style.animation = 'slideOutRight 0.3s ease'; setTimeout(() => t.remove(), 300); }, 3000);</script>
            <?php endif; ?>
            
            <div class="page-header">
                <h1>Minhas Reservas</h1>
                <p>Gerencie todas as suas reservas de viaturas</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon pendente">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Reservas Pendentes</h3>
                        <div class="stat-number"><?= $stats['pendentes'] ?? 0 ?></div>
                        <div class="stat-label">Aguardando confirmação</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon confirmada">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Reservas Confirmadas</h3>
                        <div class="stat-number"><?= $stats['confirmadas'] ?? 0 ?></div>
                        <div class="stat-label">Aprovadas</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon cancelada">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Reservas Canceladas</h3>
                        <div class="stat-number"><?= $stats['canceladas'] ?? 0 ?></div>
                        <div class="stat-label">Recusadas ou canceladas</div>
                    </div>
                </div>
            </div>
            
            <!-- Main Card -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-bookmark"></i> Lista de Reservas</h2>
                    <a href="catalogo.php" class="btn-primary">
                        <i class="fas fa-plus"></i> Nova Reserva
                    </a>
                </div>
                
                <?php if(count($reservas) > 0): ?>
                <div class="table-container">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Viatura</th>
                                <th>Período</th>
                                <th>Dias</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($reservas as $reserva): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?></strong><br>
                                    <small style="color: #999;"><?= $reserva['matricula'] ?></small>
                                </td>
                                <td>
                                    <i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($reserva['data_inicio'])) ?><br>
                                    <i class="fas fa-arrow-right"></i> até <?= date('d/m/Y', strtotime($reserva['data_fim'])) ?>
                                </td>
                                <td><?= $reserva['total_dias'] ?> dias</small></td>
                                <td class="price">MZN <?= number_format($reserva['preco_total'], 2) ?></td>
                                <td>
                                    <?php if($reserva['status'] == 'pendente'): ?>
                                        <span class="badge badge-pendente"><i class="fas fa-clock"></i> Pendente</span>
                                    <?php elseif($reserva['status'] == 'confirmada'): ?>
                                        <span class="badge badge-confirmada"><i class="fas fa-check-circle"></i> Confirmada</span>
                                    <?php else: ?>
                                        <span class="badge badge-cancelada"><i class="fas fa-times-circle"></i> Cancelada</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($reserva['status'] == 'pendente'): ?>
                                        <button class="btn-danger" onclick="abrirModalCancelar(<?= $reserva['id'] ?>, '<?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?>')">
                                            <i class="fas fa-times"></i> Cancelar
                                        </button>
                                    <?php elseif($reserva['status'] == 'confirmada'): ?>
                                        <?php if($reserva['tem_pagamento'] == 0): ?>
                                            <a href="pagamentos.php?reserva_id=<?= $reserva['id'] ?>" class="btn-pagamento">
                                                <i class="fas fa-credit-card"></i> Pagar
                                            </a>
                                        <?php else: ?>
                                            <span class="badge badge-pago">
                                                <i class="fas fa-check-circle"></i> Pago
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <p>Nenhuma reserva encontrada</p>
                    <small>Comece a reservar o seu veículo ideal!</small>
                    <a href="catalogo.php" class="btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-search"></i> Ver Viaturas
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- MODAL DE CONFIRMAÇÃO DE CANCELAMENTO -->
    <div id="modalCancelar" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Cancelar Reserva</h3>
                <button class="modal-close" onclick="fecharModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-icon">
                    <i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i>
                </div>
                <div class="modal-message" id="modalMensagem">
                    Tem certeza que deseja cancelar esta reserva?
                </div>
                <div class="modal-details" id="modalDetalhes"></div>
                <div class="alert-info">
                    <i class="fas fa-info-circle"></i>
                    Esta ação não pode ser desfeita. O cancelamento pode estar sujeito a multas conforme política da empresa.
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel-modal" onclick="fecharModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button class="btn-confirm-modal" id="btnConfirmarCancelamento">
                    <i class="fas fa-trash"></i> Sim, cancelar
                </button>
            </div>
        </div>
    </div>
    
    <script>
        let reservaIdParaCancelar = null;
        
        function abrirModalCancelar(id, viatura) {
            reservaIdParaCancelar = id;
            document.getElementById('modalDetalhes').innerHTML = '<i class="fas fa-car"></i> <strong>Viatura:</strong> ' + viatura;
            document.getElementById('modalCancelar').classList.add('active');
        }
        
        function fecharModal() {
            document.getElementById('modalCancelar').classList.remove('active');
            reservaIdParaCancelar = null;
        }
        
        function confirmarCancelamento() {
            if(reservaIdParaCancelar) {
                window.location.href = `cancelar_reserva.php?id=${reservaIdParaCancelar}`;
            }
        }
        
        document.getElementById('btnConfirmarCancelamento').onclick = function() {
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
            const modal = document.getElementById('modalCancelar');
            if(event.target === modal) {
                fecharModal();
            }
        }
    </script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/cliente.js"></script>
</body>
</html>