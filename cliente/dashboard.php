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
                        <h3>Reservas Ativas</h3>
                        <div class="estatistica-numero"> <?= $estatisticas['reservas_ativas'] ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
                
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Total Aluguéis</h3>
                        <div class="estatistica-numero"> <?= $estatisticas['total_alugueis'] ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
                
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Total Gasto</h3>
                        <div class="estatistica-numero"> MZN <?= number_format($estatisticas['total_gasto'], 2) ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
            </div>
            
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo">Minhas Reservas Recentes</h3>
                    <a href="reservas.php" class="btn btn-primario">Ver Todas</a>
                </div>
                
                <?php if(count($reservas_recentes) > 0): ?>
                <div class="container-tabela">
                    <table class="tabela">
                        <thead>
                            <tr>
                                <th>Viatura</th>
                                <th>Datas</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($reservas_recentes as $reserva): ?>
                            <tr>
                                <td><?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?></td>
                                <td><?= date('d/m/Y', strtotime($reserva['data_inicio'])) ?> - <?= date('d/m/Y', strtotime($reserva['data_fim'])) ?></td>
                                <td> MZN <?= number_format($reserva['preco_total'], 2) ?></td>
                                <td>
                                    <span class="etiqueta etiqueta-<?= $reserva['status'] == 'pendente' ? 'aviso' : ($reserva['status'] == 'confirmada' ? 'sucesso' : 'perigo') ?>">
                                        <?= ucfirst($reserva['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($reserva['status'] == 'pendente'): ?>
                                        <button class="btn btn-perigo btn-sm" onclick="cancelarReserva(<?= $reserva['id'] ?>)">Cancelar</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 2rem; color: #999;">
                    Nenhuma reserva encontrada.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/cliente.js"></script>
</body>
</html>