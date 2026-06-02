<?php
require_once '../config/auth.php';
Auth::cargo(['admin']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// ============================================
// FILTROS
// ============================================
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-t');
$metodo_filtro = $_GET['metodo'] ?? 'todos';
$status_filtro = $_GET['status'] ?? 'todos';

// ============================================
// ESTATÍSTICAS RÁPIDAS DE PAGAMENTOS
// ============================================
$query = "SELECT 
          COALESCE(SUM(CASE WHEN estado = 'confirmado' THEN valor ELSE 0 END), 0) as total_pago,
          COUNT(CASE WHEN estado = 'confirmado' THEN 1 END) as total_transacoes,
          COALESCE(SUM(CASE WHEN estado = 'pendente' THEN valor ELSE 0 END), 0) as total_pendente,
          COUNT(CASE WHEN estado = 'pendente' THEN 1 END) as transacoes_pendentes
          FROM pagamentos";
$stmt = $db->prepare($query);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Calcular taxa de conversão
$total = ($stats['total_transacoes'] ?? 0) + ($stats['transacoes_pendentes'] ?? 0);
$taxa_conversao = $total > 0 ? round(($stats['total_transacoes'] / $total) * 100, 1) : 0;

// ============================================
// PAGAMENTOS COM FILTROS
// ============================================
$sql = "SELECT p.*, u.nome as cliente_nome, u.email as cliente_email
        FROM pagamentos p
        JOIN utilizadores u ON p.utilizador_id = u.id
        WHERE DATE(p.data_criacao) BETWEEN :inicio AND :fim";

if($metodo_filtro != 'todos') {
    $sql .= " AND p.metodo_pagamento = :metodo";
}
if($status_filtro != 'todos') {
    $sql .= " AND p.estado = :status";
}

$sql .= " ORDER BY p.data_criacao DESC";

$stmt = $db->prepare($sql);
$stmt->bindParam(':inicio', $data_inicio);
$stmt->bindParam(':fim', $data_fim);
if($metodo_filtro != 'todos') {
    $stmt->bindParam(':metodo', $metodo_filtro);
}
if($status_filtro != 'todos') {
    $stmt->bindParam(':status', $status_filtro);
}
$stmt->execute();
$pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Pagamentos - RentCar</title>
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
            overflow-x: hidden;
        }

        /* Layout Principal - CORRIGIDO */
        .container-app {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* Barra Lateral Fixa */
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

        /* Conteúdo Principal com margem da barra lateral */
        .conteudo-principal {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            background: #f5f7fb;
            min-height: 100vh;
            width: calc(100% - 280px);
            overflow-x: auto;
        }

        /* Cabeçalho */
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

        .stat-icon.success { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .stat-icon.warning { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .stat-icon.info { background: rgba(23, 162, 184, 0.1); color: #17a2b8; }
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

        /* Filters Bar */
        .filters-bar {
            background: white;
            border-radius: 1rem;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .date-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #f8f9fa;
            padding: 0.5rem 1rem;
            border-radius: 0.8rem;
        }

        .date-group i {
            color: #FF8C00;
        }

        .date-group input, .filter-group select {
            border: none;
            padding: 0.4rem;
            background: transparent;
            font-size: 0.9rem;
            outline: none;
        }

        .btn-filter {
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
        }

        .btn-filter:hover {
            background: #e67e00;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
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
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-success {
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
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        /* Table Card */
        .table-card {
            background: white;
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .table-header {
            padding: 1.2rem 1.5rem;
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .table-header h3 {
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .badge-count {
            background: rgba(255,255,255,0.2);
            padding: 0.2rem 0.6rem;
            border-radius: 2rem;
            font-size: 0.7rem;
        }

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

        .badge-confirmado { background: #d4edda; color: #155724; }
        .badge-pendente { background: #fff3cd; color: #856404; }
        .badge-falhou { background: #f8d7da; color: #721c24; }

        /* Action Buttons */
        .btn-icon {
            background: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
            border: none;
            padding: 0.3rem 0.7rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .btn-icon:hover {
            background: #17a2b8;
            color: white;
            transform: translateY(-2px);
        }

        /* Price */
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

        /* Responsive - CORRIGIDO */
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
            
            .filters-bar {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
                justify-content: center;
            }
            
            .table-container {
                overflow-x: scroll;
            }
        }

        @media print {
            .filters-bar, .btn-filter, .btn-secondary, .btn-success, .barra-lateral, .barra-superior {
                display: none;
            }
            .conteudo-principal {
                margin-left: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <!-- Page Header -->
            <div class="page-header">
                <h1>Gestão de Pagamentos</h1>
                <p>Visualize e gerencie todos os pagamentos do sistema</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Pagamentos Confirmados</h3>
                        <div class="stat-number"><?= number_format($stats['total_transacoes'] ?? 0) ?></div>
                        <div class="stat-label">Transações concluídas</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Valor Pendente</h3>
                        <div class="stat-number">MZN <?= number_format($stats['total_pendente'] ?? 0, 2) ?></div>
                        <div class="stat-label"><?= $stats['transacoes_pendentes'] ?? 0 ?> transações</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Taxa de Conversão</h3>
                        <div class="stat-number"><?= $taxa_conversao ?>%</div>
                        <div class="stat-label">Pagamentos confirmados</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Pago</h3>
                        <div class="stat-number">MZN <?= number_format($stats['total_pago'] ?? 0, 2) ?></div>
                        <div class="stat-label">Valor total confirmado</div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filters-bar">
                <div class="filter-group">
                    <div class="date-group">
                        <i class="fas fa-calendar-alt"></i>
                        <input type="date" id="data_inicio" value="<?= $data_inicio ?>">
                    </div>
                    <span>até</span>
                    <div class="date-group">
                        <i class="fas fa-calendar-check"></i>
                        <input type="date" id="data_fim" value="<?= $data_fim ?>">
                    </div>
                    <select id="metodo_filtro">
                        <option value="todos" <?= $metodo_filtro == 'todos' ? 'selected' : '' ?>>Todos os Métodos</option>
                        <option value="dinheiro" <?= $metodo_filtro == 'dinheiro' ? 'selected' : '' ?>>Dinheiro</option>
                        <option value="cartao_credito" <?= $metodo_filtro == 'cartao_credito' ? 'selected' : '' ?>>Cartão Crédito</option>
                        <option value="cartao_debito" <?= $metodo_filtro == 'cartao_debito' ? 'selected' : '' ?>>Cartão Débito</option>
                        <option value="transferencia" <?= $metodo_filtro == 'transferencia' ? 'selected' : '' ?>>Transferência</option>
                        <option value="mbway" <?= $metodo_filtro == 'mbway' ? 'selected' : '' ?>>MB WAY</option>
                    </select>
                    <select id="status_filtro">
                        <option value="todos" <?= $status_filtro == 'todos' ? 'selected' : '' ?>>Todos os Status</option>
                        <option value="confirmado" <?= $status_filtro == 'confirmado' ? 'selected' : '' ?>>Confirmados</option>
                        <option value="pendente" <?= $status_filtro == 'pendente' ? 'selected' : '' ?>>Pendentes</option>
                        <option value="falhou" <?= $status_filtro == 'falhou' ? 'selected' : '' ?>>Falhados</option>
                    </select>
                    <button class="btn-filter" onclick="aplicarFiltro()">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <button class="btn-secondary" onclick="limparFiltro()">
                        <i class="fas fa-eraser"></i> Limpar
                    </button>
                </div>
                <div class="filter-group">
                    <button class="btn-success" onclick="exportarExcel()">
                        <i class="fas fa-file-excel"></i> Exportar
                    </button>
                </div>
            </div>
            
            <!-- Payments Table -->
            <div class="table-card">
                <div class="table-header">
                    <h3><i class="fas fa-list"></i> Lista de Pagamentos</h3>
                    <span class="badge-count"><?= count($pagamentos) ?> registos</span>
                </div>
                <div class="table-container">
                    <?php if(count($pagamentos) > 0): ?>
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Valor</th>
                                <th>Método</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th>Recibo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pagamentos as $p): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($p['cliente_nome']) ?></strong><br>
                                        <small style="color: #999;"><?= $p['cliente_email'] ?></small>
                                    </div>
                                </td>
                                <td class="price">MZN <?= number_format($p['valor'], 2) ?></td>
                                <td>
                                    <?php
                                    $metodo_icon = '';
                                    switch($p['metodo_pagamento']) {
                                        case 'dinheiro': $metodo_icon = 'fa-money-bill-wave'; break;
                                        case 'cartao_credito': $metodo_icon = 'fa-credit-card'; break;
                                        case 'cartao_debito': $metodo_icon = 'fa-credit-card'; break;
                                        case 'transferencia': $metodo_icon = 'fa-university'; break;
                                        case 'mbway': $metodo_icon = 'fa-mobile-alt'; break;
                                        default: $metodo_icon = 'fa-tag';
                                    }
                                    ?>
                                    <i class="fas <?= $metodo_icon ?>"></i>
                                    <?= ucfirst(str_replace('_', ' ', $p['metodo_pagamento'])) ?>
                                 </td>
                                <td>
                                    <small><?= date('d/m/Y', strtotime($p['data_criacao'])) ?></small><br>
                                    <small style="color: #999;"><?= date('H:i', strtotime($p['data_criacao'])) ?></small>
                                 </td>
                                <td>
                                    <span class="badge badge-<?= $p['estado'] ?>">
                                        <?php if($p['estado'] == 'confirmado'): ?>
                                            <i class="fas fa-check-circle"></i> Confirmado
                                        <?php elseif($p['estado'] == 'pendente'): ?>
                                            <i class="fas fa-clock"></i> Pendente
                                        <?php else: ?>
                                            <i class="fas fa-times-circle"></i> Falhou
                                        <?php endif; ?>
                                    </span>
                                 </td>
                                <td>
                                    <button class="btn-icon" onclick="emitirRecibo(<?= $p['id'] ?>)">
                                        <i class="fas fa-receipt"></i> Ver
                                    </button>
                                 </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>Nenhum pagamento encontrado</p>
                        <small>Altere os filtros para ver mais resultados</small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function aplicarFiltro() {
            const inicio = document.getElementById('data_inicio').value;
            const fim = document.getElementById('data_fim').value;
            const metodo = document.getElementById('metodo_filtro').value;
            const status = document.getElementById('status_filtro').value;
            window.location.href = `pagamentos.php?data_inicio=${inicio}&data_fim=${fim}&metodo=${metodo}&status=${status}`;
        }
        
        function limparFiltro() {
            window.location.href = 'pagamentos.php';
        }
        
        function exportarExcel() {
            const inicio = document.getElementById('data_inicio').value;
            const fim = document.getElementById('data_fim').value;
            const metodo = document.getElementById('metodo_filtro').value;
            const status = document.getElementById('status_filtro').value;
            window.location.href = `../api/pagamentos.php?acao=exportar_excel&data_inicio=${inicio}&data_fim=${fim}&metodo=${metodo}&status=${status}`;
        }
        
        function emitirRecibo(id) {
            window.open(`../pagamentos/recibo.php?id=${id}`, '_blank');
        }
    </script>
</body>
</html>