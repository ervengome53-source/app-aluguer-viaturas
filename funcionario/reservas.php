<?php
require_once '../config/auth.php';
Auth::cargo(['funcionario']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$filtro = $_GET['filtro'] ?? 'pendentes';
$mensagem = '';
$erro = '';

// Processar confirmação via POST (modal)
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['confirmar_id'])) {
        $id = (int)$_POST['confirmar_id'];
        $stmt = $db->prepare("UPDATE reservas SET status = 'confirmada' WHERE id = :id");
        $stmt->bindParam(':id', $id);
        if($stmt->execute()) {
            $mensagem = "Reserva confirmada com sucesso!";
        } else {
            $erro = "Erro ao confirmar reserva.";
        }
    }
    
    if(isset($_POST['rejeitar_id'])) {
        $id = (int)$_POST['rejeitar_id'];
        $stmt = $db->prepare("UPDATE reservas SET status = 'rejeitada' WHERE id = :id");
        $stmt->bindParam(':id', $id);
        if($stmt->execute()) {
            $mensagem = "Reserva rejeitada com sucesso!";
        } else {
            $erro = "Erro ao rejeitar reserva.";
        }
    }
}

// Buscar reservas conforme filtro
if($filtro == 'pendentes') {
    $query = "SELECT r.*, u.nome as cliente_nome, u.email as cliente_email, u.telefone as cliente_telefone,
              v.marca, v.modelo, v.matricula, v.imagem, v.preco_dia
              FROM reservas r
              JOIN utilizadores u ON r.utilizador_id = u.id
              JOIN viaturas v ON r.viatura_id = v.id
              WHERE r.status = 'pendente'
              ORDER BY r.criado_em ASC";
} elseif($filtro == 'confirmadas') {
    $query = "SELECT r.*, u.nome as cliente_nome, u.email as cliente_email, u.telefone as cliente_telefone,
              v.marca, v.modelo, v.matricula, v.imagem, v.preco_dia
              FROM reservas r
              JOIN utilizadores u ON r.utilizador_id = u.id
              JOIN viaturas v ON r.viatura_id = v.id
              WHERE r.status = 'confirmada'
              ORDER BY r.data_inicio ASC";
} else {
    $query = "SELECT r.*, u.nome as cliente_nome, u.email as cliente_email, u.telefone as cliente_telefone,
              v.marca, v.modelo, v.matricula, v.imagem, v.preco_dia
              FROM reservas r
              JOIN utilizadores u ON r.utilizador_id = u.id
              JOIN viaturas v ON r.viatura_id = v.id
              ORDER BY r.criado_em DESC";
}

$stmt = $db->prepare($query);
$stmt->execute();
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar estatísticas
$statsQuery = "SELECT 
               COUNT(CASE WHEN status = 'pendente' THEN 1 END) as total_pendentes,
               COUNT(CASE WHEN status = 'confirmada' THEN 1 END) as total_confirmadas,
               COUNT(CASE WHEN status = 'rejeitada' THEN 1 END) as total_rejeitadas
               FROM reservas";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Gestão de Reservas - SIGAV</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
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
        .stat-icon.rejeitada { background: rgba(220, 53, 69, 0.1); color: #dc3545; }

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
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .card-header h3 {
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Filtros */
        .filtros {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-filter {
            padding: 0.4rem 1rem;
            border-radius: 2rem;
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .btn-filter-active {
            background: #FF8C00;
            color: white;
        }

        .btn-filter-inactive {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .btn-filter-inactive:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        /* Tabela */
        .table-container {
            padding: 0 1rem 1rem 1rem;
            overflow-x: auto;
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
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
        }

        .modern-table td {
            padding: 0.8rem;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.85rem;
            vertical-align: middle;
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
        .badge-rejeitada { background: #f8d7da; color: #721c24; }

        /* Botões */
        .btn-sm {
            padding: 0.3rem 0.8rem;
            font-size: 0.7rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.3s ease;
            font-weight: 500;
            text-decoration: none;
        }

        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; transform: translateY(-2px); }

        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; transform: translateY(-2px); }

        .btn-info { background: #17a2b8; color: white; }
        .btn-info:hover { background: #138496; transform: translateY(-2px); }

        .acoes-cell {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
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

        .modal-content {
            background: white;
            border-radius: 1.5rem;
            width: 90%;
            max-width: 500px;
            animation: modalFadeIn 0.3s ease;
            overflow: hidden;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header.confirmar { background: linear-gradient(135deg, #28a745, #20c997); color: white; }
        .modal-header.rejeitar { background: linear-gradient(135deg, #dc3545, #c82333); color: white; }

        .modal-header h3 {
            margin: 0;
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
            opacity: 0.8;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            opacity: 1;
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-info {
            background: #f8f9fa;
            border-radius: 1rem;
            padding: 1rem;
            margin: 1rem 0;
        }

        .modal-info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.6rem 0;
            border-bottom: 1px solid #e9ecef;
            font-size: 0.85rem;
        }

        .modal-info-row:last-child {
            border-bottom: none;
        }

        .modal-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn-modal-cancel {
            flex: 1;
            padding: 0.7rem;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 0.8rem;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-modal-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-modal-confirm {
            flex: 1;
            padding: 0.7rem;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 0.8rem;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-modal-confirm.rejeitar {
            background: #dc3545;
        }

        .btn-modal-confirm:hover {
            transform: translateY(-2px);
            filter: brightness(0.95);
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

        /* Responsivo */
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
            .filtros {
                justify-content: center;
            }
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
                <span><?= htmlspecialchars($mensagem) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if($erro): ?>
            <div class="toast-notification toast-error" id="toast">
                <i class="fas fa-exclamation-circle fa-lg"></i>
                <span><?= htmlspecialchars($erro) ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Page Header -->
            <div class="page-header">
                <h1>Gestão de Reservas</h1>
                <p>Gerencie todas as reservas do sistema</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon pendente">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Reservas Pendentes</h3>
                        <div class="stat-number"><?= $stats['total_pendentes'] ?? 0 ?></div>
                        <div class="stat-label">Aguardando ação</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon confirmada">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Reservas Confirmadas</h3>
                        <div class="stat-number"><?= $stats['total_confirmadas'] ?? 0 ?></div>
                        <div class="stat-label">Aprovadas</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon rejeitada">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Reservas Rejeitadas</h3>
                        <div class="stat-number"><?= $stats['total_rejeitadas'] ?? 0 ?></div>
                        <div class="stat-label">Recusadas</div>
                    </div>
                </div>
            </div>
            
            <!-- Main Card -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-check"></i> Lista de Reservas</h3>
                    <div class="filtros">
                        <a href="?filtro=pendentes" class="btn-filter <?= $filtro == 'pendentes' ? 'btn-filter-active' : 'btn-filter-inactive' ?>">
                            <i class="fas fa-clock"></i> Pendentes
                        </a>
                        <a href="?filtro=confirmadas" class="btn-filter <?= $filtro == 'confirmadas' ? 'btn-filter-active' : 'btn-filter-inactive' ?>">
                            <i class="fas fa-check-circle"></i> Confirmadas
                        </a>
                        <a href="?filtro=todas" class="btn-filter <?= $filtro == 'todas' ? 'btn-filter-active' : 'btn-filter-inactive' ?>">
                            <i class="fas fa-list"></i> Todas
                        </a>
                    </div>
                </div>
                
                <div class="table-container">
                    <?php if(count($reservas) > 0): ?>
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Viatura</th>
                                <th>Período</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($reservas as $reserva): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($reserva['cliente_nome']) ?></strong><br>
                                    <small style="color: #999;"><?= $reserva['cliente_email'] ?></small><br>
                                    <small><i class="fas fa-phone"></i> <?= $reserva['cliente_telefone'] ?? '---' ?></small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?></strong><br>
                                    <small><?= $reserva['matricula'] ?></small><br>
                                    <small><i class="fas fa-coins"></i> MZN <?= number_format($reserva['preco_dia'], 2) ?>/dia</small>
                                </td>
                                <td>
                                    <i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($reserva['data_inicio'])) ?><br>
                                    <i class="fas fa-arrow-right"></i> até <?= date('d/m/Y', strtotime($reserva['data_fim'])) ?><br>
                                    <small><?= $reserva['total_dias'] ?> dias</small>
                                </td>
                                <td class="price">MZN <?= number_format($reserva['preco_total'], 2) ?></td>
                                <td>
                                    <?php if($reserva['status'] == 'pendente'): ?>
                                        <span class="badge badge-pendente"><i class="fas fa-clock"></i> Pendente</span>
                                    <?php elseif($reserva['status'] == 'confirmada'): ?>
                                        <span class="badge badge-confirmada"><i class="fas fa-check-circle"></i> Confirmada</span>
                                    <?php else: ?>
                                        <span class="badge badge-rejeitada"><i class="fas fa-times-circle"></i> Rejeitada</span>
                                    <?php endif; ?>
                                </td>
                                <td class="acoes-cell">
                                    <?php if($reserva['status'] == 'pendente'): ?>
                                        <button class="btn-sm btn-success" onclick="abrirModalConfirmar(<?= $reserva['id'] ?>, '<?= htmlspecialchars($reserva['cliente_nome']) ?>', '<?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?>', '<?= date('d/m/Y', strtotime($reserva['data_inicio'])) ?>', '<?= date('d/m/Y', strtotime($reserva['data_fim'])) ?>', <?= $reserva['preco_total'] ?>)">
                                            <i class="fas fa-check"></i> Confirmar
                                        </button>
                                        <button class="btn-sm btn-danger" onclick="abrirModalRejeitar(<?= $reserva['id'] ?>, '<?= htmlspecialchars($reserva['cliente_nome']) ?>', '<?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?>')">
                                            <i class="fas fa-times"></i> Rejeitar
                                        </button>
                                    <?php endif; ?>
                                    <a href="detalhe_reserva.php?id=<?= $reserva['id'] ?>" class="btn-sm btn-info">
                                        <i class="fas fa-eye"></i> Detalhes
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                     </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>Nenhuma reserva encontrada</p>
                        <small>Altere o filtro para ver mais resultados</small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- MODAL DE CONFIRMAÇÃO -->
    <div id="modalConfirmar" class="modal">
        <div class="modal-content">
            <div class="modal-header confirmar">
                <h3><i class="fas fa-check-circle"></i> Confirmar Reserva</h3>
                <button class="modal-close" onclick="fecharModal('modalConfirmar')">&times;</button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja <strong>confirmar</strong> esta reserva?</p>
                <div class="modal-info">
                    <div class="modal-info-row">
                        <span><i class="fas fa-user"></i> Cliente:</span>
                        <strong id="confirmar_cliente">-</strong>
                    </div>
                    <div class="modal-info-row">
                        <span><i class="fas fa-car"></i> Viatura:</span>
                        <strong id="confirmar_viatura">-</strong>
                    </div>
                    <div class="modal-info-row">
                        <span><i class="fas fa-calendar"></i> Período:</span>
                        <strong id="confirmar_periodo">-</strong>
                    </div>
                    <div class="modal-info-row">
                        <span><i class="fas fa-money-bill-wave"></i> Valor:</span>
                        <strong id="confirmar_valor" style="color: #28a745;">-</strong>
                    </div>
                </div>
                <form method="POST" id="formConfirmar">
                    <input type="hidden" name="confirmar_id" id="confirmar_id">
                    <div class="modal-buttons">
                        <button type="button" class="btn-modal-cancel" onclick="fecharModal('modalConfirmar')">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn-modal-confirm">
                            <i class="fas fa-check"></i> Confirmar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- MODAL DE REJEIÇÃO -->
    <div id="modalRejeitar" class="modal">
        <div class="modal-content">
            <div class="modal-header rejeitar">
                <h3><i class="fas fa-times-circle"></i> Rejeitar Reserva</h3>
                <button class="modal-close" onclick="fecharModal('modalRejeitar')">&times;</button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja <strong>rejeitar</strong> esta reserva?</p>
                <div class="modal-info">
                    <div class="modal-info-row">
                        <span><i class="fas fa-user"></i> Cliente:</span>
                        <strong id="rejeitar_cliente">-</strong>
                    </div>
                    <div class="modal-info-row">
                        <span><i class="fas fa-car"></i> Viatura:</span>
                        <strong id="rejeitar_viatura">-</strong>
                    </div>
                </div>
                <form method="POST" id="formRejeitar">
                    <input type="hidden" name="rejeitar_id" id="rejeitar_id">
                    <div class="modal-buttons">
                        <button type="button" class="btn-modal-cancel" onclick="fecharModal('modalRejeitar')">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn-modal-confirm rejeitar">
                            <i class="fas fa-trash"></i> Rejeitar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-hide toast after 3 seconds
        const toast = document.getElementById('toast');
        if(toast) {
            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        function abrirModalConfirmar(id, cliente, viatura, dataInicio, dataFim, valor) {
            document.getElementById('confirmar_id').value = id;
            document.getElementById('confirmar_cliente').innerHTML = cliente;
            document.getElementById('confirmar_viatura').innerHTML = viatura;
            document.getElementById('confirmar_periodo').innerHTML = dataInicio + ' até ' + dataFim;
            document.getElementById('confirmar_valor').innerHTML = 'MZN ' + parseFloat(valor).toLocaleString('pt-MZ', {minimumFractionDigits: 2});
            document.getElementById('modalConfirmar').style.display = 'flex';
        }
        
        function abrirModalRejeitar(id, cliente, viatura) {
            document.getElementById('rejeitar_id').value = id;
            document.getElementById('rejeitar_cliente').innerHTML = cliente;
            document.getElementById('rejeitar_viatura').innerHTML = viatura;
            document.getElementById('modalRejeitar').style.display = 'flex';
        }
        
        function fecharModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modalConfirmar = document.getElementById('modalConfirmar');
            const modalRejeitar = document.getElementById('modalRejeitar');
            if (event.target === modalConfirmar) {
                modalConfirmar.style.display = 'none';
            }
            if (event.target === modalRejeitar) {
                modalRejeitar.style.display = 'none';
            }
        }
        
        // Fechar modal com ESC
        document.addEventListener('keydown', function(event) {
            if(event.key === 'Escape') {
                fecharModal('modalConfirmar');
                fecharModal('modalRejeitar');
            }
        });
    </script>
    
    <style>
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        .price {
            font-weight: 700;
            color: #FF8C00;
        }
    </style>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/funcionario.js"></script>
</body>
</html>