<?php
require_once '../config/auth.php';
Auth::cargo(['admin']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$mensagem = '';
$erro = '';

// Criar viatura
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'criar') {
    $query = "INSERT INTO viaturas (modelo, marca, ano, matricula, preco_dia, tipo, combustivel, transmissao, lugares, descricao, cor, quilometragem, status) 
              VALUES (:modelo, :marca, :ano, :matricula, :preco_dia, :tipo, :combustivel, :transmissao, :lugares, :descricao, :cor, :quilometragem, 'disponivel')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':modelo', $_POST['modelo']);
    $stmt->bindParam(':marca', $_POST['marca']);
    $stmt->bindParam(':ano', $_POST['ano']);
    $stmt->bindParam(':matricula', $_POST['matricula']);
    $stmt->bindParam(':preco_dia', $_POST['preco_dia']);
    $stmt->bindParam(':tipo', $_POST['tipo']);
    $stmt->bindParam(':combustivel', $_POST['combustivel']);
    $stmt->bindParam(':transmissao', $_POST['transmissao']);
    $stmt->bindParam(':lugares', $_POST['lugares']);
    $stmt->bindParam(':descricao', $_POST['descricao']);
    $stmt->bindParam(':cor', $_POST['cor']);
    $stmt->bindParam(':quilometragem', $_POST['quilometragem']);
    
    if($stmt->execute()) {
        $mensagem = '<i class="fas fa-check-circle"></i> Viatura adicionada com sucesso!';
    } else {
        $erro = '<i class="fas fa-exclamation-triangle"></i> Erro ao adicionar viatura';
    }
}

// Editar viatura
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'editar') {
    $query = "UPDATE viaturas SET modelo = :modelo, marca = :marca, ano = :ano, matricula = :matricula, 
              preco_dia = :preco_dia, tipo = :tipo, combustivel = :combustivel, transmissao = :transmissao, 
              lugares = :lugares, descricao = :descricao, cor = :cor, quilometragem = :quilometragem, status = :status 
              WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':modelo', $_POST['modelo']);
    $stmt->bindParam(':marca', $_POST['marca']);
    $stmt->bindParam(':ano', $_POST['ano']);
    $stmt->bindParam(':matricula', $_POST['matricula']);
    $stmt->bindParam(':preco_dia', $_POST['preco_dia']);
    $stmt->bindParam(':tipo', $_POST['tipo']);
    $stmt->bindParam(':combustivel', $_POST['combustivel']);
    $stmt->bindParam(':transmissao', $_POST['transmissao']);
    $stmt->bindParam(':lugares', $_POST['lugares']);
    $stmt->bindParam(':descricao', $_POST['descricao']);
    $stmt->bindParam(':cor', $_POST['cor']);
    $stmt->bindParam(':quilometragem', $_POST['quilometragem']);
    $stmt->bindParam(':status', $_POST['status']);
    $stmt->bindParam(':id', $_POST['id']);
    
    if($stmt->execute()) {
        $mensagem = '<i class="fas fa-check-circle"></i> Viatura atualizada com sucesso!';
    } else {
        $erro = '<i class="fas fa-exclamation-triangle"></i> Erro ao atualizar viatura';
    }
}

// Excluir viatura
if(isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    $query = "DELETE FROM viaturas WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    
    if($stmt->execute()) {
        $mensagem = '<i class="fas fa-check-circle"></i> Viatura excluída com sucesso!';
    } else {
        $erro = '<i class="fas fa-exclamation-triangle"></i> Erro ao excluir viatura';
    }
}

// Buscar todas as viaturas
$query = "SELECT * FROM viaturas ORDER BY criado_em DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$viaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$stats_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'disponivel' THEN 1 ELSE 0 END) as disponiveis,
                SUM(CASE WHEN status = 'alugado' THEN 1 ELSE 0 END) as alugados,
                SUM(CASE WHEN status = 'manutencao' THEN 1 ELSE 0 END) as manutencao
                FROM viaturas";
$stmt = $db->prepare($stats_query);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Viaturas - RentCar</title>
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

        /* Cabeçalho da Página */
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

        /* Cards de Estatísticas */
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
        .stat-icon.danger { background: rgba(220, 53, 69, 0.1); color: #dc3545; }

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

        /* Filtros */
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

        .filter-group select {
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
            border: none;
            outline: none;
            background: transparent;
        }

        /* Tabela */
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

        /* Vehicle Info */
        .vehicle-info {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .vehicle-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .vehicle-details strong {
            display: block;
            color: #1a1a2e;
            font-size: 0.95rem;
        }

        .vehicle-details small {
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

        .badge-disponivel { background: #d4edda; color: #155724; }
        .badge-alugado { background: #fff3cd; color: #856404; }
        .badge-manutencao { background: #f8d7da; color: #721c24; }

        /* Price */
        .price {
            font-weight: 700;
            color: #FF8C00;
            font-size: 1rem;
        }

        .price small {
            font-size: 0.7rem;
            font-weight: normal;
            color: #999;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 0.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 0.8rem;
        }

        .btn-edit { background: rgba(23, 162, 184, 0.1); color: #17a2b8; }
        .btn-edit:hover { background: #17a2b8; color: white; transform: translateY(-2px); }

        .btn-delete { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
        .btn-delete:hover { background: #dc3545; color: white; transform: translateY(-2px); }

        .btn-photos { background: rgba(111, 66, 193, 0.1); color: #6f42c1; }
        .btn-photos:hover { background: #6f42c1; color: white; transform: translateY(-2px); }

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
            max-width: 900px;
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
                <h1>Gestão de Viaturas</h1>
                <p>Gerencie toda a frota de veículos da RentCar</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total de Viaturas</h3>
                        <div class="stat-number"><?= $stats['total'] ?? 0 ?></div>
                        <div class="stat-label">Frota total</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Disponíveis</h3>
                        <div class="stat-number"><?= $stats['disponiveis'] ?? 0 ?></div>
                        <div class="stat-label">Prontas para alugar</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-key"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Alugadas</h3>
                        <div class="stat-number"><?= $stats['alugados'] ?? 0 ?></div>
                        <div class="stat-label">Em circulação</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon danger">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Em Manutenção</h3>
                        <div class="stat-number"><?= $stats['manutencao'] ?? 0 ?></div>
                        <div class="stat-label">Na oficina</div>
                    </div>
                </div>
            </div>
            
            <!-- Main Card -->
            <div class="main-card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-car" style="color: #FF8C00;"></i>
                        Lista de Viaturas
                    </h2>
                    <button class="btn-primary" onclick="abrirModalNovaViatura()">
                        <i class="fas fa-plus"></i>
                        Nova Viatura
                    </button>
                </div>
                
                <div class="filters-bar">
                    <div class="filter-group">
                        <i class="fas fa-filter"></i>
                        <select id="filterStatus" onchange="filtrarTabela()">
                            <option value="todos">Todos os Status</option>
                            <option value="disponivel">Disponível</option>
                            <option value="alugado">Alugado</option>
                            <option value="manutencao">Manutenção</option>
                        </select>
                    </div>
                    <div class="filter-group search-group">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Pesquisar por marca, modelo ou matrícula..." onkeyup="filtrarTabela()">
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="modern-table" id="vehiclesTable">
                        <thead>
                            <tr>
                                <th>Viatura</th>
                                <th>Matrícula</th>
                                <th>Preço/Dia</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <?php foreach($viaturas as $viatura): ?>
                            <tr data-status="<?= $viatura['status'] ?>" data-search="<?= strtolower($viatura['marca'] . ' ' . $viatura['modelo'] . ' ' . $viatura['matricula']) ?>">
                                <td>
                                    <div class="vehicle-info">
                                        <div class="vehicle-icon">
                                            <i class="fas fa-car"></i>
                                        </div>
                                        <div class="vehicle-details">
                                            <strong><?= htmlspecialchars($viatura['marca'] . ' ' . $viatura['modelo']) ?></strong>
                                            <small><?= $viatura['ano'] ?> • <?= $viatura['lugares'] ?> lugares</small>
                                        </div>
                                    </div>
                                </td>
                                <td><i class="fas fa-id-card" style="color: #999;"></i> <?= $viatura['matricula'] ?></td>
                                <td class="price">MZN <?= number_format($viatura['preco_dia'], 2) ?><small>/dia</small></td>
                                <td><i class="fas fa-tag"></i> <?= ucfirst($viatura['tipo']) ?></td>
                                <td>
                                    <?php if($viatura['status'] == 'disponivel'): ?>
                                        <span class="badge badge-disponivel"><i class="fas fa-check-circle"></i> Disponível</span>
                                    <?php elseif($viatura['status'] == 'alugado'): ?>
                                        <span class="badge badge-alugado"><i class="fas fa-key"></i> Alugado</span>
                                    <?php else: ?>
                                        <span class="badge badge-manutencao"><i class="fas fa-tools"></i> Manutenção</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-icon btn-edit" onclick="abrirModalEditarViatura(<?= $viatura['id'] ?>)" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-icon btn-delete" onclick="confirmarExclusaoViatura(<?= $viatura['id'] ?>, '<?= htmlspecialchars($viatura['marca'] . ' ' . $viatura['modelo']) ?>')" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <a href="gerenciar_fotos.php?id=<?= $viatura['id'] ?>" class="btn-icon btn-photos" title="Gerenciar Fotos">
                                            <i class="fas fa-images"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if(count($viaturas) == 0): ?>
                    <div class="empty-state">
                        <i class="fas fa-car"></i>
                        <p>Nenhuma viatura cadastrada ainda</p>
                        <button class="btn-primary" onclick="abrirModalNovaViatura()" style="margin-top: 1rem;">
                            <i class="fas fa-plus"></i> Adicionar Primeira Viatura
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- MODAL MODERNO -->
    <div id="modalViatura" class="modal-modern">
        <div class="modal-content-modern">
            <div class="modal-header-modern">
                <h3 id="modalTitulo">
                    <i class="fas fa-plus-circle"></i>
                    Nova Viatura
                </h3>
                <button class="modal-close" onclick="fecharModalViatura()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="formViatura" method="POST">
                <input type="hidden" name="acao" id="formAcao" value="criar">
                <input type="hidden" name="id" id="viaturaId">
                <div class="modal-body-modern">
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Marca *</label>
                            <input type="text" name="marca" id="marca" class="form-control" placeholder="Ex: Toyota" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-car-side"></i> Modelo *</label>
                            <input type="text" name="modelo" id="modelo" class="form-control" placeholder="Ex: Corolla" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i> Ano *</label>
                            <input type="number" name="ano" id="ano" class="form-control" placeholder="2024" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-id-card"></i> Matrícula *</label>
                            <input type="text" name="matricula" id="matricula" class="form-control" placeholder="AB-12-34" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-money-bill-wave"></i> Preço por dia *</label>
                            <input type="number" step="0.01" name="preco_dia" id="preco_dia" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-palette"></i> Cor</label>
                            <input type="text" name="cor" id="cor" class="form-control" placeholder="Ex: Branco, Preto">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-list"></i> Tipo *</label>
                            <select name="tipo" id="tipo" class="form-control" required>
                                <option value="carro"><i class="fas fa-car"></i> Carro</option>
                                <option value="moto"><i class="fas fa-motorcycle"></i> Moto</option>
                                <option value="van"><i class="fas fa-shuttle-van"></i> Van</option>
                                <option value="luxo"><i class="fas fa-gem"></i> Luxo</option>
                                <option value="economico"><i class="fas fa-coins"></i> Económico</option>
                                <option value="suv"><i class="fas fa-car"></i> SUV</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-gas-pump"></i> Combustível *</label>
                            <select name="combustivel" id="combustivel" class="form-control" required>
                                <option value="gasolina"><i class="fas fa-gas-pump"></i> Gasolina</option>
                                <option value="diesel"><i class="fas fa-oil-can"></i> Diesel</option>
                                <option value="eletrico"><i class="fas fa-bolt"></i> Elétrico</option>
                                <option value="hibrido"><i class="fas fa-exchange-alt"></i> Híbrido</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-cogs"></i> Transmissão *</label>
                            <select name="transmissao" id="transmissao" class="form-control" required>
                                <option value="manual"><i class="fas fa-hand-paper"></i> Manual</option>
                                <option value="automatico"><i class="fas fa-robot"></i> Automático</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-users"></i> Lugares</label>
                            <input type="number" name="lugares" id="lugares" class="form-control" value="5">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-tachometer-alt"></i> Quilometragem</label>
                            <input type="number" name="quilometragem" id="quilometragem" class="form-control" value="0">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-toggle-on"></i> Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="disponivel"><i class="fas fa-check-circle"></i> Disponível</option>
                                <option value="alugado"><i class="fas fa-key"></i> Alugado</option>
                                <option value="manutencao"><i class="fas fa-tools"></i> Manutenção</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label><i class="fas fa-align-left"></i> Descrição</label>
                            <textarea name="descricao" id="descricao" class="form-control" placeholder="Descrição detalhada da viatura..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer-modern">
                    <button type="button" class="btn-secondary" onclick="fecharModalViatura()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn-success" onclick="confirmarSalvarViatura()">
                        <i class="fas fa-save"></i> Guardar
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
                <p id="confirmacaoMensagem" style="margin-bottom: 0.5rem;">Tem certeza que deseja realizar esta ação?</p>
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
        
        function abrirModalNovaViatura() {
            document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-plus-circle"></i> Nova Viatura';
            document.getElementById('formAcao').value = 'criar';
            document.getElementById('formViatura').reset();
            document.getElementById('viaturaId').value = '';
            document.getElementById('modalViatura').classList.add('active');
        }
        
        async function abrirModalEditarViatura(id) {
            try {
                const response = await fetch(`../api/viaturas.php?id=${id}`);
                const resultado = await response.json();
                if(resultado && resultado.sucesso) {
                    const v = resultado.dados;
                    document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-edit"></i> Editar Viatura';
                    document.getElementById('formAcao').value = 'editar';
                    document.getElementById('viaturaId').value = v.id;
                    document.getElementById('marca').value = v.marca;
                    document.getElementById('modelo').value = v.modelo;
                    document.getElementById('ano').value = v.ano;
                    document.getElementById('matricula').value = v.matricula;
                    document.getElementById('preco_dia').value = v.preco_dia;
                    document.getElementById('tipo').value = v.tipo;
                    document.getElementById('combustivel').value = v.combustivel;
                    document.getElementById('transmissao').value = v.transmissao;
                    document.getElementById('lugares').value = v.lugares;
                    document.getElementById('status').value = v.status;
                    document.getElementById('descricao').value = v.descricao || '';
                    document.getElementById('cor').value = v.cor || '';
                    document.getElementById('quilometragem').value = v.quilometragem || 0;
                    document.getElementById('modalViatura').classList.add('active');
                }
            } catch(error) {
                window.location.href = `editar_viatura.php?id=${id}`;
            }
        }
        
        function confirmarSalvarViatura() {
            const acao = document.getElementById('formAcao').value;
            const nome = document.getElementById('marca').value + ' ' + document.getElementById('modelo').value;
            
            const campos = ['marca', 'modelo', 'ano', 'matricula', 'preco_dia'];
            let valido = true;
            campos.forEach(campo => {
                const input = document.getElementById(campo);
                if(!input.value.trim()) {
                    input.style.borderColor = '#dc3545';
                    valido = false;
                } else {
                    input.style.borderColor = '#e0e0e0';
                }
            });
            
            if(!valido) return;
            
            abrirModalConfirmacao(
                acao === 'criar' ? 'Tem certeza que deseja adicionar esta viatura?' : 'Tem certeza que deseja salvar as alterações?',
                '<i class="fas fa-car"></i> ' + nome,
                () => { document.getElementById('formViatura').submit(); }
            );
        }
        
        function confirmarExclusaoViatura(id, nome) {
            abrirModalConfirmacao(
                'Tem certeza que deseja excluir esta viatura?',
                '<i class="fas fa-car"></i> ' + nome,
                () => { window.location.href = `?excluir=${id}`; }
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
        
        function fecharModalViatura() {
            document.getElementById('modalViatura').classList.remove('active');
        }
        
        function fecharModalConfirmacao() {
            document.getElementById('modalConfirmacao').classList.remove('active');
            window.acaoConfirmada = null;
        }
        
        function filtrarTabela() {
            const status = document.getElementById('filterStatus').value;
            const search = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#tableBody tr');
            
            rows.forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                const rowSearch = row.getAttribute('data-search');
                
                const statusMatch = status === 'todos' || rowStatus === status;
                const searchMatch = search === '' || rowSearch.includes(search);
                
                row.style.display = statusMatch && searchMatch ? '' : 'none';
            });
        }
        
        // Close modal on ESC key
        document.addEventListener('keydown', function(event) {
            if(event.key === 'Escape') {
                fecharModalViatura();
                fecharModalConfirmacao();
            }
        });
        
        // Close modal on outside click
        window.onclick = function(event) {
            const modalViatura = document.getElementById('modalViatura');
            const modalConfirmacao = document.getElementById('modalConfirmacao');
            if(event.target === modalViatura) fecharModalViatura();
            if(event.target === modalConfirmacao) fecharModalConfirmacao();
        }
    </script>
    
    <style>
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    </style>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>