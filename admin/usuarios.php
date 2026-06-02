<?php
require_once '../config/auth.php';
Auth::cargo(['admin']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$mensagem = '';
$erro = '';

// Processar ações
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    
    if($acao == 'criar') {
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $senha = password_hash('123456', PASSWORD_DEFAULT);
        $cargo = $_POST['cargo'];
        $telefone = $_POST['telefone'] ?? '';
        $morada = $_POST['morada'] ?? '';
        $nif = $_POST['nif'] ?? '';
        
        $check = $db->prepare("SELECT id FROM utilizadores WHERE email = :email");
        $check->bindParam(':email', $email);
        $check->execute();
        
        if($check->rowCount() > 0) {
            $erro = '<i class="fas fa-exclamation-triangle"></i> Email já registado no sistema!';
        } else {
            $query = "INSERT INTO utilizadores (nome, email, senha, cargo, telefone, morada, nif, status) 
                      VALUES (:nome, :email, :senha, :cargo, :telefone, :morada, :nif, 'ativo')";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':senha', $senha);
            $stmt->bindParam(':cargo', $cargo);
            $stmt->bindParam(':telefone', $telefone);
            $stmt->bindParam(':morada', $morada);
            $stmt->bindParam(':nif', $nif);
            
            if($stmt->execute()) {
                $mensagem = '<i class="fas fa-check-circle"></i> Utilizador criado com sucesso! Senha padrão: <strong>123456</strong>';
            } else {
                $erro = '<i class="fas fa-exclamation-triangle"></i> Erro ao criar utilizador';
            }
        }
    }
    
    if($acao == 'alterar_status') {
        $id = $_POST['id'];
        $status = $_POST['status'];
        
        $query = "UPDATE utilizadores SET status = :status WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        
        if($stmt->execute()) {
            $mensagem = '<i class="fas fa-check-circle"></i> Status atualizado com sucesso!';
        } else {
            $erro = '<i class="fas fa-exclamation-triangle"></i> Erro ao atualizar status';
        }
    }
    
    if($acao == 'resetar_senha') {
        $id = $_POST['id'];
        $nova_senha = password_hash('123456', PASSWORD_DEFAULT);
        
        $query = "UPDATE utilizadores SET senha = :senha WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':senha', $nova_senha);
        $stmt->bindParam(':id', $id);
        
        if($stmt->execute()) {
            $mensagem = '<i class="fas fa-check-circle"></i> Senha resetada para: <strong>123456</strong>';
        } else {
            $erro = '<i class="fas fa-exclamation-triangle"></i> Erro ao resetar senha';
        }
    }
}

// Estatísticas
$stats_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN cargo = 'admin' THEN 1 ELSE 0 END) as admins,
                SUM(CASE WHEN cargo = 'funcionario' THEN 1 ELSE 0 END) as funcionarios,
                SUM(CASE WHEN cargo = 'cliente' THEN 1 ELSE 0 END) as clientes,
                SUM(CASE WHEN status = 'ativo' THEN 1 ELSE 0 END) as ativos,
                SUM(CASE WHEN status = 'inativo' THEN 1 ELSE 0 END) as inativos
                FROM utilizadores";
$stmt = $db->prepare($stats_query);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$query = "SELECT * FROM utilizadores ORDER BY criado_em DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$utilizadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Utilizadores - RentCar</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        }

        .conteudo-principal {
            padding: 2rem;
            background: #f5f7fb;
            min-height: 100vh;
        }

        /* Page Header */
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

        /* Statistics Cards */
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
        .stat-icon.warning { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .stat-icon.info { background: rgba(23, 162, 184, 0.1); color: #17a2b8; }

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

        /* Main Card */
        .main-card {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .card-header {
            padding: 1.5rem 2rem;
            background: white;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .card-header h2 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1a1a2e;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FF8C00, #FF6B00);
            color: white;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 0.8rem;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 140, 0, 0.3);
        }

        /* Filters */
        .filters-bar {
            padding: 1rem 2rem;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            padding: 0.4rem 1rem;
            border-radius: 0.8rem;
            border: 1px solid #ddd;
        }

        .filter-group i {
            color: #999;
        }

        .filter-group select, .filter-group input {
            border: none;
            padding: 0.4rem 0;
            background: transparent;
            font-size: 0.85rem;
            outline: none;
        }

        .search-group {
            flex: 1;
            max-width: 300px;
        }

        .search-group input {
            width: 100%;
        }

        /* Table */
        .table-container {
            overflow-x: auto;
            padding: 0 2rem 2rem 2rem;
        }

        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 0.75rem;
        }

        .modern-table thead th {
            padding: 0.8rem 1rem;
            text-align: left;
            font-size: 0.8rem;
            font-weight: 600;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .modern-table tbody tr {
            background: white;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .modern-table tbody tr:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .modern-table tbody td {
            padding: 1rem;
            border-top: 1px solid #f0f0f0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.9rem;
        }

        .modern-table tbody td:first-child {
            border-left: 1px solid #f0f0f0;
            border-radius: 1rem 0 0 1rem;
        }

        .modern-table tbody td:last-child {
            border-right: 1px solid #f0f0f0;
            border-radius: 0 1rem 1rem 0;
        }

        /* User Info */
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .user-details strong {
            display: block;
            color: #1a1a2e;
            font-size: 0.95rem;
        }

        .user-details small {
            color: #999;
            font-size: 0.75rem;
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.3rem 0.8rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-ativo { background: #d4edda; color: #155724; }
        .badge-inativo { background: #f8d7da; color: #721c24; }
        .badge-admin { background: linear-gradient(135deg, #dc3545, #c82333); color: white; }
        .badge-funcionario { background: linear-gradient(135deg, #17a2b8, #138496); color: white; }
        .badge-cliente { background: linear-gradient(135deg, #28a745, #20c997); color: white; }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-icon {
            width: 34px;
            height: 34px;
            border-radius: 0.6rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }

        .btn-reset { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .btn-reset:hover { background: #ffc107; color: white; transform: translateY(-2px); }

        .btn-bloquear { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
        .btn-bloquear:hover { background: #dc3545; color: white; transform: translateY(-2px); }

        .btn-ativar { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .btn-ativar:hover { background: #28a745; color: white; transform: translateY(-2px); }

        .btn-principal {
            background: rgba(255, 140, 0, 0.1);
            color: #FF8C00;
            padding: 0.3rem 0.8rem;
            border-radius: 1rem;
            font-size: 0.7rem;
            font-weight: 600;
        }

        /* Modal Moderno */
        .modal-modern {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-modern.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content-modern {
            background: white;
            border-radius: 1.5rem;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header-modern {
            padding: 1.5rem 2rem;
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
        }

        .modal-header-modern h3 {
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-close {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
        }

        .modal-body-modern {
            padding: 2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .form-group label i {
            color: #FF8C00;
            width: 18px;
        }

        .form-control {
            padding: 0.75rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 0.8rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #FF8C00;
            box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.1);
        }

        select.form-control {
            cursor: pointer;
            background: white;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        .alert-info {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            padding: 1rem;
            border-radius: 1rem;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-size: 0.85rem;
        }

        .alert-info i {
            font-size: 1.2rem;
            color: #17a2b8;
        }

        .modal-footer-modern {
            padding: 1rem 2rem 1.5rem;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            border-top: 1px solid #eee;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 0.8rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 0.8rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        /* Toast Notification */
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
        .toast-error { border-left: 4px solid #dc3545; }
        .toast-error i { color: #dc3545; }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem;
            color: #999;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .conteudo-principal {
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
            
            .card-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .action-buttons {
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
            
            <!-- Toast Notifications -->
            <?php if($mensagem): ?>
            <div class="toast-notification toast-success" id="toast">
                <i class="fas fa-check-circle fa-lg"></i>
                <span><?= $mensagem ?></span>
            </div>
            <?php endif; ?>
            
            <?php if($erro): ?>
            <div class="toast-notification toast-error" id="toast">
                <i class="fas fa-exclamation-circle fa-lg"></i>
                <span><?= $erro ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Page Header -->
            <div class="page-header">
                <h1>Gestão de Utilizadores</h1>
                <p>Gerencie todos os utilizadores do sistema RentCar</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total de Utilizadores</h3>
                        <div class="stat-number"><?= $stats['total'] ?? 0 ?></div>
                        <div class="stat-label">Registados no sistema</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Ativos</h3>
                        <div class="stat-number"><?= $stats['ativos'] ?? 0 ?></div>
                        <div class="stat-label">Contas ativas</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Inativos</h3>
                        <div class="stat-number"><?= $stats['inativos'] ?? 0 ?></div>
                        <div class="stat-label">Contas bloqueadas</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Funcionários</h3>
                        <div class="stat-number"><?= $stats['funcionarios'] ?? 0 ?></div>
                        <div class="stat-label">Equipa RentCar</div>
                    </div>
                </div>
            </div>
            
            <!-- Main Card -->
            <div class="main-card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-users" style="color: #FF8C00;"></i>
                        Lista de Utilizadores
                    </h2>
                    <button class="btn-primary" onclick="abrirModalNovoUtilizador()">
                        <i class="fas fa-plus"></i>
                        Novo Utilizador
                    </button>
                </div>
                
                <div class="filters-bar">
                    <div class="filter-group">
                        <i class="fas fa-filter"></i>
                        <select id="filterCargo" onchange="filtrarTabela()">
                            <option value="todos">Todos os Cargos</option>
                            <option value="admin">Administradores</option>
                            <option value="funcionario">Funcionários</option>
                            <option value="cliente">Clientes</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <i class="fas fa-flag-checkered"></i>
                        <select id="filterStatus" onchange="filtrarTabela()">
                            <option value="todos">Todos os Status</option>
                            <option value="ativo">Ativos</option>
                            <option value="inativo">Inativos</option>
                        </select>
                    </div>
                    <div class="filter-group search-group">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Pesquisar por nome ou email..." onkeyup="filtrarTabela()">
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="modern-table" id="usersTable">
                        <thead>
                            <tr>
                                <th>Utilizador</th>
                                <th>Contacto</th>
                                <th>Cargo</th>
                                <th>Status</th>
                                <th>Registo</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <?php foreach($utilizadores as $user): ?>
                            <tr data-cargo="<?= $user['cargo'] ?>" data-status="<?= $user['status'] ?>" data-search="<?= strtolower($user['nome'] . ' ' . $user['email']) ?>">
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="user-details">
                                            <strong><?= htmlspecialchars($user['nome']) ?></strong>
                                            <small><i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if($user['telefone']): ?>
                                        <div><i class="fas fa-phone"></i> <?= htmlspecialchars($user['telefone']) ?></div>
                                    <?php endif; ?>
                                    <?php if($user['nif']): ?>
                                        <small><i class="fas fa-id-card"></i> <?= htmlspecialchars($user['nif']) ?></small>
                                    <?php endif; ?>
                                    <?php if(!$user['telefone'] && !$user['nif']): ?>
                                        <span style="color: #ccc;">---</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($user['cargo'] == 'admin'): ?>
                                        <span class="badge badge-admin"><i class="fas fa-crown"></i> Administrador</span>
                                    <?php elseif($user['cargo'] == 'funcionario'): ?>
                                        <span class="badge badge-funcionario"><i class="fas fa-user-tie"></i> Funcionário</span>
                                    <?php else: ?>
                                        <span class="badge badge-cliente"><i class="fas fa-user"></i> Cliente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($user['status'] == 'ativo'): ?>
                                        <span class="badge badge-ativo"><i class="fas fa-check-circle"></i> Ativo</span>
                                    <?php else: ?>
                                        <span class="badge badge-inativo"><i class="fas fa-ban"></i> Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($user['criado_em'])) ?></small>
                                    <?php if($user['ultimo_acesso']): ?>
                                        <br><small><i class="fas fa-clock"></i> <?= date('d/m/Y', strtotime($user['ultimo_acesso'])) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if($user['id'] != 1): ?>
                                            <button class="btn-icon btn-reset" onclick="confirmarResetSenha(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nome']) ?>')" title="Resetar Senha">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <?php if($user['status'] == 'ativo'): ?>
                                                <button class="btn-icon btn-bloquear" onclick="confirmarAlterarStatus(<?= $user['id'] ?>, '<?= $user['status'] ?>', '<?= htmlspecialchars($user['nome']) ?>')" title="Bloquear">
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn-icon btn-ativar" onclick="confirmarAlterarStatus(<?= $user['id'] ?>, '<?= $user['status'] ?>', '<?= htmlspecialchars($user['nome']) ?>')" title="Ativar">
                                                    <i class="fas fa-unlock-alt"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="btn-principal"><i class="fas fa-star"></i> Principal</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if(count($utilizadores) == 0): ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <p>Nenhum utilizador cadastrado ainda</p>
                        <button class="btn-primary" onclick="abrirModalNovoUtilizador()" style="margin-top: 1rem;">
                            <i class="fas fa-plus"></i> Adicionar Primeiro Utilizador
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- MODAL NOVO UTILIZADOR -->
    <div id="modalUtilizador" class="modal-modern">
        <div class="modal-content-modern">
            <div class="modal-header-modern">
                <h3>
                    <i class="fas fa-user-plus"></i>
                    Novo Utilizador
                </h3>
                <button class="modal-close" onclick="fecharModalUtilizador()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" id="formUtilizador">
                <input type="hidden" name="acao" value="criar">
                <div class="modal-body-modern">
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Nome Completo *</label>
                            <input type="text" name="nome" id="nomeUtilizador" class="form-control" placeholder="Ex: João Silva" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email *</label>
                            <input type="email" name="email" id="emailUtilizador" class="form-control" placeholder="exemplo@email.com" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Telefone</label>
                            <input type="tel" name="telefone" id="telefoneUtilizador" class="form-control" placeholder="+258 84 123 4567">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-id-card"></i> NUIT</label>
                            <input type="text" name="nif" id="nifUtilizador" class="form-control" placeholder="Número de identificação fiscal">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-briefcase"></i> Cargo *</label>
                            <select name="cargo" id="cargoUtilizador" class="form-control" required>
                                <option value="cliente"><i class="fas fa-user"></i> Cliente</option>
                                <option value="funcionario"><i class="fas fa-user-tie"></i> Funcionário</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label><i class="fas fa-map-marker-alt"></i> Morada</label>
                            <textarea name="morada" id="moradaUtilizador" class="form-control" placeholder="Morada completa..."></textarea>
                        </div>
                    </div>
                    <div class="alert-info">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <strong>Informação importante:</strong><br>
                            A senha padrão será: <strong>******</strong><br>
                            <small>O utilizador deverá alterar a senha no primeiro acesso.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer-modern">
                    <button type="button" class="btn-secondary" onclick="fecharModalUtilizador()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn-success" onclick="confirmarCriarUtilizador()">
                        <i class="fas fa-save"></i> Criar Utilizador
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- MODAL CONFIRMAÇÃO -->
    <div id="modalConfirmacao" class="modal-modern">
        <div class="modal-content-modern" style="max-width: 450px;">
            <div class="modal-header-modern" style="background: linear-gradient(135deg, #dc3545, #c82333);">
                <h3>
                    <i class="fas fa-exclamation-triangle"></i>
                    Confirmar Ação
                </h3>
                <button class="modal-close" onclick="fecharModalConfirmacao()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body-modern" style="text-align: center;">
                <i class="fas fa-question-circle" style="font-size: 3rem; color: #ffc107; margin-bottom: 1rem;"></i>
                <p id="confirmacaoMensagem" style="margin-bottom: 0.5rem; font-size: 1rem;">Tem certeza que deseja realizar esta ação?</p>
                <small id="confirmacaoDetalhes" style="color: #999;"></small>
            </div>
            <div class="modal-footer-modern">
                <button class="btn-secondary" onclick="fecharModalConfirmacao()">Cancelar</button>
                <button class="btn-success" id="btnConfirmarAcao" style="background: linear-gradient(135deg, #dc3545, #c82333);">Confirmar</button>
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
        
        function abrirModalNovoUtilizador() {
            document.getElementById('modalUtilizador').classList.add('active');
            document.getElementById('formUtilizador').reset();
        }
        
        function fecharModalUtilizador() {
            document.getElementById('modalUtilizador').classList.remove('active');
        }
        
        function confirmarCriarUtilizador() {
            const nome = document.getElementById('nomeUtilizador').value;
            const email = document.getElementById('emailUtilizador').value;
            
            if(!nome || !email) {
                alert('Por favor, preencha nome e email!');
                return;
            }
            
            abrirModalConfirmacao(
                'Tem certeza que deseja criar este utilizador?',
                '<i class="fas fa-user"></i> ' + nome + '<br><i class="fas fa-envelope"></i> ' + email,
                () => { document.getElementById('formUtilizador').submit(); }
            );
        }
        
        function confirmarAlterarStatus(id, statusAtual, nome) {
            const novoStatus = statusAtual === 'ativo' ? 'inativo' : 'ativo';
            const acaoTexto = novoStatus === 'ativo' ? 'ativar' : 'bloquear';
            const acaoIcon = novoStatus === 'ativo' ? '<i class="fas fa-unlock-alt"></i>' : '<i class="fas fa-lock"></i>';
            
            abrirModalConfirmacao(
                'Tem certeza que deseja ' + acaoTexto + ' este utilizador?',
                acaoIcon + ' ' + nome,
                () => {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="acao" value="alterar_status">
                        <input type="hidden" name="id" value="${id}">
                        <input type="hidden" name="status" value="${novoStatus}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            );
        }
        
        function confirmarResetSenha(id, nome) {
            abrirModalConfirmacao(
                'Tem certeza que deseja resetar a senha para 123456?',
                '<i class="fas fa-user"></i> ' + nome,
                () => {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="acao" value="resetar_senha">
                        <input type="hidden" name="id" value="${id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            );
        }
        
        function abrirModalConfirmacao(mensagem, detalhes, onConfirmar) {
            const modal = document.getElementById('modalConfirmacao');
            const mensagemEl = document.getElementById('confirmacaoMensagem');
            const detalhesEl = document.getElementById('confirmacaoDetalhes');
            const btnConfirmar = document.getElementById('btnConfirmarAcao');
            
            mensagemEl.innerHTML = mensagem;
            detalhesEl.innerHTML = detalhes || '';
            
            window.acaoConfirmada = onConfirmar;
            
            const novoBtn = btnConfirmar.cloneNode(true);
            btnConfirmar.parentNode.replaceChild(novoBtn, btnConfirmar);
            novoBtn.addEventListener('click', () => {
                if(window.acaoConfirmada) window.acaoConfirmada();
                fecharModalConfirmacao();
            });
            
            modal.classList.add('active');
        }
        
        function fecharModalConfirmacao() {
            document.getElementById('modalConfirmacao').classList.remove('active');
            window.acaoConfirmada = null;
        }
        
        function filtrarTabela() {
            const cargo = document.getElementById('filterCargo').value;
            const status = document.getElementById('filterStatus').value;
            const search = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#tableBody tr');
            
            rows.forEach(row => {
                const rowCargo = row.getAttribute('data-cargo');
                const rowStatus = row.getAttribute('data-status');
                const rowSearch = row.getAttribute('data-search');
                
                const cargoMatch = cargo === 'todos' || rowCargo === cargo;
                const statusMatch = status === 'todos' || rowStatus === status;
                const searchMatch = search === '' || rowSearch.includes(search);
                
                row.style.display = cargoMatch && statusMatch && searchMatch ? '' : 'none';
            });
        }
        
        // Close modal on ESC key
        document.addEventListener('keydown', function(event) {
            if(event.key === 'Escape') {
                fecharModalUtilizador();
                fecharModalConfirmacao();
            }
        });
        
        // Close modal on outside click
        window.onclick = function(event) {
            const modalUtilizador = document.getElementById('modalUtilizador');
            const modalConfirmacao = document.getElementById('modalConfirmacao');
            if(event.target === modalUtilizador) fecharModalUtilizador();
            if(event.target === modalConfirmacao) fecharModalConfirmacao();
        }
    </script>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>