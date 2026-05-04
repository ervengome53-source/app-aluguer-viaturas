<?php
require_once '../config/auth.php';
Auth::cargo(['admin']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$utilizador = Auth::utilizador();

// ============================================
// ESTATÍSTICAS GERAIS
// ============================================

// Estatísticas principais
$query = "SELECT 
          (SELECT COUNT(*) FROM utilizadores WHERE cargo = 'cliente' AND status = 'ativo') as total_clientes,
          (SELECT COUNT(*) FROM utilizadores WHERE cargo = 'funcionario' AND status = 'ativo') as total_funcionarios,
          (SELECT COUNT(*) FROM viaturas) as total_viaturas,
          (SELECT COUNT(*) FROM viaturas WHERE status = 'disponivel') as viaturas_disponiveis,
          (SELECT COUNT(*) FROM viaturas WHERE status = 'alugado') as viaturas_alugadas,
          (SELECT COUNT(*) FROM viaturas WHERE status = 'manutencao') as viaturas_manutencao,
          (SELECT COUNT(*) FROM reservas WHERE status = 'pendente') as reservas_pendentes,
          (SELECT COUNT(*) FROM reservas WHERE status = 'confirmada') as reservas_confirmadas,
          (SELECT COUNT(*) FROM alugueis WHERE status = 'ativo') as alugueis_ativos,
          (SELECT COUNT(*) FROM alugueis WHERE status = 'finalizado') as alugueis_finalizados";
$stmt = $db->prepare($query);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// ============================================
// RECEITAS
// ============================================

// Receita total
$query = "SELECT SUM(valor) as receita_total, COUNT(*) as total_pagamentos 
          FROM pagamentos WHERE estado = 'confirmado'";
$stmt = $db->prepare($query);
$stmt->execute();
$receita_total = $stmt->fetch(PDO::FETCH_ASSOC);

// Receita mensal (últimos 6 meses)
$query = "SELECT 
          DATE_FORMAT(data_pagamento, '%b') as mes,
          MONTH(data_pagamento) as mes_num,
          SUM(valor) as total
          FROM pagamentos
          WHERE estado = 'confirmado' AND data_pagamento IS NOT NULL
          AND data_pagamento >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
          GROUP BY MONTH(data_pagamento), DATE_FORMAT(data_pagamento, '%b')
          ORDER BY data_pagamento ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$receita_mensal = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Receita hoje
$query = "SELECT SUM(valor) as hoje FROM pagamentos 
          WHERE estado = 'confirmado' AND DATE(data_pagamento) = CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$receita_hoje = $stmt->fetch(PDO::FETCH_ASSOC);

// Receita este mês
$query = "SELECT SUM(valor) as mes_atual FROM pagamentos 
          WHERE estado = 'confirmado' AND MONTH(data_pagamento) = MONTH(CURDATE())";
$stmt = $db->prepare($query);
$stmt->execute();
$receita_mes = $stmt->fetch(PDO::FETCH_ASSOC);

// ============================================
// VIATURAS MAIS ALUGADAS
// ============================================

$query = "SELECT v.marca, v.modelo, COUNT(a.id) as total_alugueis, SUM(a.preco_total) as receita
          FROM viaturas v
          LEFT JOIN alugueis a ON v.id = a.viatura_id
          WHERE a.status = 'finalizado' OR a.id IS NULL
          GROUP BY v.id
          ORDER BY total_alugueis DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$top_viaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// CLIENTES MAIS ATIVOS
// ============================================

$query = "SELECT u.nome, u.email, COUNT(a.id) as total_alugueis, SUM(a.preco_total) as total_gasto
          FROM utilizadores u
          LEFT JOIN alugueis a ON u.id = a.utilizador_id
          WHERE u.cargo = 'cliente' AND (a.status = 'finalizado' OR a.id IS NULL)
          GROUP BY u.id
          ORDER BY total_alugueis DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$top_clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// PAGAMENTOS PENDENTES
// ============================================

$query = "SELECT p.*, u.nome as cliente_nome, 
          CASE 
              WHEN p.reserva_id IS NOT NULL THEN 
                  (SELECT CONCAT(v.marca, ' ', v.modelo) FROM reservas r JOIN viaturas v ON r.viatura_id = v.id WHERE r.id = p.reserva_id)
              ELSE 
                  (SELECT CONCAT(v.marca, ' ', v.modelo) FROM alugueis a JOIN viaturas v ON a.viatura_id = v.id WHERE a.id = p.aluguer_id)
          END as descricao
          FROM pagamentos p
          JOIN utilizadores u ON p.utilizador_id = u.id
          WHERE p.estado = 'pendente'
          ORDER BY p.data_criacao DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$pagamentos_pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// VIATURAS ALUGADAS (para o mapa)
// ============================================

$query = "SELECT a.*, v.marca, v.modelo, v.matricula, u.nome as cliente_nome
          FROM alugueis a
          JOIN viaturas v ON a.viatura_id = v.id
          JOIN utilizadores u ON a.utilizador_id = u.id
          WHERE a.status = 'ativo'
          ORDER BY a.data_fim ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$viaturas_alugadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dados para o gráfico de receita mensal (formato JSON)
$meses_labels = [];
$meses_valores = [];
foreach($receita_mensal as $r) {
    $meses_labels[] = $r['mes'];
    $meses_valores[] = $r['total'];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SIGAV</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
        }
        
        .container-app {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .barra-lateral {
            width: 260px;
            background: #1E3A5F;
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            padding: 1.5rem;
        }
        
        .barra-lateral-cabecalho {
            text-align: center;
            margin-bottom: 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 1rem;
        }
        
        .barra-lateral-cabecalho h3 {
            color: #FF8C00;
            font-size: 1.5rem;
        }
        
        .barra-lateral-nav a {
            display: block;
            padding: 0.75rem;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .barra-lateral-nav a:hover, .barra-lateral-nav a.ativo {
            background: #FF8C00;
        }
        
        /* Conteúdo principal */
        .conteudo-principal {
            flex: 1;
            margin-left: 260px;
            padding: 20px;
        }
        
        .barra-superior {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .info-utilizador {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .avatar-utilizador {
            width: 40px;
            height: 40px;
            background: #FF8C00;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }
        
        .btn-perigo {
            background: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
        }
        
        /* Cards de estatísticas */
        .grade-estatisticas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .cartao-estatistica {
            background: white;
            border-radius: 12px;
            padding: 1.2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .cartao-estatistica:hover {
            transform: translateY(-3px);
        }
        
        .estatistica-info h3 {
            color: #666;
            font-size: 0.75rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }
        
        .estatistica-numero {
            font-size: 1.8rem;
            font-weight: bold;
            color: #1E3A5F;
        }
        
        .estatistica-icone {
            font-size: 2rem;
            color: #FF8C00;
        }
        
        /* Gráficos */
        .graficos-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .cartao-grafico {
            background: white;
            border-radius: 12px;
            padding: 1.2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .cartao-grafico h3 {
            color: #1E3A5F;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #FF8C00;
            font-size: 1rem;
        }
        
        .grafico {
            height: 250px;
            position: relative;
        }
        
        /* Mapa de Viaturas Alugadas */
        .mapa-container {
            background: white;
            border-radius: 12px;
            padding: 1.2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .mapa-container h3 {
            color: #1E3A5F;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #FF8C00;
        }
        
        .mapa-simulado {
            background: linear-gradient(135deg, #1a472a 0%, #2d6a4f 100%);
            border-radius: 12px;
            padding: 20px;
            min-height: 350px;
            position: relative;
            overflow: hidden;
        }
        
        .mapa-titulo {
            text-align: center;
            color: white;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .mapa-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        
        .mapa-card {
            background: rgba(255,255,255,0.95);
            border-radius: 10px;
            padding: 12px;
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        
        .mapa-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .mapa-card.alugado {
            border-left: 4px solid #dc3545;
        }
        
        .mapa-card.disponivel {
            border-left: 4px solid #28a745;
        }
        
        .mapa-card h4 {
            color: #1E3A5F;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .mapa-card p {
            color: #666;
            font-size: 11px;
            margin: 3px 0;
        }
        
        .mapa-card .status {
            font-size: 10px;
            margin-top: 5px;
        }
        
        .status-alugado {
            color: #dc3545;
        }
        
        .status-disponivel {
            color: #28a745;
        }
        
        /* Tabelas */
        .tabela-container {
            overflow-x: auto;
        }
        
        .tabela {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        
        .tabela th, .tabela td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .tabela th {
            background: #1E3A5F;
            color: white;
        }
        
        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 11px;
        }
        
        .btn-sm {
            padding: 3px 8px;
            font-size: 10px;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-warning {
            background: #FF8C00;
            color: white;
        }
        
        .etiqueta {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
        }
        
        .etiqueta-pendente {
            background: #ffc107;
            color: #333;
        }
        
        .etiqueta-confirmado {
            background: #28a745;
            color: white;
        }
        
        @media (max-width: 768px) {
            .conteudo-principal {
                margin-left: 0;
            }
            .graficos-container {
                grid-template-columns: 1fr;
            }
            .mapa-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .scroll {
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container-app">
        <!-- Sidebar -->
        <div class="barra-lateral">
            <div class="barra-lateral-cabecalho">
                <h3> SIGAV</h3>
                <small>Administrador</small>
            </div>
            <nav class="barra-lateral-nav">
                <a href="dashboard.php" class="ativo"> Dashboard</a>
                <a href="viaturas.php">Viaturas</a>
                <a href="usuarios.php"> Utilizadores</a>
                <a href="relatorios.php"> Relatórios</a>
                <a href="relatorio_pagamentos.php"> Pagamentos</a>
                <a href="configuracoes.php"> Configurações</a>
            </nav>
        </div>
        
        <div class="conteudo-principal">
            <!-- Top Bar -->
            <div class="barra-superior">
                <div class="info-utilizador">
                    <span>Bem-vindo, <strong><?= htmlspecialchars($utilizador['nome']) ?></strong></span>
                    <div class="avatar-utilizador"><?= strtoupper(substr($utilizador['nome'], 0, 1)) ?></div>
                    <a href="../public/logout.php" class="btn-perigo">Sair</a>
                </div>
            </div>
            
            <!-- ============================================ -->
            <!-- CARDS ESTATÍSTICOS PRINCIPAIS -->
            <!-- ============================================ -->
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
                        <h3>Viaturas Disponível</h3>
                        <div class="estatistica-numero"><?= $stats['viaturas_disponiveis'] ?? 0 ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
            </div>
            
            <div class="grade-estatisticas">
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Viaturas Alugadas</h3>
                        <div class="estatistica-numero"><?= $stats['viaturas_alugadas'] ?? 0 ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Reservas Pendentes</h3>
                        <div class="estatistica-numero"><?= $stats['reservas_pendentes'] ?? 0 ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Aluguéis Ativos</h3>
                        <div class="estatistica-numero"><?= $stats['alugueis_ativos'] ?? 0 ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Receita Total</h3>
                        <div class="estatistica-numero">MZN <?= number_format($receita_total['receita_total'] ?? 0, 2) ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
            </div>
            
            <!-- ============================================ -->
            <!-- GRÁFICOS -->
            <!-- ============================================ -->
            <div class="graficos-container">
                <div class="cartao-grafico">
                    <h3> Receita Mensal (últimos 6 meses)</h3>
                    <div class="grafico">
                        <canvas id="graficoReceita"></canvas>
                    </div>
                </div>
                <div class="cartao-grafico">
                    <h3> Viaturas Mais Alugadas</h3>
                    <div class="grafico">
                        <canvas id="graficoViaturas"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- ============================================ -->
            <!-- MAPA DE VIATURAS ALUGADAS -->
            <!-- ============================================ -->
            <div class="mapa-container">
                <h3> Mapa de Viaturas Alugadas</h3>
                <div class="mapa-simulado">
                    <div class="mapa-titulo">
                        ️ Mapa de Localização das Viaturas - Cidade de Maputo
                    </div>
                    <div class="mapa-grid">
                        <?php if(count($viaturas_alugadas) > 0): ?>
                            <?php foreach($viaturas_alugadas as $v): ?>
                            <div class="mapa-card alugado" onclick="window.location.href='../funcionario/devolucao.php?id=<?= $v['id'] ?>'">
                                <h4><?= htmlspecialchars($v['marca'] . ' ' . $v['modelo']) ?></h4>
                                <p> Matrícula: <?= $v['matricula'] ?></p>
                                <p> Cliente: <?= htmlspecialchars($v['cliente_nome']) ?></p>
                                <p> Devolução: <?= date('d/m/Y', strtotime($v['data_fim'])) ?></p>
                                <p> Localização: 
                                    <?php 
                                    $locais = ['Av. Marginal', 'Baixa', 'Sommerschield', 'Coop', 'Polana', 'Triunfo'];
                                    echo $locais[array_rand($locais)];
                                    ?>
                                </p>
                                <div class="status status-alugado"> ALUGADO</div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="mapa-card disponivel" style="grid-column: span 3; text-align: center;">
                                <p> Todas as viaturas estão disponíveis no momento!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- ============================================ -->
            <!-- TOP VIATURAS E CLIENTES -->
            <!-- ============================================ -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                <div class="cartao-grafico">
                    <h3> Top 5 Viaturas Mais Alugadas</h3>
                    <div class="tabela-container">
                        <table class="tabela">
                            <thead>
                                <tr><th>Viatura</th><th>Total Aluguéis</th><th>Receita</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($top_viaturas as $v): ?>
                                <tr>
                                    <td><?= htmlspecialchars($v['marca'] . ' ' . $v['modelo']) ?></td>
                                    <td><?= $v['total_alugueis'] ?? 0 ?></td>
                                    <td>MZN <?= number_format($v['receita'] ?? 0, 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="cartao-grafico">
                    <h3> Top 5 Clientes Mais Ativos</h3>
                    <div class="tabela-container">
                        <table class="tabela">
                            <thead>
                                <tr><th>Cliente</th><th>Total Aluguéis</th><th>Total Gasto</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($top_clientes as $c): ?>
                                <tr>
                                    <td><?= htmlspecialchars($c['nome']) ?><br><small><?= $c['email'] ?></small></td>
                                    <td><?= $c['total_alugueis'] ?? 0 ?></td>
                                    <td>MZN <?= number_format($c['total_gasto'] ?? 0, 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- ============================================ -->
            <!-- PAGAMENTOS PENDENTES -->
            <!-- ============================================ -->
            <div class="cartao-grafico">
                <h3> Pagamentos Pendentes</h3>
                <?php if(count($pagamentos_pendentes) > 0): ?>
                <div class="tabela-container scroll">
                    <table class="tabela">
                        <thead>
                            <tr><th>Referência</th><th>Cliente</th><th>Descrição</th><th>Valor</th><th>Método</th><th>Ações</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($pagamentos_pendentes as $p): ?>
                            <tr>
                                <td><?= $p['referencia_pagamento'] ?></td>
                                <td><?= htmlspecialchars($p['cliente_nome']) ?></td>
                                <td><?= htmlspecialchars(substr($p['descricao'], 0, 30)) ?>...</td>
                                <td>MZN <?= number_format($p['valor'], 2) ?></td>
                                <td><?= ucfirst(str_replace('_', ' ', $p['metodo_pagamento'])) ?></td>
                                <td>
                                    <a href="../funcionario/pagamentos.php?confirmar=<?= $p['id'] ?>" class="btn btn-success btn-sm">Confirmar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p style="text-align: center; padding: 20px;"> Não há pagamentos pendentes</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Gráfico de Receita Mensal
        const ctxReceita = document.getElementById('graficoReceita').getContext('2d');
        const meses = <?= json_encode($meses_labels) ?>;
        const valores = <?= json_encode($meses_valores) ?>;
        
        new Chart(ctxReceita, {
            type: 'line',
            data: {
                labels: meses,
                datasets: [{
                    label: 'Receita (MZN)',
                    data: valores,
                    borderColor: '#FF8C00',
                    backgroundColor: 'rgba(255, 140, 0, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { callbacks: { label: (ctx) => `MZN ${ctx.raw.toLocaleString()}` } }
                },
                scales: { y: { beginAtZero: true, title: { display: true, text: 'Valor (MZN)' } } }
            }
        });
        
        // Gráfico de Viaturas Mais Alugadas
        const ctxViaturas = document.getElementById('graficoViaturas').getContext('2d');
        const viaturasLabels = <?= json_encode(array_map(function($v) { return $v['modelo']; }, $top_viaturas)) ?>;
        const viaturasData = <?= json_encode(array_map(function($v) { return $v['total_alugueis'] ?? 0; }, $top_viaturas)) ?>;
        
        new Chart(ctxViaturas, {
            type: 'bar',
            data: {
                labels: viaturasLabels,
                datasets: [{
                    label: 'Número de Aluguéis',
                    data: viaturasData,
                    backgroundColor: '#1E3A5F',
                    borderColor: '#FF8C00',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: { y: { beginAtZero: true, title: { display: true, text: 'Quantidade' } } }
            }
        });
    </script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>