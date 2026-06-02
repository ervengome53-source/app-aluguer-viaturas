<?php
require_once '../config/auth.php';
Auth::cargo(['funcionario']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$busca = $_GET['busca'] ?? '';
$mensagem = '';
$erro = '';

// Processar criação de cliente
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'criar') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = password_hash('123456', PASSWORD_DEFAULT);
    $telefone = $_POST['telefone'] ?? '';
    $morada = $_POST['morada'] ?? '';
    $nif = $_POST['nif'] ?? '';
    
    $check = $db->prepare("SELECT id FROM utilizadores WHERE email = :email");
    $check->bindParam(':email', $email);
    $check->execute();
    
    if($check->rowCount() > 0) {
        $erro = '<i class="fas fa-exclamation-triangle"></i> Email já registado!';
    } else {
        $query = "INSERT INTO utilizadores (nome, email, senha, telefone, morada, nif, cargo, status) 
                  VALUES (:nome, :email, :senha, :telefone, :morada, :nif, 'cliente', 'ativo')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':senha', $senha);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':morada', $morada);
        $stmt->bindParam(':nif', $nif);
        
        if($stmt->execute()) {
            $mensagem = '<i class="fas fa-check-circle"></i> Cliente criado com sucesso! Senha: 123456';
            echo '<script>setTimeout(function(){ window.location.href = "clientes.php"; }, 2000);</script>';
        } else {
            $erro = '<i class="fas fa-exclamation-triangle"></i> Erro ao criar cliente';
        }
    }
}

// Processar ativação/desativação
if(isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = $_GET['status'] ?? '';
    $novoStatus = $status == 'ativo' ? 'inativo' : 'ativo';
    
    $query = "UPDATE utilizadores SET status = :status WHERE id = :id AND cargo = 'cliente'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $novoStatus);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    header('Location: clientes.php');
    exit();
}

// Buscar clientes
$query = "SELECT * FROM utilizadores WHERE cargo = 'cliente'";
if($busca) {
    $query .= " AND (nome LIKE :busca OR email LIKE :busca OR telefone LIKE :busca)";
}
$query .= " ORDER BY id DESC";
$stmt = $db->prepare($query);
if($busca) {
    $buscaParam = "%$busca%";
    $stmt->bindParam(':busca', $buscaParam);
}
$stmt->execute();
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$total_clientes = count($clientes);
$clientes_ativos = count(array_filter($clientes, function($c) { return $c['status'] == 'ativo'; }));
$clientes_inativos = $total_clientes - $clientes_ativos;
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Gestão de Clientes - SIGAV</title>
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
        
        .stat-icon.total { background: rgba(255, 140, 0, 0.1); color: #FF8C00; }
        .stat-icon.ativo { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .stat-icon.inativo { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
        
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
        
        .card-header h2 {
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .search-area {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-input {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.8rem;
            font-size: 0.85rem;
            width: 220px;
            outline: none;
        }
        
        .btn-search {
            background: #FF8C00;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
        }
        
        .btn-search:hover {
            background: #e67e00;
            transform: translateY(-2px);
        }
        
        .btn-clear {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            text-decoration: none;
        }
        
        .btn-clear:hover {
            background: #5a6268;
            transform: translateY(-2px);
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
            font-size: 0.85rem;
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
        
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.3rem 0.8rem;
            border-radius: 2rem;
            font-size: 0.7rem;
            font-weight: 500;
        }
        
        .badge-ativo { background: #d4edda; color: #155724; }
        .badge-inativo { background: #f8d7da; color: #721c24; }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 0.3rem 0.7rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            font-size: 0.7rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-history {
            background: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
        }
        
        .btn-history:hover {
            background: #17a2b8;
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-disable {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .btn-disable:hover {
            background: #dc3545;
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-enable {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .btn-enable:hover {
            background: #28a745;
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
            max-width: 550px;
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
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-group label i {
            color: #FF8C00;
            width: 20px;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 0.8rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #FF8C00;
            box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.1);
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .alert-info {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 0.8rem;
            border-radius: 0.8rem;
            margin-top: 1rem;
            font-size: 0.8rem;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem 1.5rem;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            border-top: 1px solid #eee;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 0.8rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .btn-save {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 0.8rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-save:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        /* Modal Confirmação */
        .modal-confirm {
            background: white;
            border-radius: 1.5rem;
            width: 90%;
            max-width: 420px;
            overflow: hidden;
            text-align: center;
        }
        
        .modal-confirm .modal-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .modal-confirm .modal-message {
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .modal-confirm .modal-details {
            font-size: 0.85rem;
            color: #666;
        }
        
        .btn-confirm-modal {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 0.8rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-confirm-modal:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .btn-confirm-success {
            background: #28a745;
        }
        
        .btn-confirm-success:hover {
            background: #218838;
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
            padding: 3rem;
            color: #999;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .barra-lateral { width: 0; transform: translateX(-100%); }
            .conteudo-principal { margin-left: 0; width: 100%; padding: 1rem; }
            .stats-grid { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
            .card-header { flex-direction: column; }
            .search-area { width: 100%; justify-content: center; }
            .table-container { overflow-x: auto; }
            .modern-table { min-width: 600px; }
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
                <h1>Gestão de Clientes</h1>
                <p>Gerencie todos os clientes do sistema</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total de Clientes</h3>
                        <div class="stat-number"><?= $total_clientes ?></div>
                        <div class="stat-label">Registados no sistema</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon ativo">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Clientes Ativos</h3>
                        <div class="stat-number"><?= $clientes_ativos ?></div>
                        <div class="stat-label">Contas ativas</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon inativo">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Clientes Inativos</h3>
                        <div class="stat-number"><?= $clientes_inativos ?></div>
                        <div class="stat-label">Contas bloqueadas</div>
                    </div>
                </div>
            </div>
            
            <!-- Main Card -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-users" style="color: #FF8C00;"></i> Lista de Clientes</h2>
                    <div class="search-area">
                        <form method="GET" style="display: flex; gap: 0.5rem;">
                            <input type="text" name="busca" class="search-input" placeholder="Buscar cliente..." value="<?= htmlspecialchars($busca) ?>">
                            <button type="submit" class="btn-search"><i class="fas fa-search"></i> Buscar</button>
                            <?php if($busca): ?>
                                <a href="clientes.php" class="btn-clear"><i class="fas fa-times"></i> Limpar</a>
                            <?php endif; ?>
                        </form>
                        <button class="btn-primary" onclick="abrirModalNovoCliente()">
                            <i class="fas fa-plus"></i> Novo Cliente
                        </button>
                    </div>
                </div>
                
                <div class="table-container">
                    <?php if(count($clientes) > 0): ?>
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Email</th>
                                <th>Telefone</th>
                                <th>NUIT</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($clientes as $cliente): ?>
                            <tr>
                                <td>
                                    <span style="font-weight: bold; color: #FF8C00;">#<?= $cliente['id'] ?></span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($cliente['nome']) ?></strong>
                                    <?php if($cliente['morada']): ?>
                                        <br><small style="color: #999;"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars(substr($cliente['morada'], 0, 30)) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <i class="fas fa-envelope" style="color: #999;"></i> <?= htmlspecialchars($cliente['email']) ?>
                                </td>
                                <td>
                                    <?php if($cliente['telefone']): ?>
                                        <i class="fas fa-phone" style="color: #999;"></i> <?= $cliente['telefone'] ?>
                                    <?php else: ?>
                                        <span style="color: #ccc;">---</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($cliente['nif']): ?>
                                        <i class="fas fa-id-card" style="color: #999;"></i> <?= $cliente['nif'] ?>
                                    <?php else: ?>
                                        <span style="color: #ccc;">---</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($cliente['status'] == 'ativo'): ?>
                                        <span class="badge badge-ativo"><i class="fas fa-check-circle"></i> Ativo</span>
                                    <?php else: ?>
                                        <span class="badge badge-inativo"><i class="fas fa-ban"></i> Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="action-buttons">
                                    <a href="historico_cliente.php?id=<?= $cliente['id'] ?>" class="btn-action btn-history">
                                        <i class="fas fa-history"></i> Histórico
                                    </a>
                                    <?php if($cliente['status'] == 'ativo'): ?>
                                        <button class="btn-action btn-disable" onclick="confirmarDesativar(<?= $cliente['id'] ?>, '<?= addslashes($cliente['nome']) ?>')">
                                            <i class="fas fa-ban"></i> Desativar
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-action btn-enable" onclick="confirmarAtivar(<?= $cliente['id'] ?>, '<?= addslashes($cliente['nome']) ?>')">
                                            <i class="fas fa-check"></i> Ativar
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
                        <p>Nenhum cliente encontrado</p>
                        <button class="btn-primary" onclick="abrirModalNovoCliente()" style="margin-top: 1rem;">
                            <i class="fas fa-plus"></i> Adicionar Primeiro Cliente
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- MODAL NOVO CLIENTE -->
    <div id="modalNovoCliente" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Novo Cliente</h3>
                <button class="modal-close" onclick="fecharModalNovoCliente()">&times;</button>
            </div>
            <form method="POST" action="clientes.php">
                <input type="hidden" name="acao" value="criar">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Nome Completo *</label>
                            <input type="text" name="nome" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Telefone</label>
                            <input type="tel" name="telefone" class="form-control" placeholder="+258 84 123 4567">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-id-card"></i> NUIT</label>
                            <input type="text" name="nif" class="form-control" placeholder="Número de identificação fiscal">
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Morada</label>
                        <textarea name="morada" class="form-control" rows="2" placeholder="Morada completa do cliente"></textarea>
                    </div>
                    <div class="alert-info">
                        <i class="fas fa-info-circle"></i>
                        A senha padrão será: <strong>123456</strong>. O cliente deverá alterar no primeiro acesso.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="fecharModalNovoCliente()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- MODAL DE CONFIRMAÇÃO -->
    <div id="modalConfirmacao" class="modal">
        <div class="modal-confirm">
            <div class="modal-header">
                <h3><i class="fas fa-question-circle"></i> Confirmar Ação</h3>
                <button class="modal-close" onclick="fecharModalConfirmacao()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-icon" id="modalIcone">
                    <i class="fas fa-exclamation-triangle" style="color: #ffc107; font-size: 3rem;"></i>
                </div>
                <div class="modal-message" id="modalMensagem">
                    Tem certeza que deseja realizar esta ação?
                </div>
                <div class="modal-details" id="modalDetalhes"></div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="fecharModalConfirmacao()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button class="btn-confirm-modal" id="btnConfirmarAcao">
                    <i class="fas fa-check"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
    
    <script>
        let acaoConfirmada = null;
        let idConfirmado = null;
        
        function abrirModalNovoCliente() {
            document.getElementById('modalNovoCliente').classList.add('active');
        }
        
        function fecharModalNovoCliente() {
            document.getElementById('modalNovoCliente').classList.remove('active');
        }
        
        function confirmarDesativar(id, nome) {
            document.getElementById('modalIcone').innerHTML = '<i class="fas fa-ban" style="color: #dc3545; font-size: 3rem;"></i>';
            document.getElementById('modalMensagem').innerHTML = 'Desativar este cliente?';
            document.getElementById('modalDetalhes').innerHTML = '<strong>Cliente:</strong> ' + nome;
            
            const btnConfirmar = document.getElementById('btnConfirmarAcao');
            btnConfirmar.className = 'btn-confirm-modal';
            btnConfirmar.innerHTML = '<i class="fas fa-ban"></i> Sim, desativar';
            
            idConfirmado = id;
            acaoConfirmada = 'desativar';
            document.getElementById('modalConfirmacao').classList.add('active');
        }
        
        function confirmarAtivar(id, nome) {
            document.getElementById('modalIcone').innerHTML = '<i class="fas fa-check-circle" style="color: #28a745; font-size: 3rem;"></i>';
            document.getElementById('modalMensagem').innerHTML = 'Ativar este cliente?';
            document.getElementById('modalDetalhes').innerHTML = '<strong>Cliente:</strong> ' + nome;
            
            const btnConfirmar = document.getElementById('btnConfirmarAcao');
            btnConfirmar.className = 'btn-confirm-modal btn-confirm-success';
            btnConfirmar.innerHTML = '<i class="fas fa-check"></i> Sim, ativar';
            
            idConfirmado = id;
            acaoConfirmada = 'ativar';
            document.getElementById('modalConfirmacao').classList.add('active');
        }
        
        function fecharModalConfirmacao() {
            document.getElementById('modalConfirmacao').classList.remove('active');
            idConfirmado = null;
            acaoConfirmada = null;
        }
        
        function executarAcao() {
            if (acaoConfirmada === 'desativar' && idConfirmado) {
                window.location.href = 'clientes.php?toggle_status=1&id=' + idConfirmado + '&status=ativo';
            } else if (acaoConfirmada === 'ativar' && idConfirmado) {
                window.location.href = 'clientes.php?toggle_status=1&id=' + idConfirmado + '&status=inativo';
            }
        }
        
        document.getElementById('btnConfirmarAcao').onclick = function() {
            executarAcao();
            fecharModalConfirmacao();
        };
        
        // Fechar modais com ESC
        document.addEventListener('keydown', function(event) {
            if(event.key === 'Escape') {
                fecharModalNovoCliente();
                fecharModalConfirmacao();
            }
        });
        
        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modalNovo = document.getElementById('modalNovoCliente');
            const modalConfirm = document.getElementById('modalConfirmacao');
            if (event.target === modalNovo) fecharModalNovoCliente();
            if (event.target === modalConfirm) fecharModalConfirmacao();
        }
    </script>
</body>
</html>