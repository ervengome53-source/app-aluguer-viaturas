<?php
require_once '../config/auth.php';
Auth::cargo(['cliente']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$utilizador = Auth::utilizador();

// Processar criação de reserva via POST
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['veiculo_id'])) {
    $veiculo_id = $_POST['veiculo_id'];
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    
    // Calcular dias
    $inicio = new DateTime($data_inicio);
    $fim = new DateTime($data_fim);
    $dias = $inicio->diff($fim)->days + 1;
    
    // Buscar preço do veículo
    $query = "SELECT preco_dia FROM viaturas WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $veiculo_id);
    $stmt->execute();
    $viatura = $stmt->fetch(PDO::FETCH_ASSOC);
    $preco_total = $viatura['preco_dia'] * $dias;
    
    // Inserir reserva
    $query = "INSERT INTO reservas (utilizador_id, viatura_id, data_inicio, data_fim, total_dias, preco_total) 
              VALUES (:user_id, :veiculo_id, :data_inicio, :data_fim, :dias, :preco)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $utilizador['id']);
    $stmt->bindParam(':veiculo_id', $veiculo_id);
    $stmt->bindParam(':data_inicio', $data_inicio);
    $stmt->bindParam(':data_fim', $data_fim);
    $stmt->bindParam(':dias', $dias);
    $stmt->bindParam(':preco', $preco_total);
    
    if($stmt->execute()) {
        $_SESSION['mensagem'] = 'Reserva realizada com sucesso!';
        $_SESSION['mensagem_tipo'] = 'sucesso';
    } else {
        $_SESSION['mensagem'] = 'Erro ao realizar reserva. Tente novamente.';
        $_SESSION['mensagem_tipo'] = 'erro';
    }
    
    header('Location: reservas.php');
    exit();
}

// Buscar reservas do cliente
$query = "SELECT r.*, v.marca, v.modelo, v.imagem 
          FROM reservas r 
          JOIN viaturas v ON r.viatura_id = v.id 
          WHERE r.utilizador_id = :user_id 
          ORDER BY r.criado_em DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $utilizador['id']);
$stmt->execute();
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mensagem = $_SESSION['mensagem'] ?? '';
$mensagem_tipo = $_SESSION['mensagem_tipo'] ?? '';
unset($_SESSION['mensagem']);
unset($_SESSION['mensagem_tipo']);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Reservas</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <style>
        .notificacao {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }
        .notificacao.sucesso {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .notificacao.erro {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <?php if($mensagem): ?>
                <div class="notificacao <?= $mensagem_tipo ?>">
                    <?= htmlspecialchars($mensagem) ?>
                </div>
            <?php endif; ?>
            
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo"> Minhas Reservas</h3>
                    <a href="viaturas.php" class="btn btn-primario">+ Nova Reserva</a>
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
                                <td><?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?></td>
                                <td><?= date('d/m/Y', strtotime($reserva['data_inicio'])) ?> - <?= date('d/m/Y', strtotime($reserva['data_fim'])) ?></td>
                                <td><?= $reserva['total_dias'] ?></td>
                                <td>MZN <?= number_format($reserva['preco_total'], 2) ?></td>
                                <td>
                                    <span class="etiqueta etiqueta-<?= $reserva['status'] == 'pendente' ? 'aviso' : 'sucesso' ?>">
                                        <?= ucfirst($reserva['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($reserva['status'] == 'pendente'): ?>
                                        <button class="btn btn-perigo btn-sm" onclick="cancelarReserva(<?= $reserva['id'] ?>)">
                                            Cancelar
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="texto-centro" style="padding: 3rem; text-align: center;">
                    <p>Nenhuma reserva encontrada.</p>
                    <a href="viaturas.php" class="btn btn-primario">Ver Viaturas</a>
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