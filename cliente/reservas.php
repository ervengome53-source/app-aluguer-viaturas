<?php
session_start();
require_once '../config/auth.php';
Auth::cargo(['cliente']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$utilizador = Auth::utilizador();

// Mostrar mensagem de sucesso se existir
$mensagem_sucesso = $_SESSION['mensagem_sucesso'] ?? '';
unset($_SESSION['mensagem_sucesso']);

// Buscar reservas do cliente com informação de pagamento (CORRIGIDO)
$query = "SELECT r.*, v.marca, v.modelo, v.imagem, v.preco_dia,
          CASE 
              WHEN EXISTS (SELECT 1 FROM pagamentos p WHERE p.reserva_id = r.id AND p.estado IN ('confirmado', 'pendente')) 
              THEN 1 
              ELSE 0 
          END as tem_pagamento
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Reservas - SIGAV</title>
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
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .btn-pagamento:hover { background: #2a5298; }
        .status-pendente { background: #ffc107; color: #333; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.7rem; display: inline-flex; align-items: center; gap: 0.3rem; }
        .status-confirmada { background: #28a745; color: white; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.7rem; display: inline-flex; align-items: center; gap: 0.3rem; }
        .status-cancelada { background: #dc3545; color: white; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.7rem; display: inline-flex; align-items: center; gap: 0.3rem; }
        .etiqueta-sucesso { background: #28a745; color: white; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.7rem; display: inline-flex; align-items: center; gap: 0.3rem; }
        .texto-centro { text-align: center; }
        .badge-pago {
            background: #28a745;
            color: white;
            padding: 0.2rem 0.8rem;
            border-radius: 20px;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .mensagem-flutuante {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #28a745;
        }
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <?php if($mensagem_sucesso): ?>
            <div class="mensagem-flutuante">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($mensagem_sucesso) ?>
            </div>
            <?php endif; ?>
            
            <div class="grade-estatisticas">
                <div class="cartao-estatistica">
                    <h3><i class="fas fa-clock"></i> Pendentes</h3>
                    <div class="estatistica-numero" style="font-size: 2rem;"><?= $stats['pendentes'] ?? 0 ?></div>
                </div>
                <div class="cartao-estatistica">
                    <h3><i class="fas fa-check-circle"></i> Confirmadas</h3>
                    <div class="estatistica-numero" style="font-size: 2rem;"><?= $stats['confirmadas'] ?? 0 ?></div>
                </div>
                <div class="cartao-estatistica">
                    <h3><i class="fas fa-times-circle"></i> Canceladas</h3>
                    <div class="estatistica-numero" style="font-size: 2rem;"><?= $stats['canceladas'] ?? 0 ?></div>
                </div>
            </div>
            
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo"><i class="fas fa-bookmark"></i> Minhas Reservas</h3>
                    <a href="viaturas.php" class="btn btn-primario"><i class="fas fa-plus"></i> Nova Reserva</a>
                </div>
                
                <?php if(count($reservas) > 0): ?>
                <div class="container-tabela">
                    <table class="tabela">
                        <thead>
                            <tr>
                                <th><i class="fas fa-car"></i> Viatura</th>
                                <th><i class="fas fa-calendar"></i> Período</th>
                                <th><i class="fas fa-sun"></i> Dias</th>
                                <th><i class="fas fa-coins"></i> Valor</th>
                                <th><i class="fas fa-chart-line"></i> Status</th>
                                <th><i class="fas fa-cogs"></i> Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($reservas as $reserva): ?>
                            <tr>
                                <td><i class="fas fa-car-side"></i> <strong><?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?></strong></td>
                                <td><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($reserva['data_inicio'])) ?><br><small>até <?= date('d/m/Y', strtotime($reserva['data_fim'])) ?></small></td>
                                <td><?= $reserva['total_dias'] ?> dias</small></td>
                                <td><i class="fas fa-money-bill-wave"></i> MZN <?= number_format($reserva['preco_total'], 2) ?></td>
                                <td>
                                    <?php if($reserva['status'] == 'pendente'): ?>
                                        <span class="status-pendente"><i class="fas fa-clock"></i> Pendente</span>
                                    <?php elseif($reserva['status'] == 'confirmada'): ?>
                                        <span class="status-confirmada"><i class="fas fa-check-circle"></i> Confirmada</span>
                                    <?php else: ?>
                                        <span class="status-cancelada"><i class="fas fa-times-circle"></i> Cancelada</span>
                                    <?php endif; ?>
                                </td>
                                <td class="tabela-acoes">
                                    <?php if($reserva['status'] == 'pendente'): ?>
                                        <button class="btn btn-perigo btn-sm" onclick="cancelarReserva(<?= $reserva['id'] ?>)">
                                            <i class="fas fa-times"></i> Cancelar
                                        </button>
                                    <?php elseif($reserva['status'] == 'confirmada'): ?>
                                        <?php if($reserva['tem_pagamento'] == 0): ?>
                                            <a href="pagamentos.php?reserva_id=<?= $reserva['id'] ?>" class="btn-pagamento">
                                                <i class="fas fa-credit-card"></i> Pagar Agora
                                            </a>
                                        <?php else: ?>
                                            <span class="badge-pago">
                                                <i class="fas fa-check-circle"></i> Já Pago
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="texto-centro" style="padding: 3rem;">
                    <i class="fas fa-calendar-times" style="font-size: 3rem; color: #ccc;"></i>
                    <h3>Nenhuma reserva encontrada</h3>
                    <p>Comece a reservar o seu veículo ideal!</p>
                    <a href="viaturas.php" class="btn btn-primario"><i class="fas fa-search"></i> Ver Viaturas</a>
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