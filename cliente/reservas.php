<?php
require_once '../config/auth.php';
Auth::cargo(['cliente']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$utilizador = Auth::utilizador();

// Buscar reservas do cliente com informação de pagamento
$query = "SELECT r.*, v.marca, v.modelo, v.imagem, v.preco_dia,
          (SELECT COUNT(*) FROM pagamentos WHERE reserva_id = r.id AND estado = 'confirmado') as tem_pagamento
          FROM reservas r 
          JOIN viaturas v ON r.viatura_id = v.id 
          WHERE r.utilizador_id = :utilizador_id 
          ORDER BY r.criado_em DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':utilizador_id', $utilizador['id']);
$stmt->execute();
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$query = "SELECT 
          COUNT(CASE WHEN status = 'pendente' THEN 1 END) as pendentes,
          COUNT(CASE WHEN status = 'confirmada' THEN 1 END) as confirmadas,
          COUNT(CASE WHEN status = 'cancelada' THEN 1 END) as canceladas
          FROM reservas WHERE utilizador_id = :utilizador_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':utilizador_id', $utilizador['id']);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Reservas - RentCar</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="../assets/css/cliente.css">
    <style>
        .grade-estatisticas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .cartao-estatistica {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .btn-pagamento {
            background: #1E3A5F;
            color: white;
            padding: 0.3rem 0.8rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.75rem;
            text-decoration: none;
        }
        
        .btn-pagamento:hover {
            background: #2a5298;
        }
        
        .status-pendente {
            background: #ffc107;
            color: #333;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
        }
        
        .status-confirmada {
            background: #28a745;
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
        }
        
        .status-cancelada {
            background: #dc3545;
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
        }
        
        .etiqueta-sucesso {
            background: #28a745;
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <div class="grade-estatisticas">
                <div class="cartao-estatistica">
                    <h3>Pendentes</h3>
                    <div class="estatistica-numero" style="font-size: 2rem;"><?= $stats['pendentes'] ?? 0 ?></div>
                </div>
                <div class="cartao-estatistica">
                    <h3>Confirmadas</h3>
                    <div class="estatistica-numero" style="font-size: 2rem;"><?= $stats['confirmadas'] ?? 0 ?></div>
                </div>
                <div class="cartao-estatistica">
                    <h3>Canceladas</h3>
                    <div class="estatistica-numero" style="font-size: 2rem;"><?= $stats['canceladas'] ?? 0 ?></div>
                </div>
            </div>
            
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo">Minhas Reservas</h3>
                    <a href="viaturas.php" class="btn btn-primario" style="background: #1E3A5F;">+ Nova Reserva</a>
                </div>
                
                <?php if(count($reservas) > 0): ?>
                <div class="container-tabela">
                    <table class="tabela">
                        <thead>
                            <tr>
                                <th>Viatura</th>
                                <th>Período</th>
                                <th>Dias</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($reservas as $reserva): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?></strong>
                                 </td>
                                <td>
                                    <?= date('d/m/Y', strtotime($reserva['data_inicio'])) ?><br>
                                    <small>até <?= date('d/m/Y', strtotime($reserva['data_fim'])) ?></small>
                                 </td>
                                <td><?= $reserva['total_dias'] ?> dias</td>
                                <td>MZN <?= number_format($reserva['preco_total'], 2) ?></td>
                                <td>
                                    <?php if($reserva['status'] == 'pendente'): ?>
                                        <span class="status-pendente">Pendente</span>
                                    <?php elseif($reserva['status'] == 'confirmada'): ?>
                                        <span class="status-confirmada">Confirmada</span>
                                    <?php else: ?>
                                        <span class="status-cancelada">Cancelada</span>
                                    <?php endif; ?>
                                </td>
                                <td class="tabela-acoes">
                                    <?php if($reserva['status'] == 'pendente'): ?>
                                        <button class="btn btn-perigo btn-sm" onclick="cancelarReserva(<?= $reserva['id'] ?>)">
                                            Cancelar
                                        </button>
                                    <?php elseif($reserva['status'] == 'confirmada'): ?>
                                        <?php if($reserva['tem_pagamento'] == 0): ?>
                                            <a href="pagamentos.php?reserva_id=<?= $reserva['id'] ?>" class="btn-pagamento">
                                                Pagar Agora
                                            </a>
                                        <?php else: ?>
                                            <span class="etiqueta-sucesso">Pago</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </table>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="texto-centro" style="padding: 3rem;">
                    <div style="font-size: 3rem;"></div>
                    <h3>Nenhuma reserva encontrada</h3>
                    <p>Comece a reservar o seu veículo ideal!</p>
                    <a href="viaturas.php" class="btn btn-primario" style="background: #1E3A5F; margin-top: 1rem;">Ver Viaturas</a>
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