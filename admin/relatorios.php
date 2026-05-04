<?php
require_once '../config/auth.php';
Auth::cargo(['admin']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Estatísticas gerais
$query = "SELECT 
          (SELECT COUNT(*) FROM utilizadores WHERE cargo = 'cliente') as total_clientes,
          (SELECT COUNT(*) FROM utilizadores WHERE cargo = 'funcionario') as total_funcionarios,
          (SELECT COUNT(*) FROM viaturas) as total_viaturas,
          (SELECT COUNT(*) FROM viaturas WHERE status = 'disponivel') as viaturas_disponiveis,
          (SELECT COUNT(*) FROM reservas WHERE status = 'pendente') as reservas_pendentes,
          (SELECT COUNT(*) FROM alugueis WHERE status = 'ativo') as alugueis_ativos";
$stmt = $db->prepare($query);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Viaturas mais alugadas
$query = "SELECT v.marca, v.modelo, COUNT(a.id) as total_alugueis, SUM(a.preco_total) as receita
          FROM viaturas v
          LEFT JOIN alugueis a ON v.id = a.viatura_id
          WHERE a.status = 'finalizado' OR a.status IS NULL
          GROUP BY v.id
          ORDER BY total_alugueis DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$top_viaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Clientes mais ativos
$query = "SELECT u.nome, u.email, COUNT(a.id) as total_alugueis, SUM(a.preco_total) as total_gasto
          FROM utilizadores u
          JOIN alugueis a ON u.id = a.utilizador_id
          WHERE a.status = 'finalizado' AND u.cargo = 'cliente'
          GROUP BY u.id
          ORDER BY total_alugueis DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$top_clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Receita mensal
$query = "SELECT 
          DATE_FORMAT(data_pagamento, '%Y-%m') as mes,
          SUM(valor) as total
          FROM pagamentos
          WHERE estado = 'confirmado' AND data_pagamento IS NOT NULL
          GROUP BY DATE_FORMAT(data_pagamento, '%Y-%m')
          ORDER BY mes DESC
          LIMIT 12";
$stmt = $db->prepare($query);
$stmt->execute();
$receita_mensal = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cálculos financeiros adicionais
$query = "SELECT 
          SUM(CASE WHEN estado = 'confirmado' THEN valor ELSE 0 END) as receita_total,
          SUM(CASE WHEN estado = 'confirmado' AND MONTH(data_pagamento) = MONTH(CURRENT_DATE()) THEN valor ELSE 0 END) as receita_mes,
          COUNT(CASE WHEN estado = 'confirmado' THEN 1 END) as total_transacoes,
          AVG(CASE WHEN estado = 'confirmado' THEN valor ELSE NULL END) as ticket_medio
          FROM pagamentos";
$stmt = $db->prepare($query);
$stmt->execute();
$financas = $stmt->fetch(PDO::FETCH_ASSOC);

// Multas totais
$query = "SELECT SUM(valor) as total_multas FROM multas WHERE status = 'pago'";
$stmt = $db->prepare($query);
$stmt->execute();
$multas = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Admin</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
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
                        <h3>Total Clientes</h3>
                        <div class="estatistica-numero"><?= $stats['total_clientes'] ?? 0 ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Total Funcionários</h3>
                        <div class="estatistica-numero"><?= $stats['total_funcionarios'] ?? 0 ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Total Viaturas</h3>
                        <div class="estatistica-numero"><?= $stats['total_viaturas'] ?? 0 ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Viaturas Disponíveis</h3>
                        <div class="estatistica-numero"><?= $stats['viaturas_disponiveis'] ?? 0 ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
            </div>
            
            <div class="grade-estatisticas">
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Reservas Pendentes</h3>
                        <div class="estatistica-numero"><?= $stats['reservas_pendentes'] ?? 0 ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Aluguer Ativos</h3>
                        <div class="estatistica-numero"><?= $stats['alugueis_ativos'] ?? 0 ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
            </div>
            
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo"> Receita Mensal</h3>
                </div>
                <div style="height: 300px;">
                    <canvas id="graficoReceita"></canvas>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="cartao">
                    <div class="cartao-cabecalho">
                        <h3 class="cartao-titulo"> Viaturas Mais Alugadas</h3>
                    </div>
                    <div class="container-tabela">
                        <table class="tabela">
                            <thead>
                                <tr>
                                    <th>Viatura</th>
                                    <th>Total Aluguer</th>
                                    <th>Receita</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($top_viaturas) > 0): ?>
                                    <?php foreach($top_viaturas as $v): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($v['marca'] . ' ' . $v['modelo']) ?></td>
                                        <td><?= $v['total_alugueis'] ?? 0 ?></td>
                                        <td>MZN <?= number_format($v['receita'] ?? 0, 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="texto-centro">Nenhum dado disponível</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="cartao">
                    <div class="cartao-cabecalho">
                        <h3 class="cartao-titulo"> Clientes Mais Ativos</h3>
                    </div>
                    <div class="container-tabela">
                        <table class="tabela">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Total Aluguer</th>
                                    <th>Total Gasto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($top_clientes) > 0): ?>
                                    <?php foreach($top_clientes as $c): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($c['nome']) ?><br>
                                            <small><?= $c['email'] ?></small>
                                        </td>
                                        <td><?= $c['total_alugueis'] ?></td>
                                        <td>MZN <?= number_format($c['total_gasto'] ?? 0, 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="texto-centro">Nenhum dado disponível</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="cartao" style="margin-top: 1.5rem;">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo"> Resumo Financeiro</h3>
                    <div>
                        <button class="btn btn-sucesso" onclick="exportarRelatorioExcel()"> Exportar Excel</button>
                        <button class="btn btn-info" onclick="window.print()"> Imprimir</button>
                    </div>
                </div>
                
                <div class="grade-estatisticas" style="margin-top: 1rem;">
                    <div class="cartao-estatistica">
                        <div class="estatistica-info">
                            <h3>Receita Total</h3>
                            <div class="estatistica-numero">MZN <?= number_format($financas['receita_total'] ?? 0, 2) ?></div>
                        </div>
                        <div class="estatistica-icone"></div>
                    </div>
                    <div class="cartao-estatistica">
                        <div class="estatistica-info">
                            <h3>Receita Este Mês</h3>
                            <div class="estatistica-numero">MZN <?= number_format($financas['receita_mes'] ?? 0, 2) ?></div>
                        </div>
                        <div class="estatistica-icone"></div>
                    </div>
                    <div class="cartao-estatistica">
                        <div class="estatistica-info">
                            <h3>Total Transações</h3>
                            <div class="estatistica-numero"> <?= $financas['total_transacoes'] ?? 0 ?></div>
                        </div>
                        <div class="estatistica-icone"></div>
                    </div>
                    <div class="cartao-estatistica">
                        <div class="estatistica-info">
                            <h3>Ticket Médio</h3>
                            <div class="estatistica-numero">MZN <?= number_format($financas['ticket_medio'] ?? 0, 2) ?></div>
                        </div>
                        <div class="estatistica-icone"></div>
                    </div>
                </div>
                
                <div class="grade-estatisticas">
                    <div class="cartao-estatistica">
                        <div class="estatistica-info">
                            <h3>Total Multas</h3>
                            <div class="estatistica-numero">MZN <?= number_format($multas['total_multas'] ?? 0, 2) ?></div>
                        </div>
                        <div class="estatistica-icone"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Gráfico de Receita Mensal
        const meses = <?= json_encode(array_column($receita_mensal, 'mes')) ?>;
        const valores = <?= json_encode(array_column($receita_mensal, 'total')) ?>;
        
        if(document.getElementById('graficoReceita')) {
            const ctx = document.getElementById('graficoReceita').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: meses.length > 0 ? meses.reverse() : ['Sem dados'],
                    datasets: [{
                        label: 'Receita (MZN)',
                        data: valores.length > 0 ? valores.reverse() : [0],
                        borderColor: '#FF8C00',
                        backgroundColor: 'rgba(255, 140, 0, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: { 
                            callbacks: { 
                                label: (ctx) => `MZN ${ctx.raw.toLocaleString()}`
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Valor (MZN)' }
                        },
                        x: {
                            title: { display: true, text: 'Mês' }
                        }
                    }
                }
            });
        }
        
        function exportarRelatorioExcel() {
            window.location.href = '../api/relatorios.php?acao=exportar_excel';
        }
    </script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>