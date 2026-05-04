<?php
require_once '../config/auth.php';
Auth::cargo(['admin']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Estatísticas gerais
$query = "SELECT 
          COUNT(*) as total_transacoes,
          SUM(valor) as valor_total,
          AVG(valor) as valor_medio,
          SUM(CASE WHEN estado = 'confirmado' THEN valor ELSE 0 END) as valor_confirmado,
          COUNT(CASE WHEN estado = 'pendente' THEN 1 END) as pendentes,
          COUNT(CASE WHEN estado = 'falhou' THEN 1 END) as falhados
          FROM pagamentos 
          WHERE MONTH(data_criacao) = MONTH(CURRENT_DATE())";
$stmt = $db->prepare($query);
$stmt->execute();
$resumo_mensal = $stmt->fetch(PDO::FETCH_ASSOC);

// Pagamentos por método
$query = "SELECT metodo_pagamento, COUNT(*) as total, SUM(valor) as total_valor 
          FROM pagamentos 
          WHERE estado = 'confirmado'
          GROUP BY metodo_pagamento";
$stmt = $db->prepare($query);
$stmt->execute();
$metodos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pagamentos recentes
$query = "SELECT p.*, u.nome as cliente_nome 
          FROM pagamentos p 
          JOIN utilizadores u ON p.utilizador_id = u.id 
          ORDER BY p.data_criacao DESC LIMIT 50";
$stmt = $db->prepare($query);
$stmt->execute();
$pagamentos_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Pagamentos - Admin</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <div class="grade-estatisticas">
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Total Transações (Mês)</h3>
                        <div class="estatistica-numero"><?= $resumo_mensal['total_transacoes'] ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
                
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Valor Total (Mês)</h3>
                        <div class="estatistica-numero">MZN<?= number_format($resumo_mensal['valor_total'] ?? 0, 2) ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
                
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Valor Médio</h3>
                        <div class="estatistica-numero">MZN<?= number_format($resumo_mensal['valor_medio'] ?? 0, 2) ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
                
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Pagamentos Pendentes</h3>
                        <div class="estatistica-numero"><?= $resumo_mensal['pendentes'] ?? 0 ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                <div class="cartao">
                    <h3 class="cartao-titulo">Pagamentos por Método</h3>
                    <canvas id="graficoMetodos" style="max-height: 300px;"></canvas>
                </div>
                
                <div class="cartao">
                    <h3 class="cartao-titulo">Evolução Mensal</h3>
                    <canvas id="graficoEvolucao" style="max-height: 300px;"></canvas>
                </div>
            </div>
            
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo">Pagamentos Recentes</h3>
                    <div>
                        <button class="btn btn-success" onclick="exportarExcel()"> Exportar Excel</button>
                        <button class="btn btn-info" onclick="window.print()"> Imprimir</button>
                    </div>
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
                                <th>Estado</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pagamentos_recentes as $pagamento): ?>
                            <tr>
                                <td><?= $pagamento['referencia_pagamento'] ?></td>
                                <td><?= htmlspecialchars($pagamento['cliente_nome']) ?></td>
                                <td>MZN<?= number_format($pagamento['valor'], 2) ?></td>
                                <td><?= ucfirst(str_replace('_', ' ', $pagamento['metodo_pagamento'])) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($pagamento['data_criacao'])) ?></td>
                                <td>
                                    <span class="etiqueta etiqueta-<?= $pagamento['estado'] ?>">
                                        <?= ucfirst($pagamento['estado']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-info btn-sm" onclick="verDetalhes(<?= $pagamento['id'] ?>)">
                                        Ver
                                    </button>
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
        // Gráfico de métodos de pagamento
        const ctxMetodos = document.getElementById('graficoMetodos').getContext('2d');
        new Chart(ctxMetodos, {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_map(function($m) { 
                    return ucfirst(str_replace('_', ' ', $m['metodo_pagamento'])); 
                }, $metodos)) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($metodos, 'total_valor')) ?>,
                    backgroundColor: ['#FF8C00', '#1E3A5F', '#28a745', '#dc3545', '#17a2b8']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
        
        // Gráfico de evolução mensal
        const ctxEvolucao = document.getElementById('graficoEvolucao').getContext('2d');
        new Chart(ctxEvolucao, {
            type: 'line',
            data: {
                labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
                datasets: [{
                    label: 'Receita (€)',
                    data: [12500, 15000, 18200, 21000, 23500, 28900, 31200, 34500, 37800, 40200, 43500, 46800],
                    borderColor: '#FF8C00',
                    backgroundColor: 'rgba(255, 140, 0, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' }
                }
            }
        });
        
        function verDetalhes(pagamentoId) {
            window.open(`../pagamentos/recibo.php?id=${pagamentoId}`, '_blank');
        }
        
        function exportarExcel() {
            window.location.href = '../pagamentos/api_pagamentos.php?acao=exportar_excel';
        }
    </script>
</body>
</html>