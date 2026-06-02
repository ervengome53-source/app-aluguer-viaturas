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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Reservas - SIGAV</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .container-app { display: flex; min-height: 100vh; }
        .conteudo-principal { flex: 1; margin-left: 270px; padding: 1.5rem; }
        
        /* Estatísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
        }
        .stat-label {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.3rem;
        }
        .stat-pendente .stat-number { color: #ffc107; }
        .stat-confirmada .stat-number { color: #28a745; }
        .stat-rejeitada .stat-number { color: #dc3545; }
        
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #1E3A5F, #2a5298);
        }
        
        .card-header h3 {
            color: white;
            margin: 0;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filtros {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-filter {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.75rem;
            transition: all 0.3s ease;
        }
        
        .btn-filter-active {
            background: #FF8C00;
            color: white;
        }
        
        .btn-filter-inactive {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .btn-filter-inactive:hover { background: rgba(255,255,255,0.3); }
        
        .table-container { overflow-x: auto; }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th {
            padding: 0.8rem;
            text-align: left;
            font-weight: 600;
            color: #1E3A5F;
            border-bottom: 2px solid #FF8C00;
            font-size: 0.75rem;
            text-transform: uppercase;
        }
        
        .table td {
            padding: 0.8rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .table tr:hover td { background: #fef9e6; }
        
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .badge-pendente { background: #fff3cd; color: #856404; }
        .badge-confirmada { background: #d4edda; color: #155724; }
        .badge-rejeitada { background: #f8d7da; color: #721c24; }
        
        .btn-sm {
            padding: 0.25rem 0.6rem;
            font-size: 0.7rem;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; transform: translateY(-2px); }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; transform: translateY(-2px); }
        .btn-info { background: #17a2b8; color: white; }
        .btn-info:hover { background: #138496; transform: translateY(-2px); }
        
        .acoes-cell { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
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
        
        .modal-header.confirmar { background: #28a745; color: white; }
        .modal-header.rejeitar { background: #dc3545; color: white; }
        
        .modal-header h3 {
            margin: 0;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            opacity: 0.8;
        }
        
        .modal-close:hover { opacity: 1; }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .modal-info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
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
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .btn-modal-confirm {
            flex: 1;
            padding: 0.7rem;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .btn-modal-confirm.rejeitar { background: #dc3545; }
        .btn-modal-confirm:hover { filter: brightness(0.9); }
        
        .mensagem-flutuante {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #28a745;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .mensagem-flutuante.erro {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }
        
        .close-mensagem {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: inherit;
        }
        
        @media (max-width: 768px) {
            .conteudo-principal { margin-left: 0; padding: 1rem; }
            .card-header { flex-direction: column; gap: 1rem; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <?php if($mensagem): ?>
            <div class="mensagem-flutuante">
                <span><i class="fas fa-check-circle"></i> <?= htmlspecialchars($mensagem) ?></span>
                <button class="close-mensagem" onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
            <?php endif; ?>
            
            <?php if($erro): ?>
            <div class="mensagem-flutuante erro">
                <span><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($erro) ?></span>
                <button class="close-mensagem" onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
            <?php endif; ?>
            
            <!-- Estatísticas -->
            <div class="stats-grid">
                <div class="stat-card stat-pendente">
                    <div class="stat-number"><?= $stats['total_pendentes'] ?? 0 ?></div>
                    <div class="stat-label"><i class="fas fa-clock"></i> Pendentes</div>
                </div>
                <div class="stat-card stat-confirmada">
                    <div class="stat-number"><?= $stats['total_confirmadas'] ?? 0 ?></div>
                    <div class="stat-label"><i class="fas fa-check-circle"></i> Confirmadas</div>
                </div>
                <div class="stat-card stat-rejeitada">
                    <div class="stat-number"><?= $stats['total_rejeitadas'] ?? 0 ?></div>
                    <div class="stat-label"><i class="fas fa-times-circle"></i> Rejeitadas</div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-check"></i> Gestão de Reservas</h3>
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
                    <table class="table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-user"></i> Cliente</th>
                                <th><i class="fas fa-car"></i> Viatura</th>
                                <th><i class="fas fa-calendar"></i> Período</th>
                                <th><i class="fas fa-money-bill-wave"></i> Valor</th>
                                <th><i class="fas fa-chart-line"></i> Status</th>
                                <th><i class="fas fa-cogs"></i> Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($reservas) > 0): ?>
                                <?php foreach($reservas as $reserva): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-user-circle"></i> <strong><?= htmlspecialchars($reserva['cliente_nome']) ?></strong><br>
                                        <small><i class="fas fa-envelope"></i> <?= htmlspecialchars($reserva['cliente_email']) ?></small><br>
                                        <small><i class="fas fa-phone"></i> <?= htmlspecialchars($reserva['cliente_telefone'] ?? '---') ?></small>
                                    </td>
                                    <td>
                                        <i class="fas fa-car-side"></i> <?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?><br>
                                        <small><i class="fas fa-id-card"></i> <?= htmlspecialchars($reserva['matricula']) ?></small><br>
                                        <small><i class="fas fa-coins"></i> MZN <?= number_format($reserva['preco_dia'], 2) ?>/dia</small>
                                    </td>
                                    <td>
                                        <i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($reserva['data_inicio'])) ?><br>
                                        <small><i class="fas fa-arrow-right"></i> até <?= date('d/m/Y', strtotime($reserva['data_fim'])) ?></small><br>
                                        <small><i class="fas fa-sun"></i> <?= $reserva['total_dias'] ?> dias</small>
                                    </td>
                                    <td><i class="fas fa-coins"></i> <strong>MZN <?= number_format($reserva['preco_total'], 2) ?></strong></td>
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
                            <?php else: ?>
                                <tr><td colspan="6" style="text-align: center; padding: 3rem;">
                                    <i class="fas fa-inbox" style="font-size: 3rem; color: #ccc;"></i><br>
                                    Nenhuma reserva encontrada
                                </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
        // Abrir modal de confirmação
        function abrirModalConfirmar(id, cliente, viatura, dataInicio, dataFim, valor) {
            document.getElementById('confirmar_id').value = id;
            document.getElementById('confirmar_cliente').innerHTML = cliente;
            document.getElementById('confirmar_viatura').innerHTML = viatura;
            document.getElementById('confirmar_periodo').innerHTML = dataInicio + ' até ' + dataFim;
            document.getElementById('confirmar_valor').innerHTML = 'MZN ' + parseFloat(valor).toLocaleString('pt-MZ', {minimumFractionDigits: 2});
            document.getElementById('modalConfirmar').style.display = 'flex';
        }
        
        // Abrir modal de rejeição
        function abrirModalRejeitar(id, cliente, viatura) {
            document.getElementById('rejeitar_id').value = id;
            document.getElementById('rejeitar_cliente').innerHTML = cliente;
            document.getElementById('rejeitar_viatura').innerHTML = viatura;
            document.getElementById('modalRejeitar').style.display = 'flex';
        }
        
        // Fechar modal
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
    </script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/funcionario.js"></script>
</body>
</html>