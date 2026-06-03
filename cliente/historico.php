<?php
require_once '../config/auth.php';
Auth::cargo(['cliente']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$utilizador = Auth::utilizador();

// Buscar histórico de aluguéis finalizados
$query = "SELECT a.*, v.marca, v.modelo, v.imagem, v.matricula,
          p.valor as valor_pago, p.metodo_pagamento, p.data_pagamento, p.referencia_pagamento
          FROM alugueis a 
          JOIN viaturas v ON a.viatura_id = v.id 
          LEFT JOIN pagamentos p ON a.id = p.aluguer_id
          WHERE a.utilizador_id = :utilizador_id AND a.status = 'finalizado'
          ORDER BY a.data_devolucao DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':utilizador_id', $utilizador['id']);
$stmt->execute();
$historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$query = "SELECT 
          COUNT(*) as total_alugueis,
          COALESCE(SUM(preco_total), 0) as total_gasto,
          COALESCE(AVG(preco_total), 0) as media_gasto,
          COUNT(CASE WHEN a.status = 'ativo' THEN 1 END) as alugueis_ativos
          FROM alugueis a WHERE a.utilizador_id = :utilizador_id";
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
    <title>Histórico de Aluguer - SIGAV</title>
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

        .badge-pago { background: #d4edda; color: #155724; }
        .badge-pendente { background: #fff3cd; color: #856404; }

        .price {
            font-weight: 700;
            color: #FF8C00;
        }

        /* Botões */
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

        /* Empty State */
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

        .empty-state .btn-primary {
            background: #FF8C00;
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            font-size: 0.85rem;
            text-decoration: none;
            margin-top: 1rem;
        }

        .empty-state .btn-primary:hover {
            background: #e67e00;
            transform: translateY(-2px);
        }

        /* Modal Recibo */
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
            max-width: 400px;
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

        .modal-info {
            background: #f8f9fa;
            border-radius: 0.8rem;
            padding: 1rem;
            margin: 1rem 0;
            text-align: left;
        }

        .modal-info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.4rem 0;
            font-size: 0.85rem;
        }

        .modal-footer {
            padding: 1rem 1.5rem 1.5rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
            border-top: 1px solid #eee;
        }

        .btn-close-modal {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 0.8rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-close-modal:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-print {
            background: #FF8C00;
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 0.8rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-print:hover {
            background: #e67e00;
            transform: translateY(-2px);
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
                min-width: 650px;
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
                <h1>Histórico de Aluguéis</h1>
                <p>Consulte todo o seu histórico de aluguéis e recibos</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total de Aluguéis</h3>
                        <div class="stat-number"><?= $stats['total_alugueis'] ?? 0 ?></div>
                        <div class="stat-label">Aluguéis realizados</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Gasto</h3>
                        <div class="stat-number">MZN <?= number_format($stats['total_gasto'] ?? 0, 2) ?></div>
                        <div class="stat-label">Em aluguéis</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Média por Aluguer</h3>
                        <div class="stat-number">MZN <?= number_format($stats['media_gasto'] ?? 0, 2) ?></div>
                        <div class="stat-label">Ticket médio</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Aluguéis Ativos</h3>
                        <div class="stat-number"><?= $stats['alugueis_ativos'] ?? 0 ?></div>
                        <div class="stat-label">Em andamento</div>
                    </div>
                </div>
            </div>
            
            <!-- Main Card -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-history"></i> Histórico de Aluguéis</h2>
                </div>
                
                <?php if(count($historico) > 0): ?>
                <div class="table-container">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Viatura</th>
                                <th>Período</th>
                                <th>Dias</th>
                                <th>Valor</th>
                                <th>Pagamento</th>
                                <th>Data Devolução</th>
                                <th>Recibo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($historico as $item): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($item['marca'] . ' ' . $item['modelo']) ?></strong><br>
                                    <small style="color: #999;"><?= $item['matricula'] ?></small>
                                </td>
                                <td>
                                    <i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($item['data_inicio'])) ?><br>
                                    <i class="fas fa-arrow-right"></i> até <?= date('d/m/Y', strtotime($item['data_fim'])) ?>
                                </td>
                                <td><?= $item['total_dias'] ?> dias</small></td>
                                <td class="price">MZN <?= number_format($item['preco_total'], 2) ?></td>
                                <td>
                                    <?php if($item['valor_pago']): ?>
                                        <span class="badge badge-pago">
                                            <i class="fas fa-check-circle"></i> MZN <?= number_format($item['valor_pago'], 2) ?>
                                        </span><br>
                                        <small><i class="fas fa-credit-card"></i> <?= ucfirst(str_replace('_', ' ', $item['metodo_pagamento'])) ?></small>
                                    <?php else: ?>
                                        <span class="badge badge-pendente">
                                            <i class="fas fa-clock"></i> Pendente
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= date('d/m/Y', strtotime($item['data_devolucao'])) ?>
                                </td>
                                <td>
                                    <button class="btn-recibo" onclick="visualizarRecibo(<?= $item['id'] ?>, '<?= htmlspecialchars($item['marca'] . ' ' . $item['modelo']) ?>', '<?= date('d/m/Y', strtotime($item['data_inicio'])) ?>', '<?= date('d/m/Y', strtotime($item['data_fim'])) ?>', <?= $item['preco_total'] ?>)">
                                        <i class="fas fa-receipt"></i> Recibo
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
                    <p>Nenhum aluguer encontrado</p>
                    <small>Você ainda não realizou nenhum aluguer</small>
                    <a href="catalogo.php" class="btn-primary">
                        <i class="fas fa-car"></i> Alugar Viatura
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- MODAL DE RECIBO -->
    <div id="modalRecibo" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-receipt"></i> Detalhes do Recibo</h3>
                <button class="modal-close" onclick="fecharModalRecibo()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-icon">
                    <i class="fas fa-file-invoice" style="color: #FF8C00;"></i>
                </div>
                <div class="modal-message">Recibo de Aluguer</div>
                <div class="modal-info" id="reciboInfo">
                    <div class="modal-info-row">
                        <span><i class="fas fa-car"></i> Viatura:</span>
                        <strong id="recibo_viatura">-</strong>
                    </div>
                    <div class="modal-info-row">
                        <span><i class="fas fa-calendar"></i> Período:</span>
                        <strong id="recibo_periodo">-</strong>
                    </div>
                    <div class="modal-info-row">
                        <span><i class="fas fa-sun"></i> Dias:</span>
                        <strong id="recibo_dias">-</strong>
                    </div>
                    <div class="modal-info-row">
                        <span><i class="fas fa-money-bill-wave"></i> Valor:</span>
                        <strong id="recibo_valor" style="color: #FF8C00;">-</strong>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-close-modal" onclick="fecharModalRecibo()">
                    <i class="fas fa-times"></i> Fechar
                </button>
                <button class="btn-print" onclick="imprimirRecibo()">
                    <i class="fas fa-print"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
    
    <script>
        let reciboData = null;
        
        function visualizarRecibo(id, viatura, dataInicio, dataFim, valor) {
            reciboData = { id, viatura, dataInicio, dataFim, valor };
            
            document.getElementById('recibo_viatura').innerHTML = viatura;
            document.getElementById('recibo_periodo').innerHTML = dataInicio + ' até ' + dataFim;
            document.getElementById('recibo_valor').innerHTML = 'MZN ' + parseFloat(valor).toLocaleString('pt-MZ', {minimumFractionDigits: 2});
            
            // Calcular dias
            const inicio = new Date(dataInicio.split('/').reverse().join('-'));
            const fim = new Date(dataFim.split('/').reverse().join('-'));
            const diffTime = Math.abs(fim - inicio);
            const dias = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            document.getElementById('recibo_dias').innerHTML = dias + ' dia(s)';
            
            document.getElementById('modalRecibo').classList.add('active');
        }
        
        function fecharModalRecibo() {
            document.getElementById('modalRecibo').classList.remove('active');
            reciboData = null;
        }
        
        function imprimirRecibo() {
            if(reciboData) {
                // Abrir página de recibo para impressão
                window.open(`../pagamentos/recibo.php?aluguer_id=${reciboData.id}`, '_blank');
            }
            fecharModalRecibo();
        }
        
        function emitirReciboAluguer(aluguerId) {
            window.open(`../pagamentos/recibo.php?aluguer_id=${aluguerId}`, '_blank');
        }
        
        // Fechar modal com ESC
        document.addEventListener('keydown', function(event) {
            if(event.key === 'Escape') {
                fecharModalRecibo();
            }
        });
        
        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modal = document.getElementById('modalRecibo');
            if(event.target === modal) {
                fecharModalRecibo();
            }
        }
    </script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/cliente.js"></script>
</body>
</html>