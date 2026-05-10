<?php
require_once '../config/auth.php';
Auth::cargo(['admin']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Filtros
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-t');

// Estatísticas detalhadas
$query = "SELECT 
          SUM(CASE WHEN estado = 'confirmado' THEN valor ELSE 0 END) as receita_total,
          SUM(CASE WHEN estado = 'confirmado' AND MONTH(data_pagamento) = MONTH(CURRENT_DATE()) THEN valor ELSE 0 END) as receita_mes,
          SUM(CASE WHEN estado = 'confirmado' AND WEEK(data_pagamento) = WEEK(CURRENT_DATE()) THEN valor ELSE 0 END) as receita_semana,
          SUM(CASE WHEN estado = 'confirmado' AND DATE(data_pagamento) = CURDATE() THEN valor ELSE 0 END) as receita_hoje,
          SUM(CASE WHEN estado = 'pendente' THEN valor ELSE 0 END) as valor_pendente,
          COUNT(CASE WHEN estado = 'confirmado' THEN 1 END) as total_transacoes,
          AVG(CASE WHEN estado = 'confirmado' THEN valor ELSE NULL END) as ticket_medio
          FROM pagamentos";
$stmt = $db->prepare($query);
$stmt->execute();
$financas = $stmt->fetch(PDO::FETCH_ASSOC);

// Pagamentos por método
$query = "SELECT metodo_pagamento, COUNT(*) as total, SUM(valor) as total_valor 
          FROM pagamentos WHERE estado = 'confirmado'
          GROUP BY metodo_pagamento";
$stmt = $db->prepare($query);
$stmt->execute();
$metodos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pagamentos recentes
$query = "SELECT p.*, u.nome as cliente_nome, u.email as cliente_email
          FROM pagamentos p
          JOIN utilizadores u ON p.utilizador_id = u.id
          WHERE p.data_criacao BETWEEN :inicio AND :fim
          ORDER BY p.data_criacao DESC
          LIMIT 50";
$stmt = $db->prepare($query);
$stmt->bindParam(':inicio', $data_inicio);
$stmt->bindParam(':fim', $data_fim);
$stmt->execute();
$pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Pagamentos - Admin</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .filtros {
            background: white;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .stats-financeiras {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-financeira {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-valor {
            font-size: 1.6rem;
            font-weight: bold;
            color: #1E3A5F;
        }
        
        .graficos-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .cartao {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .cartao h3 {
            color: #1E3A5F;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #1E3A5F;
        }
        
        .btn-primario {
            background: #1E3A5F;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .btn-primario:hover {
            background: #2a5298;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .graficos-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <!-- Estatísticas Financeiras -->
            <div class="stats-financeiras">
                <div class="stat-financeira">
                    <div class="stat-valor">MZN <?= number_format($financas['receita_total'] ?? 0, 2) ?></div>
                    <small>Receita Total</small>
                </div>
                <div class="stat-financeira">
                    <div class="stat-valor">MZN <?= number_format($financas['receita_semana'] ?? 0, 2) ?></div>
                    <small>Receita Esta Semana</small>
                </div>
                <div class="stat-financeira">
                    <div class="stat-valor">MZN <?= number_format($financas['receita_hoje'] ?? 0, 2) ?></div>
                    <small>Receita Hoje</small>
                </div>
            </div>
            
            <div class="stats-financeiras">
                <div class="stat-financeira">
                    <div class="stat-valor"><?= $financas['total_transacoes'] ?? 0 ?></div>
                    <small>Total Transações</small>
                </div>
                <div class="stat-financeira">
                    <div class="stat-valor">MZN <?= number_format($financas['valor_pendente'] ?? 0, 2) ?></div>
                    <small>Valor Pendente</small>
                </div>
            </div>
            
            <!-- Gráficos -->
            <div class="graficos-container">
                <div class="cartao">
                    <h3>Pagamentos por Método</h3>
                    <canvas id="graficoMetodos" style="height: 250px;"></canvas>
                </div>
                <div class="cartao">
                    <h3>Valor por Método</h3>
                    <canvas id="graficoValorMetodos" style="height: 250px;"></canvas>
                </div>
            </div>
            
            <!-- Tabela de Pagamentos -->
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo" style="color: #1E3A5F;">Lista de Pagamentos</h3>
                </div>
                <div class="container-tabela">
                    <table class="tabela">
                        <thead>
                            <tr>
                                <th>Referência</th>
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
                                <td><?= $p['referencia_pagamento'] ?></td>
                                <td><?= htmlspecialchars($p['cliente_nome']) ?><br><small><?= $p['cliente_email'] ?></small></td>
                                <td>MZN <?= number_format($p['valor'], 2) ?></td>
                                <td><?= ucfirst(str_replace('_', ' ', $p['metodo_pagamento'])) ?></td>
                                <td><?= date('d/m/Y - H:i', strtotime($p['data_criacao'])) ?></td>
                                <td>
                                    <span class="etiqueta etiqueta-<?= $p['estado'] == 'confirmado' ? 'sucesso' : 'aviso' ?>">
                                        <?= ucfirst($p['estado']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-info btn-sm" onclick="emitirRecibo(<?= $p['id'] ?>)">recibo</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Gráfico de Pagamentos por Método
        const metodosLabels = <?= json_encode(array_column($metodos, 'metodo_pagamento')) ?>;
        const metodosData = <?= json_encode(array_column($metodos, 'total')) ?>;
        
        new Chart(document.getElementById('graficoMetodos'), {
            type: 'pie',
            data: {
                labels: metodosLabels,
                datasets: [{
                    data: metodosData,
                    backgroundColor: ['#1E3A5F', '#2a5298', '#3a6baf', '#4a84cc', '#5a9de9']
                }]
            },
            options: { responsive: true, maintainAspectRatio: true }
        });
        
        // Gráfico de Valor por Método
        const valorMetodosData = <?= json_encode(array_column($metodos, 'total_valor')) ?>;
        
        new Chart(document.getElementById('graficoValorMetodos'), {
            type: 'bar',
            data: {
                labels: metodosLabels,
                datasets: [{
                    label: 'Valor (MZN)',
                    data: valorMetodosData,
                    backgroundColor: '#1E3A5F',
                    borderRadius: 8
                }]
            },
            options: { responsive: true, maintainAspectRatio: true }
        });
        
        function aplicarFiltro() {
            const inicio = document.getElementById('data_inicio').value;
            const fim = document.getElementById('data_fim').value;
            window.location.href = `?data_inicio=${inicio}&data_fim=${fim}`;
        }
        
        function exportarExcel() {
            window.location.href = '../api/pagamentos.php?acao=exportar_excel';
        }
        
        function emitirRecibo(id) {
            window.open(`../pagamentos/recibo.php?id=${id}`, '_blank');
        }
    </script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>