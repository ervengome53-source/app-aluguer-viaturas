<?php
require_once '../config/auth.php';
Auth::cargo(['funcionario']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$utilizador = Auth::utilizador();

// Estatísticas do dia
$query = "SELECT 
          (SELECT COUNT(*) FROM reservas WHERE status = 'pendente' AND DATE(criado_em) = CURDATE()) as reservas_pendentes_hoje,
          (SELECT COUNT(*) FROM reservas WHERE status = 'confirmada' AND DATE(criado_em) = CURDATE()) as reservas_confirmadas_hoje,
          (SELECT COUNT(*) FROM alugueis WHERE status = 'ativo' AND DATE(data_inicio) = CURDATE()) as alugueis_hoje,
          (SELECT COUNT(*) FROM alugueis WHERE status = 'ativo') as alugueis_ativos,
          (SELECT COUNT(*) FROM alugueis WHERE DATE(data_devolucao) = CURDATE()) as devolucoes_hoje";
$stmt = $db->prepare($query);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Reservas pendentes (últimas 10)
$query = "SELECT r.*, u.nome as cliente_nome, u.email as cliente_email, u.telefone as cliente_telefone,
          v.marca, v.modelo, v.matricula
          FROM reservas r
          JOIN utilizadores u ON r.utilizador_id = u.id
          JOIN viaturas v ON r.viatura_id = v.id
          WHERE r.status = 'pendente'
          ORDER BY r.criado_em ASC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$reservas_pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Aluguéis ativos
$query = "SELECT a.*, u.nome as cliente_nome, v.marca, v.modelo, v.matricula
          FROM alugueis a
          JOIN utilizadores u ON a.utilizador_id = u.id
          JOIN viaturas v ON a.viatura_id = v.id
          WHERE a.status = 'ativo'
          ORDER BY a.data_fim ASC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$alugueis_ativos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Próximas devoluções
$query = "SELECT a.*, u.nome as cliente_nome, u.telefone as cliente_telefone,
          v.marca, v.modelo, v.matricula
          FROM alugueis a
          JOIN utilizadores u ON a.utilizador_id = u.id
          JOIN viaturas v ON a.viatura_id = v.id
          WHERE a.status = 'ativo' AND a.data_fim BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
          ORDER BY a.data_fim ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$proximas_devolucoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Viaturas alugadas para o mapa
$query = "SELECT a.*, v.marca, v.modelo, v.matricula, u.nome as cliente_nome
          FROM alugueis a
          JOIN viaturas v ON a.viatura_id = v.id
          JOIN utilizadores u ON a.utilizador_id = u.id
          WHERE a.status = 'ativo'
          ORDER BY a.data_fim ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$viaturas_alugadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$locais = [
    'Av. Marginal', 'Baixa', 'Sommerschield', 'Coop', 
    'Polana', 'Triunfo', 'Jardim', 'Malhangalene', 
    'Catembe', 'Costa do Sol', 'Matola', 'Liberdade'
];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Dashboard - Funcionário SIGAV</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
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

        /* Layout Principal */
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

        /* Conteúdo Principal */
        .conteudo-principal {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            background: #f5f7fb;
            min-height: 100vh;
            width: calc(100% - 280px);
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

        /* Grid de Duas Colunas */
        .two-columns {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        /* Cards Padrão */
        .card {
            background: white;
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .card-header {
            padding: 1.2rem 1.5rem;
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
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

        /* Tabelas */
        .table-container {
            padding: 0 1rem 1rem 1rem;
            overflow-x: auto;
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 500px;
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

        .badge-pendente { background: #fff3cd; color: #856404; }
        .badge-confirmada { background: #d4edda; color: #155724; }
        .badge-ativo { background: #d4edda; color: #155724; }
        .badge-atrasado { background: #f8d7da; color: #721c24; }

        /* Botões */
        .btn-sm {
            padding: 0.3rem 0.8rem;
            font-size: 0.7rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; transform: translateY(-2px); }

        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; transform: translateY(-2px); }

        .btn-info { background: #17a2b8; color: white; }
        .btn-info:hover { background: #138496; transform: translateY(-2px); }

        /* Mapa */
        .mapa-card {
            background: white;
            border-radius: 1rem;
            padding: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            cursor: pointer;
            border-left: 4px solid #dc3545;
        }

        .mapa-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .mapa-card h4 {
            font-size: 1rem;
            color: #1a1a2e;
            margin-bottom: 0.5rem;
        }

        .mapa-card p {
            font-size: 0.75rem;
            color: #666;
            margin-bottom: 0.3rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #999;
        }

        .empty-state i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            opacity: 0.5;
        }

        /* Responsivo */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .two-columns {
                grid-template-columns: 1fr;
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
                <h1>Painel do Funcionário</h1>
                <p>Bem-vindo de volta, <?= htmlspecialchars($utilizador['nome'] ?? 'Funcionário') ?>.</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Reservas Pendentes</h3>
                        <div class="stat-number"><?= $stats['reservas_pendentes_hoje'] ?? 0 ?></div>
                        <div class="stat-label">Aguardando ação</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Aluguer Ativos</h3>
                        <div class="stat-number"><?= $stats['alugueis_ativos'] ?? 0 ?></div>
                        <div class="stat-label">Em circulação</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-undo-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Devoluções Hoje</h3>
                        <div class="stat-number"><?= $stats['devolucoes_hoje'] ?? 0 ?></div>
                        <div class="stat-label">Previsão para hoje</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Aluguer Hoje</h3>
                        <div class="stat-number"><?= $stats['alugueis_hoje'] ?? 0 ?></div>
                        <div class="stat-label">Novos registos</div>
                    </div>
                </div>
            </div>
            
            <!-- Mapa de Localização -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h3><i class="fas fa-map-marker-alt"></i> Localização das Viaturas Alugadas</h3>
                    <span class="badge-count"><?= count($viaturas_alugadas) ?> viaturas em circulação</span>
                </div>
                <div class="table-container">
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">
                        <?php if(count($viaturas_alugadas) > 0): ?>
                            <?php foreach($viaturas_alugadas as $v): ?>
                            <?php $local = $locais[array_rand($locais)]; ?>
                            <div class="mapa-card" onclick="window.location.href='devolucao.php?id=<?= $v['id'] ?>'">
                                <h4><i class="fas fa-car"></i> <?= htmlspecialchars($v['marca'] . ' ' . $v['modelo']) ?></h4>
                                <p><i class="fas fa-id-card"></i> Matrícula: <?= $v['matricula'] ?></p>
                                <p><i class="fas fa-user"></i> Cliente: <?= htmlspecialchars($v['cliente_nome']) ?></p>
                                <p><i class="fas fa-calendar"></i> Devolução: <?= date('d/m/Y', strtotime($v['data_fim'])) ?></p>
                                <p><i class="fas fa-location-dot"></i> Localização: <strong><?= $local ?></strong></p>
                                <div class="badge badge-atrasado" style="margin-top: 0.5rem; display: inline-flex;">🔴 ALUGADO</div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state" style="grid-column: span 3;">
                                <i class="fas fa-check-circle" style="color: #28a745;"></i>
                                <p>Todas as viaturas estão disponíveis no momento!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Reservas Pendentes e Aluguéis Ativos -->
            <div class="two-columns">
                <!-- Reservas Pendentes -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-clock"></i> Reservas Pendentes</h3>
                        <span class="badge-count"><?= count($reservas_pendentes) ?> reservas</span>
                    </div>
                    <div class="table-container">
                        <?php if(count($reservas_pendentes) > 0): ?>
                        <table class="modern-table">
                            <thead>
                                <tr><th>Cliente</th><th>Viatura</th><th>Período</th><th>Ações</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($reservas_pendentes as $reserva): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($reserva['cliente_nome']) ?></strong><br>
                                        <small style="color: #999;"><?= $reserva['cliente_email'] ?></small>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?><br>
                                        <small><?= $reserva['matricula'] ?></small>
                                    </td>
                                    <td>
                                        <small><?= date('d/m/Y', strtotime($reserva['data_inicio'])) ?></small><br>
                                        <small>até <?= date('d/m/Y', strtotime($reserva['data_fim'])) ?></small>
                                    </td>
                                    <td>
                                        <button class="btn-sm btn-success" onclick="confirmarReserva(<?= $reserva['id'] ?>)">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn-sm btn-danger" onclick="rejeitarReserva(<?= $reserva['id'] ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>Nenhuma reserva pendente</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Aluguéis Ativos -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-key"></i> Aluguer Ativos</h3>
                        <span class="badge-count"><?= count($alugueis_ativos) ?> ativos</span>
                    </div>
                    <div class="table-container">
                        <?php if(count($alugueis_ativos) > 0): ?>
                        <table class="modern-table">
                            <thead>
                                <tr><th>Cliente</th><th>Viatura</th><th>Devolução</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($alugueis_ativos as $aluguer): ?>
                                <?php $atrasado = strtotime($aluguer['data_fim']) < time(); ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($aluguer['cliente_nome']) ?></strong>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($aluguer['marca'] . ' ' . $aluguer['modelo']) ?>
                                    </td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($aluguer['data_fim'])) ?>
                                        <?php if($atrasado): ?>
                                            <span class="badge badge-atrasado" style="margin-left: 0.5rem;">Atrasado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-ativo"><i class="fas fa-play"></i> Ativo</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>Nenhum aluguel ativo</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Próximas Devoluções -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-week"></i> Próximas Devoluções (3 dias)</h3>
                    <span class="badge-count"><?= count($proximas_devolucoes) ?> programadas</span>
                </div>
                <div class="table-container">
                    <?php if(count($proximas_devolucoes) > 0): ?>
                    <table class="modern-table">
                        <thead>
                            <tr><th>Cliente</th><th>Viatura</th><th>Data Devolução</th><th>Contacto</th><th>Ação</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($proximas_devolucoes as $devolucao): ?>
                            <?php 
                            $dias_restantes = ceil((strtotime($devolucao['data_fim']) - time()) / 86400);
                            $classe_urgente = $dias_restantes <= 1 ? 'badge-atrasado' : '';
                            ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($devolucao['cliente_nome']) ?></strong>
                                </td>
                                <td>
                                    <?= htmlspecialchars($devolucao['marca'] . ' ' . $devolucao['modelo']) ?><br>
                                    <small><?= $devolucao['matricula'] ?></small>
                                </td>
                                <td>
                                    <?= date('d/m/Y', strtotime($devolucao['data_fim'])) ?>
                                    <?php if($dias_restantes <= 1): ?>
                                        <span class="badge badge-atrasado" style="margin-left: 0.5rem;">Urgente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <i class="fas fa-phone"></i> <?= $devolucao['cliente_telefone'] ?? '---' ?>
                                </td>
                                <td>
                                    <a href="devolucao.php?id=<?= $devolucao['id'] ?>" class="btn-sm btn-info">
                                        <i class="fas fa-exchange-alt"></i> Registrar
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-check"></i>
                        <p>Nenhuma devolução prevista nos próximos 3 dias</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function confirmarReserva(id) {
            if(confirm('Confirmar esta reserva?')) {
                window.location.href = `reservas.php?confirmar=${id}`;
            }
        }
        
        function rejeitarReserva(id) {
            if(confirm('Rejeitar esta reserva?')) {
                window.location.href = `reservas.php?rejeitar=${id}`;
            }
        }
    </script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/funcionario.js"></script>
</body>
</html>