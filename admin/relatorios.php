<?php
require_once '../config/auth.php';
Auth::cargo(['admin']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// ============================================
// PARÂMETROS DE FILTRO
// ============================================
$data_inicio = $_GET['inicio'] ?? date('Y-m-01');
$data_fim = $_GET['fim'] ?? date('Y-m-t');
$tipo_relatorio = $_GET['tipo'] ?? 'geral';

// ============================================
// RECEITA MENSAL (ÚLTIMOS 12 MESES)
// ============================================
$query = "SELECT 
          DATE_FORMAT(data_pagamento, '%b/%Y') as mes,
          DATE_FORMAT(data_pagamento, '%Y-%m') as mes_ordenar,
          COALESCE(SUM(valor), 0) as total
          FROM pagamentos
          WHERE estado = 'confirmado' AND data_pagamento IS NOT NULL
          GROUP BY DATE_FORMAT(data_pagamento, '%Y-%m')
          ORDER BY mes_ordenar ASC
          LIMIT 12";
$stmt = $db->prepare($query);
$stmt->execute();
$receita_mensal = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// RECEITA POR PERÍODO SELECIONADO
// ============================================
$query = "SELECT 
          COALESCE(SUM(valor), 0) as total_periodo,
          COUNT(*) as total_transacoes_periodo,
          COALESCE(AVG(valor), 0) as ticket_medio_periodo
          FROM pagamentos
          WHERE estado = 'confirmado' 
          AND DATE(data_pagamento) BETWEEN :inicio AND :fim";
$stmt = $db->prepare($query);
$stmt->bindParam(':inicio', $data_inicio);
$stmt->bindParam(':fim', $data_fim);
$stmt->execute();
$periodo_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// ============================================
// TOP VIATURAS MAIS ALUGADAS
// ============================================
$query = "SELECT v.marca, v.modelo, v.matricula, COUNT(a.id) as total_alugueis, 
          COALESCE(SUM(a.preco_total), 0) as receita,
          v.imagem
          FROM viaturas v
          LEFT JOIN alugueis a ON v.id = a.viatura_id AND a.status = 'finalizado'
          GROUP BY v.id
          ORDER BY total_alugueis DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$top_viaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// TOP CLIENTES MAIS ATIVOS
// ============================================
$query = "SELECT u.id, u.nome, u.email, u.telefone, COUNT(a.id) as total_alugueis, 
          COALESCE(SUM(a.preco_total), 0) as total_gasto
          FROM utilizadores u
          LEFT JOIN alugueis a ON u.id = a.utilizador_id AND a.status = 'finalizado'
          WHERE u.cargo = 'cliente'
          GROUP BY u.id
          ORDER BY total_alugueis DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$top_clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// ESTATÍSTICAS FINANCEIRAS GERAIS
// ============================================
$query = "SELECT 
          COALESCE(SUM(CASE WHEN estado = 'confirmado' THEN valor ELSE 0 END), 0) as receita_total,
          COALESCE(SUM(CASE WHEN estado = 'confirmado' AND MONTH(data_pagamento) = MONTH(CURRENT_DATE()) THEN valor ELSE 0 END), 0) as receita_mes,
          COUNT(CASE WHEN estado = 'confirmado' THEN 1 END) as total_transacoes,
          COALESCE(AVG(CASE WHEN estado = 'confirmado' THEN valor ELSE NULL END), 0) as ticket_medio,
          COALESCE(SUM(CASE WHEN estado = 'pendente' THEN valor ELSE 0 END), 0) as valor_pendente
          FROM pagamentos";
$stmt = $db->prepare($query);
$stmt->execute();
$financas = $stmt->fetch(PDO::FETCH_ASSOC);

// ============================================
// MULTAS TOTAIS
// ============================================
$query = "SELECT COALESCE(SUM(valor), 0) as total_multas, COUNT(*) as total_multa_registadas 
          FROM multas WHERE status = 'pago'";
$stmt = $db->prepare($query);
$stmt->execute();
$multas = $stmt->fetch(PDO::FETCH_ASSOC);

// ============================================
// ALUGUÉIS POR MÊS
// ============================================
$query = "SELECT 
          DATE_FORMAT(data_inicio, '%b/%Y') as mes,
          DATE_FORMAT(data_inicio, '%Y-%m') as mes_ordenar,
          COUNT(*) as total_alugueis
          FROM alugueis
          WHERE status = 'finalizado'
          GROUP BY DATE_FORMAT(data_inicio, '%Y-%m')
          ORDER BY mes_ordenar ASC
          LIMIT 12";
$stmt = $db->prepare($query);
$stmt->execute();
$alugueis_mensal = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// PAGAMENTOS POR MÉTODO
// ============================================
$query = "SELECT 
          metodo_pagamento,
          COUNT(*) as total,
          COALESCE(SUM(valor), 0) as total_valor
          FROM pagamentos
          WHERE estado = 'confirmado'
          GROUP BY metodo_pagamento
          ORDER BY total_valor DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$pagamentos_metodos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dados para gráficos
$meses_labels = [];
$meses_valores = [];
foreach($receita_mensal as $r) {
    $meses_labels[] = $r['mes'];
    $meses_valores[] = (float)$r['total'];
}

$alugueis_labels = [];
$alugueis_valores = [];
foreach($alugueis_mensal as $a) {
    $alugueis_labels[] = $a['mes'];
    $alugueis_valores[] = (int)$a['total_alugueis'];
}

$metodos_labels = [];
$metodos_valores = [];
foreach($pagamentos_metodos as $m) {
    $metodo_label = '';
    switch($m['metodo_pagamento']) {
        case 'dinheiro': $metodo_label = 'Dinheiro'; break;
        case 'cartao_credito': $metodo_label = 'Cartão Crédito'; break;
        case 'cartao_debito': $metodo_label = 'Cartão Débito'; break;
        case 'transferencia': $metodo_label = 'Transferência'; break;
        case 'mbway': $metodo_label = 'MB WAY'; break;
        case 'paypal': $metodo_label = 'PayPal'; break;
        default: $metodo_label = $m['metodo_pagamento'];
    }
    $metodos_labels[] = $metodo_label;
    $metodos_valores[] = (float)$m['total_valor'];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - RentCar</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .date-group input {
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

        .btn-info {
            background: #17a2b8;
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

        .btn-info:hover {
            background: #138496;
            transform: translateY(-2px);
        }

        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: white;
            border-radius: 1.5rem;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .chart-card h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .chart-card h3 i {
            color: #FF8C00;
        }

        .chart-container {
            position: relative;
            height: 250px;
        }

        /* Tables Grid */
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

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
        }

        .modern-table tr:last-child td {
            border-bottom: none;
        }

        .modern-table tr:hover td {
            background: #fef9e6;
        }

        .rank-1 { color: #FF8C00; font-weight: bold; }
        .rank-2 { color: #C0C0C0; font-weight: bold; }
        .rank-3 { color: #CD7F32; font-weight: bold; }

        .vehicle-info, .client-info {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .vehicle-icon, .client-icon {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 0.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
        }

        .client-icon {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        /* Period Summary */
        .period-summary {
            background: linear-gradient(135deg, #FF8C00, #FF6B00);
            color: white;
            border-radius: 1rem;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .summary-item {
            text-align: center;
        }

        .summary-label {
            font-size: 0.7rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-value {
            font-size: 1.2rem;
            font-weight: 700;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .charts-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .tables-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .conteudo-principal {
                padding: 1rem;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .charts-grid {
                grid-template-columns: 1fr;
            }
            .filters-bar {
                flex-direction: column;
            }
            .filter-group {
                width: 100%;
                justify-content: center;
            }
            .period-summary {
                flex-direction: column;
                text-align: center;
            }
        }

        @media print {
            .filters-bar, .btn-filter, .btn-secondary, .btn-success, .btn-info, .barra-lateral, .barra-superior {
                display: none;
            }
            .conteudo-principal {
                padding: 0;
                margin: 0;
            }
            .stat-card, .chart-card, .table-card {
                break-inside: avoid;
                page-break-inside: avoid;
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
                <h1>Relatórios e Análises</h1>
                <p>Análise completa de desempenho e métricas do sistema</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Receita Total</h3>
                        <div class="stat-number">MZN <?= number_format($financas['receita_total'] ?? 0, 2) ?></div>
                        <div class="stat-label">Histórico completo</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-calendar-month"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Receita Este Mês</h3>
                        <div class="stat-number">MZN <?= number_format($financas['receita_mes'] ?? 0, 2) ?></div>
                        <div class="stat-label"><?= date('F Y') ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Ticket Médio</h3>
                        <div class="stat-number">MZN <?= number_format($financas['ticket_medio'] ?? 0, 2) ?></div>
                        <div class="stat-label">Por transação</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Multas Arrecadadas</h3>
                        <div class="stat-number">MZN <?= number_format($multas['total_multas'] ?? 0, 2) ?></div>
                        <div class="stat-label"><?= $multas['total_multa_registadas'] ?? 0 ?> multas pagas</div>
                    </div>
                </div>
            </div>
            
            <!-- Period Filter -->
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
                    <button class="btn-filter" onclick="aplicarFiltro()">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <button class="btn-secondary" onclick="limparFiltro()">
                        <i class="fas fa-eraser"></i> Limpar
                    </button>
                </div>
                <div class="filter-group">
                    <button class="btn-success" onclick="exportarRelatorioExcel()">
                        <i class="fas fa-file-excel"></i> Exportar Excel
                    </button>
                    <button class="btn-info" onclick="window.print()">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                </div>
            </div>
            
            <!-- Period Summary -->
            <div class="period-summary">
                <div class="summary-item">
                    <div class="summary-label">Período Analisado</div>
                    <div class="summary-value"><?= date('d/m/Y', strtotime($data_inicio)) ?> - <?= date('d/m/Y', strtotime($data_fim)) ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Receita no Período</div>
                    <div class="summary-value">MZN <?= number_format($periodo_stats['total_periodo'] ?? 0, 2) ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Transações</div>
                    <div class="summary-value"><?= $periodo_stats['total_transacoes_periodo'] ?? 0 ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Ticket Médio</div>
                    <div class="summary-value">MZN <?= number_format($periodo_stats['ticket_medio_periodo'] ?? 0, 2) ?></div>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="charts-grid">
                <div class="chart-card">
                    <h3><i class="fas fa-chart-line"></i> Evolução da Receita</h3>
                    <div class="chart-container">
                        <canvas id="graficoReceita"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <h3><i class="fas fa-chart-bar"></i> Evolução de Aluguéis</h3>
                    <div class="chart-container">
                        <canvas id="graficoAlugueis"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <h3><i class="fas fa-chart-pie"></i> Pagamentos por Método</h3>
                    <div class="chart-container">
                        <canvas id="graficoMetodos"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Top Rankings -->
            <div class="tables-grid">
                <div class="table-card">
                    <div class="table-header">
                        <h3><i class="fas fa-trophy"></i> Top 10 Viaturas Mais Alugadas</h3>
                        <span class="badge-count"><?= count($top_viaturas) ?> viaturas</span>
                    </div>
                    <div class="table-container">
                        <table class="modern-table">
                            <thead>
                                <tr><th>#</th><th>Viatura</th><th>Aluguéis</th><th>Receita</th></tr>
                            </thead>
                            <tbody>
                                <?php if(count($top_viaturas) > 0): ?>
                                    <?php foreach($top_viaturas as $index => $v): ?>
                                    <tr>
                                        <td class="rank-<?= $index + 1 == 1 ? '1' : ($index + 1 == 2 ? '2' : ($index + 1 == 3 ? '3' : '')) ?>">
                                            <?php if($index == 0): ?>
                                                <i class="fas fa-crown"></i> 1º
                                            <?php elseif($index == 1): ?>
                                                2º
                                            <?php elseif($index == 2): ?>
                                                3º
                                            <?php else: ?>
                                                <?= $index + 1 ?>º
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="vehicle-info">
                                                <div class="vehicle-icon">
                                                    <i class="fas fa-car"></i>
                                                </div>
                                                <strong><?= htmlspecialchars($v['marca'] . ' ' . $v['modelo']) ?></strong>
                                            </div>
                                         </td>
                                        <td><?= $v['total_alugueis'] ?? 0 ?></td>
                                        <td class="rank-<?= $index + 1 == 1 ? '1' : '' ?>">
                                            MZN <?= number_format($v['receita'] ?? 0, 2) ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" style="text-align: center; padding: 2rem;">Nenhum dado disponível</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="table-card">
                    <div class="table-header">
                        <h3><i class="fas fa-star"></i> Top 10 Clientes Mais Ativos</h3>
                        <span class="badge-count"><?= count($top_clientes) ?> clientes</span>
                    </div>
                    <div class="table-container">
                        <table class="modern-table">
                            <thead>
                                <tr><th>#</th><th>Cliente</th><th>Aluguéis</th><th>Total Gasto</th></tr>
                            </thead>
                            <tbody>
                                <?php if(count($top_clientes) > 0): ?>
                                    <?php foreach($top_clientes as $index => $c): ?>
                                    <tr>
                                        <td class="rank-<?= $index + 1 == 1 ? '1' : ($index + 1 == 2 ? '2' : ($index + 1 == 3 ? '3' : '')) ?>">
                                            <?php if($index == 0): ?>
                                                <i class="fas fa-crown"></i> 1º
                                            <?php elseif($index == 1): ?>
                                                2º
                                            <?php elseif($index == 2): ?>
                                                3º
                                            <?php else: ?>
                                                <?= $index + 1 ?>º
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="client-info">
                                                <div class="client-icon">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div>
                                                    <strong><?= htmlspecialchars($c['nome']) ?></strong><br>
                                                    <small style="color: #999;"><?= $c['email'] ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= $c['total_alugueis'] ?? 0 ?></td>
                                        <td class="rank-<?= $index + 1 == 1 ? '1' : '' ?>">
                                            MZN <?= number_format($c['total_gasto'] ?? 0, 2) ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" style="text-align: center; padding: 2rem;">Nenhum dado disponível</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Gráfico de Receita Mensal
        const mesesLabels = <?= json_encode($meses_labels) ?>;
        const receitaValores = <?= json_encode($meses_valores) ?>;
        
        const ctxReceita = document.getElementById('graficoReceita').getContext('2d');
        new Chart(ctxReceita, {
            type: 'line',
            data: {
                labels: mesesLabels,
                datasets: [{
                    label: 'Receita (MZN)',
                    data: receitaValores,
                    borderColor: '#FF8C00',
                    backgroundColor: 'rgba(255, 140, 0, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#FF8C00',
                    pointBorderColor: 'white',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { 
                        callbacks: { 
                            label: (ctx) => `Receita: MZN ${ctx.raw.toLocaleString()}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Valor (MZN)', font: { weight: 'bold' } },
                        ticks: { callback: (value) => 'MZN ' + value.toLocaleString() }
                    }
                }
            }
        });
        
        // Gráfico de Aluguéis Mensais
        const alugueisLabels = <?= json_encode($alugueis_labels) ?>;
        const alugueisValores = <?= json_encode($alugueis_valores) ?>;
        
        const ctxAlugueis = document.getElementById('graficoAlugueis').getContext('2d');
        new Chart(ctxAlugueis, {
            type: 'bar',
            data: {
                labels: alugueisLabels,
                datasets: [{
                    label: 'Número de Aluguéis',
                    data: alugueisValores,
                    backgroundColor: 'rgba(255, 140, 0, 0.8)',
                    borderRadius: 8,
                    barPercentage: 0.7,
                    categoryPercentage: 0.8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { 
                        callbacks: { 
                            label: (ctx) => `Aluguéis: ${ctx.raw}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Quantidade', font: { weight: 'bold' } },
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
        
        // Gráfico de Pagamentos por Método
        const metodosLabels = <?= json_encode($metodos_labels) ?>;
        const metodosValores = <?= json_encode($metodos_valores) ?>;
        
        const ctxMetodos = document.getElementById('graficoMetodos').getContext('2d');
        new Chart(ctxMetodos, {
            type: 'doughnut',
            data: {
                labels: metodosLabels,
                datasets: [{
                    data: metodosValores,
                    backgroundColor: [
                        '#FF8C00',
                        '#28a745',
                        '#17a2b8',
                        '#ffc107',
                        '#6f42c1',
                        '#dc3545'
                    ],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { 
                        callbacks: { 
                            label: (ctx) => {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const percentagem = ((ctx.raw / total) * 100).toFixed(1);
                                return `${ctx.label}: MZN ${ctx.raw.toLocaleString()} (${percentagem}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        function aplicarFiltro() {
            const dataInicio = document.getElementById('data_inicio').value;
            const dataFim = document.getElementById('data_fim').value;
            
            if(dataInicio && dataFim) {
                window.location.href = `relatorios.php?inicio=${dataInicio}&fim=${dataFim}`;
            } else {
                alert('Selecione as datas de início e fim');
            }
        }
        
        function limparFiltro() {
            window.location.href = 'relatorios.php';
        }
        
        function exportarRelatorioExcel() {
            window.location.href = '../api/relatorios.php?acao=exportar_excel';
        }
    </script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>