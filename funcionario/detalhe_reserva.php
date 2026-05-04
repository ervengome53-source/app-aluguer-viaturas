<?php
require_once '../config/auth.php';
Auth::cargo(['funcionario']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Buscar detalhes da reserva
$query = "SELECT r.*, 
          u.nome as cliente_nome, u.email as cliente_email, u.telefone as cliente_telefone,
          v.marca, v.modelo, v.matricula, v.preco_dia
          FROM reservas r
          JOIN utilizadores u ON r.utilizador_id = u.id
          JOIN viaturas v ON r.viatura_id = v.id
          WHERE r.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$reserva = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$reserva) {
    header('Location: reservas.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Reserva - Funcionário</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7fa; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #1E3A5F; }
        .detalhe { margin: 15px 0; padding: 10px; background: #f8f9fa; border-radius: 8px; }
        .label { font-weight: bold; width: 150px; display: inline-block; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn-primary { background: #1E3A5F; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-primary:hover { background: #FF8C00; }
        .btn-success:hover { background: #218838; }
        .btn-danger:hover { background: #c82333; }
    </style>
</head>
<body>
    <div class="container">
        <h1> Detalhes da Reserva 	<?= $reserva['id'] ?></h1>
        
        <div class="detalhe">
            <strong>Cliente:</strong> <?= htmlspecialchars($reserva['cliente_nome']) ?><br>
            <strong>Email:</strong> <?= htmlspecialchars($reserva['cliente_email']) ?><br>
            <strong>Telefone:</strong> <?= htmlspecialchars($reserva['cliente_telefone'] ?? 'Não informado') ?>
        </div>
        
        <div class="detalhe">
            <strong>Viatura:</strong> <?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?><br>
            <strong>Matrícula:</strong> <?= htmlspecialchars($reserva['matricula']) ?><br>
            <strong>Preço por dia:</strong> Mts <?= number_format($reserva['preco_dia'], 2) ?>
        </div>
        
        <div class="detalhe">
            <strong>Data de Início:</strong> <?= date('d/m/Y', strtotime($reserva['data_inicio'])) ?><br>
            <strong>Data de Fim:</strong> <?= date('d/m/Y', strtotime($reserva['data_fim'])) ?><br>
            <strong>Total de Dias:</strong> <?= $reserva['total_dias'] ?> dias<br>
            <strong>Valor Total:</strong> MZN <?= number_format($reserva['preco_total'], 2) ?>
        </div>
        
        <div class="detalhe">
            <strong>Status:</strong> 
            <span style="color: <?= $reserva['status'] == 'pendente' ? 'orange' : 'green' ?>">
                <?= ucfirst($reserva['status']) ?>
            </span><br>
            <strong>Data da Reserva:</strong> <?= date('d/m/Y H:i', strtotime($reserva['criado_em'])) ?>
        </div>
        
        <div style="margin-top: 20px;">
            <?php if($reserva['status'] == 'pendente'): ?>
                <a href="reservas.php?confirmar=<?= $reserva['id'] ?>" class="btn btn-success"> Confirmar Reserva</a>
                <a href="reservas.php?rejeitar=<?= $reserva['id'] ?>" class="btn btn-danger"> Rejeitar Reserva</a>
            <?php endif; ?>
            <a href="reservas.php" class="btn btn-secondary"> Voltar</a>
        </div>
    </div>
</body>
</html>