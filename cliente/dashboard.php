<?php
require_once '../config/auth.php';
Auth::cargo(['cliente']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$utilizador = Auth::utilizador();

// Estatísticas
$estatisticas = [];

// Total de reservas ativas
$query = "SELECT COUNT(*) as total FROM reservas 
          WHERE utilizador_id = :utilizador_id AND status IN ('pendente', 'confirmada')";
$stmt = $db->prepare($query);
$stmt->bindParam(':utilizador_id', $utilizador['id']);
$stmt->execute();
$estatisticas['reservas_ativas'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total de aluguéis
$query = "SELECT COUNT(*) as total FROM alugueis WHERE utilizador_id = :utilizador_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':utilizador_id', $utilizador['id']);
$stmt->execute();
$estatisticas['total_alugueis'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total gasto
$query = "SELECT SUM(preco_total) as total FROM alugueis 
          WHERE utilizador_id = :utilizador_id AND status = 'finalizado'";
$stmt = $db->prepare($query);
$stmt->bindParam(':utilizador_id', $utilizador['id']);
$stmt->execute();
$estatisticas['total_gasto'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Reservas recentes
$query = "SELECT r.*, v.modelo, v.marca, v.imagem 
          FROM reservas r 
          JOIN viaturas v ON r.viatura_id = v.id 
          WHERE r.utilizador_id = :utilizador_id 
          ORDER BY r.criado_em DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':utilizador_id', $utilizador['id']);
$stmt->execute();
$reservas_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - Cliente SIGAV</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="../assets/css/cliente.css">
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <div class="grade-estatisticas">
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3><i class="fas fa-calendar-check"></i> Reservas Ativas</h3>
                        <div class="estatistica-numero"><?= $estatisticas['reservas_ativas'] ?></div>
                    </div>
                    <div class="estatistica-icone"><i class="fas fa-calendar-check"></i></div>
                </div>
                
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3><i class="fas fa-car"></i> Total Aluguer</h3>
                        <div class="estatistica-numero"><?= $estatisticas['total_alugueis'] ?></div>
                    </div>
                    <div class="estatistica-icone"><i class="fas fa-car"></i></div>
                </div>
                
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3><i class="fas fa-money-bill-wave"></i> Total Gasto</h3>
                        <div class="estatistica-numero">MZN <?= number_format($estatisticas['total_gasto'], 2) ?></div>
                    </div>
                    <div class="estatistica-icone"><i class="fas fa-money-bill-wave"></i></div>
                </div>
            </div>
            
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo"><i class="fas fa-history"></i> Minhas Reservas Recentes</h3>
                    <a href="reservas.php" class="btn btn-primario"><i class="fas fa-eye"></i> Ver Todas</a>
                </div>
                
                <?php if(count($reservas_recentes) > 0): ?>
                <div class="container-tabela">
                    <table class="tabela">
                        <thead>
                            <tr>
                                <th><i class="fas fa-car"></i> Viatura</th>
                                <th><i class="fas fa-calendar"></i> Datas</th>
                                <th><i class="fas fa-coins"></i> Total</th>
                                <th><i class="fas fa-chart-line"></i> Status</th>
                                <th><i class="fas fa-cogs"></i> Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($reservas_recentes as $reserva): ?>
                            <tr>
                                <td><i class="fas fa-car-side"></i> <?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?></td>
                                <td><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($reserva['data_inicio'])) ?> - <?= date('d/m/Y', strtotime($reserva['data_fim'])) ?></td>
                                <td><i class="fas fa-money-bill-wave"></i> MZN <?= number_format($reserva['preco_total'], 2) ?></td>
                                <td>
                                    <span class="etiqueta etiqueta-<?= $reserva['status'] == 'pendente' ? 'aviso' : ($reserva['status'] == 'confirmada' ? 'sucesso' : 'perigo') ?>">
                                        <?php if($reserva['status'] == 'pendente'): ?>
                                            <i class="fas fa-clock"></i>
                                        <?php elseif($reserva['status'] == 'confirmada'): ?>
                                            <i class="fas fa-check-circle"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times-circle"></i>
                                        <?php endif; ?>
                                        <?= ucfirst($reserva['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($reserva['status'] == 'pendente'): ?>
                                        <button class="btn btn-perigo btn-sm" onclick="cancelarReserva(<?= $reserva['id'] ?>)">
                                            <i class="fas fa-times"></i> Cancelar
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 2rem; color: #999;">
                    <i class="fas fa-inbox" style="font-size: 3rem;"></i>
                    <p>Nenhuma reserva encontrada.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function cancelarReserva(id) {
            if(confirm('Tem certeza que deseja cancelar esta reserva?')) {
                window.location.href = `cancelar_reserva.php?id=${id}`;
            }
        }
    </script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/cliente.js"></script>
</body>
</html>