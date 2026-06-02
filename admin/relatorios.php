<?php
require_once '../config/auth.php';
Auth::cargo(['admin']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// ============================================
// RECEITA MENSAL (ÚLTIMOS 12 MESES)
// ============================================

$query = "SELECT 
          DATE_FORMAT(data_pagamento, '%b/%Y') as mes,
          SUM(valor) as total
          FROM pagamentos
          WHERE estado = 'confirmado' AND data_pagamento IS NOT NULL
          GROUP BY DATE_FORMAT(data_pagamento, '%Y-%m')
          ORDER BY data_pagamento ASC
          LIMIT 12";
$stmt = $db->prepare($query);
$stmt->execute();
$receita_mensal = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// TOP VIATURAS MAIS ALUGADAS (HISTÓRICO)
// ============================================

$query = "SELECT v.marca, v.modelo, COUNT(a.id) as total_alugueis, SUM(a.preco_total) as receita
          FROM viaturas v
          LEFT JOIN alugueis a ON v.id = a.viatura_id
          WHERE a.status = 'finalizado' OR a.id IS NULL
          GROUP BY v.id
          ORDER BY total_alugueis DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$top_viaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// TOP CLIENTES MAIS ATIVOS (HISTÓRICO)
// ============================================

$query = "SELECT u.nome, u.email, COUNT(a.id) as total_alugueis, SUM(a.preco_total) as total_gasto
          FROM utilizadores u
          LEFT JOIN alugueis a ON u.id = a.utilizador_id
          WHERE u.cargo = 'cliente' AND (a.status = 'finalizado' OR a.id IS NULL)
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
          SUM(CASE WHEN estado = 'confirmado' THEN valor ELSE 0 END) as receita_total,
          SUM(CASE WHEN estado = 'confirmado' AND MONTH(data_pagamento) = MONTH(CURRENT_DATE()) THEN valor ELSE 0 END) as receita_mes,
          COUNT(CASE WHEN estado = 'confirmado' THEN 1 END) as total_transacoes,
          AVG(CASE WHEN estado = 'confirmado' THEN valor ELSE NULL END) as ticket_medio,
          SUM(CASE WHEN estado = 'pendente' THEN valor ELSE 0 END) as valor_pendente
          FROM pagamentos";
$stmt = $db->prepare($query);
$stmt->execute();
$financas = $stmt->fetch(PDO::FETCH_ASSOC);

// ============================================
// MULTAS TOTAIS
// ============================================

$query = "SELECT SUM(valor) as total_multas, COUNT(*) as total_multa_registadas 
          FROM multas WHERE status = 'pago'";
$stmt = $db->prepare($query);
$stmt->execute();
$multas = $stmt->fetch(PDO::FETCH_ASSOC);

// Dados para gráficos (formato JSON)
$meses_labels = [];
$meses_valores = [];
foreach($receita_mensal as $r) {
    $meses_labels[] = $r['mes'];
    $meses_valores[] = (float)$r['total'];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Admin</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .filtros-periodo {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        
        .btn-exportar {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-exportar:hover {
            background: #FF8C00;
        }
        
        .btn-imprimir {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-imprimir:hover {
            background: #138496;
        }
        
        @media print {
            .btn-exportar, .btn-imprimir, .barra-superior, .barra-lateral {
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
            <!-- ============================================ -->
            <!-- TOP VIATURAS E CLIENTES -->
            <!-- ============================================ -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="cartao">
                    <div class="cartao-cabecalho">
                        <h3 class="cartao-titulo">Top 10 Viaturas Mais Alugadas</h3>
                    </div>
                    <div class="container-tabela">
                        <table class="tabela">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Viatura</th>
                                    <th>Total Aluguer</th>
                                    <th>Receita Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($top_viaturas) > 0): ?>
                                    <?php $rank = 1; ?>
                                    <?php foreach($top_viaturas as $v): ?>
                                    <tr class="<?= $rank <= 3 ? 'destaque' : '' ?>">
                                        <td style="font-weight: bold; color: <?= $rank == 1 ? '#FF8C00' : ($rank == 2 ? '#C0C0C0' : ($rank == 3 ? '#CD7F32' : '#333')) ?>">
                                            <?= $rank++ ?>º
                                        </td>
                                        <td><strong><?= htmlspecialchars($v['marca'] . ' ' . $v['modelo']) ?></strong></td>
                                        <td><?= $v['total_alugueis'] ?? 0 ?></td>
                                        <td>MZN <?= number_format($v['receita'] ?? 0, 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="texto-centro">Nenhum dado disponível</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="cartao">
                    <div class="cartao-cabecalho">
                        <h3 class="cartao-titulo">Top 10 Clientes Mais Ativos</h3>
                    </div>
                    <div class="container-tabela">
                        <table class="tabela">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Cliente</th>
                                    <th>Total Aluguer</th>
                                    <th>Total Gasto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($top_clientes) > 0): ?>
                                    <?php $rank = 1; ?>
                                    <?php foreach($top_clientes as $c): ?>
                                    <tr>
                                        <td style="font-weight: bold;"><?= $rank++ ?>º</td>
                                        <td>
                                            <strong><?= htmlspecialchars($c['nome']) ?></strong><br>
                                            <small><?= $c['email'] ?></small>
                                        </td>
                                        <td><?= $c['total_alugueis'] ?? 0 ?></td>
                                        <td>MZN <?= number_format($c['total_gasto'] ?? 0, 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="texto-centro">Nenhum dado disponível</td></tr>
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
        const meses = <?= json_encode($meses_labels) ?>;
        const valores = <?= json_encode($meses_valores) ?>;
        
        const ctx = document.getElementById('graficoReceita').getContext('2d');
        let graficoReceita = new Chart(ctx, {
            type: 'line',
            data: {
                labels: meses,
                datasets: [{
                    label: 'Receita (MZN)',
                    data: valores,
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
                        title: { display: true, text: 'Mês/Ano' }
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