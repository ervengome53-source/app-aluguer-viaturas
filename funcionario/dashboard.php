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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Funcionário SIGAV</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .container-app { display: flex; min-height: 100vh; }
        .conteudo-principal { flex: 1; margin-left: 270px; padding: 1.5rem; }
        
        /* Cards de estatísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.2rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover { transform: translateY(-3px); }
        
        .stat-info h4 {
            font-size: 0.75rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #1E3A5F;
        }
        
        .stat-icon {
            font-size: 2.2rem;
            color: #FF8C00;
            opacity: 0.8;
        }
        
        /* Card padrão */
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #1E3A5F, #2a5298);
        }
        
        .card-header h3 {
            color: white;
            margin: 0;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Tabelas */
        .table-container { overflow-x: auto; }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th {
            padding: 0.8rem;
            text-align: left;
            font-weight: 600;
            color: #1E3A5F;
            border-bottom: 2px solid #FF8C00;
            font-size: 0.8rem;
            text-transform: uppercase;
        }
        
        .table td {
            padding: 0.8rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .table tr:hover td { background: #fef9e6; }
        
        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .badge-pendente { background: #fff3cd; color: #856404; }
        .badge-confirmada { background: #d4edda; color: #155724; }
        .badge-ativo { background: #d4edda; color: #155724; }
        .badge-atrasado { background: #f8d7da; color: #721c24; }
        
        /* Botões */
        .btn-sm {
            padding: 0.25rem 0.6rem;
            font-size: 0.7rem;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.3s ease;
        }
        
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; transform: translateY(-2px); }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; transform: translateY(-2px); }
        .btn-info { background: #17a2b8; color: white; }
        .btn-info:hover { background: #138496; transform: translateY(-2px); }
        
        /* Mapa */
        .mapa-container {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .mapa-simulado {
            background: linear-gradient(135deg, #1a472a, #2d6a4f);
            border-radius: 12px;
            padding: 1rem;
        }
        
        .mapa-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .mapa-card {
            background: rgba(255,255,255,0.95);
            border-radius: 10px;
            padding: 0.8rem;
            cursor: pointer;
            transition: transform 0.3s ease;
            border-left: 3px solid #dc3545;
        }
        
        .mapa-card:hover { transform: translateY(-3px); }
        
        @media (max-width: 768px) {
            .conteudo-principal { margin-left: 0; padding: 1rem; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <!-- Cards de Estatísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <h4><i class="fas fa-clock"></i> Reservas Pendentes</h4>
                        <div class="stat-number"><?= $stats['reservas_pendentes_hoje'] ?? 0 ?></div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h4><i class="fas fa-car"></i> Aluguer Ativos</h4>
                        <div class="stat-number"><?= $stats['alugueis_ativos'] ?? 0 ?></div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-key"></i></div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h4><i class="fas fa-undo-alt"></i> Devoluções Hoje</h4>
                        <div class="stat-number"><?= $stats['devolucoes_hoje'] ?? 0 ?></div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-exchange-alt"></i></div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h4><i class="fas fa-plus-circle"></i> Aluguer Hoje</h4>
                        <div class="stat-number"><?= $stats['alugueis_hoje'] ?? 0 ?></div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                </div>
            </div>
            
            <!-- Mapa de Localização -->
            <div class="mapa-container">
                <h3 style="color: #1E3A5F; margin-bottom: 1rem;"><i class="fas fa-map-marker-alt"></i> Mapa de Localização das Viaturas Alugadas</h3>
                <div class="mapa-simulado">
                    <div class="mapa-grid">
                        <?php if(count($viaturas_alugadas) > 0): ?>
                            <?php foreach($viaturas_alugadas as $v): ?>
                            <?php $local = $locais[array_rand($locais)]; ?>
                            <div class="mapa-card" onclick="window.location.href='devolucao.php?id=<?= $v['id'] ?>'">
                                <h4><i class="fas fa-car"></i> <?= htmlspecialchars($v['marca'] . ' ' . $v['modelo']) ?></h4>
                                <p><i class="fas fa-id-card"></i> Matrícula: <?= $v['matricula'] ?></p>
                                <p><i class="fas fa-user"></i> Cliente: <?= htmlspecialchars($v['cliente_nome']) ?></p>
                                <p><i class="fas fa-calendar"></i> Devolução: <?= date('d/m/Y', strtotime($v['data_fim'])) ?></p>
                                <p><i class="fas fa-location-dot"></i> Localização: <strong><?= $local ?></strong></p>
                                <div class="badge badge-atrasado" style="margin-top: 0.5rem;"><i class="fas fa-lock"></i> ALUGADO</div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="mapa-card" style="grid-column: span 3; text-align: center;">
                                <i class="fas fa-check-circle" style="font-size: 2rem; color: #28a745;"></i>
                                <p>Todas as viaturas estão disponíveis!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Reservas Pendentes e Aluguéis Ativos -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <!-- Reservas Pendentes -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-clock"></i> Reservas Pendentes</h3>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr><th>Cliente</th><th>Viatura</th><th>Datas</th><th>Ações</th></tr>
                            </thead>
                            <tbody>
                                <?php if(count($reservas_pendentes) > 0): ?>
                                    <?php foreach($reservas_pendentes as $reserva): ?>
                                    <tr>
                                        <td><i class="fas fa-user-circle"></i> <?= htmlspecialchars($reserva['cliente_nome']) ?><br><small><?= $reserva['cliente_email'] ?></small></td>
                                        <td><i class="fas fa-car-side"></i> <?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?></td>
                                        <td><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($reserva['data_inicio'])) ?><br><small>até <?= date('d/m/Y', strtotime($reserva['data_fim'])) ?></small></td>
                                        <td class="acoes-cell">
                                            <button class="btn-sm btn-success" onclick="confirmarReserva(<?= $reserva['id'] ?>)"><i class="fas fa-check"></i> Confirmar</button>
                                            <button class="btn-sm btn-danger" onclick="rejeitarReserva(<?= $reserva['id'] ?>)"><i class="fas fa-times"></i> Rejeitar</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" style="text-align: center; padding: 2rem;"><i class="fas fa-inbox"></i> Nenhuma reserva pendente</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Aluguéis Ativos -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-key"></i> Aluguer Ativos</h3>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr><th>Cliente</th><th>Viatura</th><th>Data Fim</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php if(count($alugueis_ativos) > 0): ?>
                                    <?php foreach($alugueis_ativos as $aluguer): ?>
                                    <tr>
                                        <td><i class="fas fa-user"></i> <?= htmlspecialchars($aluguer['cliente_nome']) ?></td>
                                        <td><i class="fas fa-car"></i> <?= htmlspecialchars($aluguer['marca'] . ' ' . $aluguer['modelo']) ?></td>
                                        <td><i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($aluguer['data_fim'])) ?>
                                            <?php if(strtotime($aluguer['data_fim']) < time()): ?>
                                                <span class="badge badge-atrasado"><i class="fas fa-exclamation-triangle"></i> Atrasado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge badge-ativo"><i class="fas fa-play"></i> Ativo</span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" style="text-align: center; padding: 2rem;"><i class="fas fa-inbox"></i> Nenhum aluguer ativo</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Próximas Devoluções -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-undo-alt"></i> Próximas Devoluções (próximos 3 dias)</h3>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr><th>Cliente</th><th>Viatura</th><th>Data Devolução</th><th>Telefone</th><th>Ação</th></tr>
                        </thead>
                        <tbody>
                            <?php if(count($proximas_devolucoes) > 0): ?>
                                <?php foreach($proximas_devolucoes as $devolucao): ?>
                                <tr>
                                    <td><i class="fas fa-user"></i> <?= htmlspecialchars($devolucao['cliente_nome']) ?></td>
                                    <td><i class="fas fa-car"></i> <?= htmlspecialchars($devolucao['marca'] . ' ' . $devolucao['modelo']) ?><br><small><?= $devolucao['matricula'] ?></small></td>
                                    <td><i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($devolucao['data_fim'])) ?></td>
                                    <td><i class="fas fa-phone"></i> <?= $devolucao['cliente_telefone'] ?? '---' ?></td>
                                    <td><a href="devolucao.php?id=<?= $devolucao['id'] ?>" class="btn-sm btn-info"><i class="fas fa-exchange-alt"></i> Registrar</a></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align: center; padding: 2rem;"><i class="fas fa-calendar-check"></i> Nenhuma devolução prevista</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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