<?php
// controllers/ClienteController.php
require_once '../config/database.php';
require_once '../config/auth.php';

header('Content-Type: application/json');
session_start();

$database = new Database();
$db = $database->getConnection();
Auth::init($db);

if(!isset($_SESSION['utilizador_id'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Não autenticado']);
    exit();
}

$acao = $_GET['acao'] ?? '';

switch($acao) {
    case 'reservar':
        $dados = json_decode(file_get_contents('php://input'), true);
        
        $data_inicio = $dados['data_inicio'];
        $data_fim = $dados['data_fim'];
        $veiculo_id = $dados['veiculo_id'];
        $utilizador_id = $_SESSION['utilizador_id'];
        
        // Calcular dias e preço
        $inicio = new DateTime($data_inicio);
        $fim = new DateTime($data_fim);
        $dias = $inicio->diff($fim)->days + 1;
        
        // Buscar preço do veículo
        $query = "SELECT preco_dia FROM viaturas WHERE id = :veiculo_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':veiculo_id', $veiculo_id);
        $stmt->execute();
        $veiculo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $preco_total = $veiculo['preco_dia'] * $dias;
        
        // Verificar disponibilidade
        $verificar = "SELECT id FROM reservas WHERE viatura_id = :veiculo_id 
                      AND status IN ('pendente', 'confirmada')
                      AND ((data_inicio BETWEEN :data_inicio AND :data_fim) 
                      OR (data_fim BETWEEN :data_inicio AND :data_fim))";
        $stmt = $db->prepare($verificar);
        $stmt->bindParam(':veiculo_id', $veiculo_id);
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Veículo não disponível para estas datas']);
            exit();
        }
        
        // Criar reserva
        $query = "INSERT INTO reservas (utilizador_id, viatura_id, data_inicio, data_fim, total_dias, preco_total) 
                  VALUES (:utilizador_id, :veiculo_id, :data_inicio, :data_fim, :dias, :preco_total)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':utilizador_id', $utilizador_id);
        $stmt->bindParam(':veiculo_id', $veiculo_id);
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->bindParam(':dias', $dias);
        $stmt->bindParam(':preco_total', $preco_total);
        
        if($stmt->execute()) {
            echo json_encode(['sucesso' => true, 'mensagem' => 'Reserva realizada com sucesso']);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao criar reserva']);
        }
        break;
        
    case 'cancelar':
        $dados = json_decode(file_get_contents('php://input'), true);
        $reserva_id = $dados['reserva_id'];
        
        $query = "UPDATE reservas SET status = 'cancelada' 
                  WHERE id = :id AND utilizador_id = :utilizador_id AND status = 'pendente'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $reserva_id);
        $stmt->bindParam(':utilizador_id', $_SESSION['utilizador_id']);
        
        if($stmt->execute()) {
            echo json_encode(['sucesso' => true, 'mensagem' => 'Reserva cancelada']);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao cancelar reserva']);
        }
        break;
        
    default:
        echo json_encode(['sucesso' => false, 'mensagem' => 'Ação não encontrada']);
}
?>