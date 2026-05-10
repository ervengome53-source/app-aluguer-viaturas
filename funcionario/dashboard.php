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

// Próximas devoluções (próximos 3 dias)
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

// ============================================
// VIATURAS ALUGADAS (PARA O MAPA)
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

// Lista de locais para o mapa
$locais = [
    'Av. Marginal', 'Baixa', 'Sommerschield', 'Coop', 
    'Polana', 'Triunfo', 'Jardim', 'Malhangalene', 
    'Catembe', 'Costa do Sol', 'Matola', 'Liberdade'
];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Funcionário SIGAV</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="../assets/css/funcionario.css">
    <style>
        .welcome-banner {
            background: linear-gradient(135deg, var(--azul-escuro), var(--azul-claro));
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        
        .aluguer-atrasado {
            background: rgba(220, 53, 69, 0.1);
        }
        
        /* ============================================ */
        /* MAPA DE LOCALIZAÇÃO */
        /* ============================================ */
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
            min-height: 300px;
        }
        
        .mapa-titulo {
            text-align: center;
            color: white;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .mapa-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
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
        
        .status-alugado {
            color: #dc3545;
            font-weight: bold;
            margin-top: 8px;
            font-size: 11px;
        }
        
        @media (max-width: 768px) {
            .mapa-grid {
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
            
            <div class="welcome-banner">

                <p>Hoje é <?= date('d/m/Y') ?> - Gerencie as operações do dia</p>
            </div>
            
            <!-- ============================================ -->
            <!-- MAPA DE LOCALIZAÇÃO DAS VIATURAS ALUGADAS -->
            <!-- ============================================ -->
            <div class="mapa-container">
                <h3>Mapa de Localização das Viaturas Alugadas</h3>
                <div class="mapa-simulado">
                    <div class="mapa-titulo">
                        Mapa de Localização - Cidade de Maputo
                    </div>
                    <div class="mapa-grid">
                        <?php if(count($viaturas_alugadas) > 0): ?>
                            <?php foreach($viaturas_alugadas as $v): ?>
                            <?php $local = $locais[array_rand($locais)]; ?>
                            <div class="mapa-card alugado" onclick="window.location.href='devolucao.php?id=<?= $v['id'] ?>'">
                                <h4><?= htmlspecialchars($v['marca'] . ' ' . $v['modelo']) ?></h4>
                                <p>Matrícula: <?= $v['matricula'] ?></p>
                                <p>Cliente: <?= htmlspecialchars($v['cliente_nome']) ?></p>
                                <p>Devolução: <?= date('d/m/Y', strtotime($v['data_fim'])) ?></p>
                                <p>Localização atual: <strong><?= $local ?></strong></p>
                                <div class="status status-alugado">🔴 ALUGADO</div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="mapa-card" style="grid-column: span 3; text-align: center;">
                                <p>Todas as viaturas estão disponíveis no momento!</p>
                                <p style="font-size: 0.8rem;">Nenhuma viatura alugada no momento.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="grade-estatisticas">
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Reservas Pendentes</h3>
                        <div class="estatistica-numero"><?= $stats['reservas_pendentes_hoje'] ?? 0 ?></div>
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
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Devoluções Hoje</h3>
                        <div class="estatistica-numero"><?= $stats['devolucoes_hoje'] ?? 0 ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Aluguer Hoje</h3>
                        <div class="estatistica-numero"><?= $stats['alugueis_hoje'] ?? 0 ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <!-- Reservas Pendentes -->
                <div class="cartao">
                    <div class="cartao-cabecalho">
                        <h3 class="cartao-titulo">Reservas Pendentes</h3>
                    </div>
                    
                    <?php if(count($reservas_pendentes) > 0): ?>
                    <div class="container-tabela">
                        <table class="tabela">
                            <thead>
                                <tr><th>Cliente</th><th>Viatura</th><th>Datas</th><th>Ações</th></td>
                            </thead>
                            <tbody>
                                <?php foreach($reservas_pendentes as $reserva): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($reserva['cliente_nome']) ?><br>
                                        <small><?= $reserva['cliente_email'] ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?></td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($reserva['data_inicio'])) ?><br>
                                        <small>até <?= date('d/m/Y', strtotime($reserva['data_fim'])) ?></small>
                                    </td>
                                    <td class="tabela-acoes">
                                        <button class="btn btn-sucesso btn-sm" onclick="confirmarReserva(<?= $reserva['id'] ?>)">confirmar</button>
                                        <button class="btn btn-perigo btn-sm" onclick="rejeitarReserva(<?= $reserva['id'] ?>)">rejeitar</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="texto-centro" style="padding: 2rem;">Nenhuma reserva pendente</div>
                    <?php endif; ?>
                </div>
                
                <!-- Aluguéis Ativos -->
                <div class="cartao">
                    <div class="cartao-cabecalho">
                        <h3 class="cartao-titulo">Aluguer Ativos</h3>
                    </div>
                    
                    <?php if(count($alugueis_ativos) > 0): ?>
                    <div class="container-tabela">
                        <table class="tabela">
                            <thead>
                                <tr><th>Cliente</th><th>Viatura</th><th>Data Fim</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($alugueis_ativos as $aluguer): ?>
                                <tr class="<?= strtotime($aluguer['data_fim']) < time() ? 'aluguer-atrasado' : '' ?>">
                                    <td><?= htmlspecialchars($aluguer['cliente_nome']) ?></td>
                                    <td><?= htmlspecialchars($aluguer['marca'] . ' ' . $aluguer['modelo']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($aluguer['data_fim'])) ?>
                                        <?php if(strtotime($aluguer['data_fim']) < time()): ?>
                                            <span class="etiqueta etiqueta-perigo">Atrasado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="etiqueta etiqueta-aviso">Ativo</span></td>
                                </td>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="texto-centro" style="padding: 2rem;">Nenhum aluguer ativo</div>
                    <?php endif; ?>
                </div>
            </div>
                        
                <?php if(count($proximas_devolucoes) > 0): ?>
                <div class="container-tabela">
                    <table class="tabela">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Viatura</th>
                                <th>Data Devolução</th>
                                <th>Telefone</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($proximas_devolucoes as $devolucao): ?>
                            <tr>
                                <td><?= htmlspecialchars($devolucao['cliente_nome']) ?></td>
                                <td><?= htmlspecialchars($devolucao['marca'] . ' ' . $devolucao['modelo']) ?> <br>
                                    <small><?= $devolucao['matricula'] ?></small>
                                </td>
                                <td>
                                    <?= date('d/m/Y', strtotime($devolucao['data_fim'])) ?>
                                    <br>
                                    <small><?= floor((strtotime($devolucao['data_fim']) - time()) / 86400) + 1 ?> dias restantes</small>
                                </td>
                                <td><?= $devolucao['cliente_telefone'] ?? '---' ?></td>
                                <td>
                                    <a href="devolucao.php?id=<?= $devolucao['id'] ?>" class="btn btn-info btn-sm">Registrar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="texto-centro" style="padding: 2rem;">Nenhuma devolução prevista nos próximos dias</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function confirmarReserva(id) {
            modal.confirmar('Confirmar esta reserva?', async () => {
                const resultado = await API.post('../api/reservas.php?acao=confirmar', { reserva_id: id });
                if(resultado && resultado.sucesso) {
                    Utilitarios.mostrarNotificacao('Reserva confirmada!', 'sucesso');
                    setTimeout(() => window.location.reload(), 1000);
                }
            });
        }
        
        function rejeitarReserva(id) {
            modal.confirmar('Rejeitar esta reserva?', async () => {
                const resultado = await API.post('../api/reservas.php?acao=rejeitar', { reserva_id: id });
                if(resultado && resultado.sucesso) {
                    Utilitarios.mostrarNotificacao('Reserva rejeitada!', 'aviso');
                    setTimeout(() => window.location.reload(), 1000);
                }
            });
        }
    </script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/funcionario.js"></script>
</body>
</html>