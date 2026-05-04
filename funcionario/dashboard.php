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
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Funcionário SIGAV</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="../assets/css/funcionario.css">
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <div class="welcome-banner">
                <h1><?= htmlspecialchars($utilizador['nome']) ?>!</h1>
                <p>Hoje é <?= date('d/m/Y') ?> - Gerencie as operações do dia</p>
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
                        <h3 class="cartao-titulo"> Reservas Pendentes</h3>
                    </div>
                    
                    <?php if(count($reservas_pendentes) > 0): ?>
                    <div class="container-tabela">
                        <table class="tabela">
                            <thead>
                                <tr><th>Cliente</th><th>Viatura</th><th>Datas</th><th>Ações</th></tr>
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
                                        <button class="btn btn-sucesso btn-sm" onclick="confirmarReserva(<?= $reserva['id'] ?>)">comfirmar</button>
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
                        <h3 class="cartao-titulo"> Aluguer Ativos</h3>
                    
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
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="texto-centro" style="padding: 2rem;">Nenhum aluguer ativo</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Próximas Devoluções -->
            <div class="cartao" style="margin-top: 1.5rem;">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo"> Próximas Devoluções (próximos 3 dias)</h3>
                    <a href="devolucao.php" class="btn btn-destaque btn-sm">Registar Devolução</a>
                </div>
                
                <?php if(count($proximas_devolucoes) > 0): ?>
                <div class="container-tabela">
                    <table class="tabela">
                        <thead>
                            <tr><th>Cliente</th><th>Viatura</th><th>Data Devolução</th><th>Telefone</th><th>Ação</th></tr>
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
                                    <button class="btn btn-info btn-sm" onclick="window.location.href='devolucao.php?id=<?= $devolucao['id'] ?>'">
                                        Registrar
                                    </button>
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
    </style>
    
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
                const resultado = await API.post('/api../reservas.php?acao=rejeitar', { reserva_id: id });
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