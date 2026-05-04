<?php
// api/reservas.php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$acao = $_GET['acao'] ?? '';

switch($method) {
    case 'GET':
        if($acao == 'pendentes') {
            $query = "SELECT r.*, u.nome as cliente_nome, u.email as cliente_email, v.marca, v.modelo 
                      FROM reservas r
                      JOIN utilizadores u ON r.utilizador_id = u.id
                      JOIN viaturas v ON r.viatura_id = v.id
                      WHERE r.status = 'pendente'
                      ORDER BY r.criado_em ASC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['sucesso' => true, 'dados' => $reservas]);
        }
        elseif($acao == 'minhas' && isset($_SESSION['utilizador_id'])) {
            $user_id = $_SESSION['utilizador_id'];
            $query = "SELECT r.*, v.marca, v.modelo, v.imagem 
                      FROM reservas r
                      JOIN viaturas v ON r.viatura_id = v.id
                      WHERE r.utilizador_id = :user_id
                      ORDER BY r.criado_em DESC";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['sucesso' => true, 'dados' => $reservas]);
        }
        elseif($acao == 'por_cliente' && isset($_GET['cliente_id'])) {
            $cliente_id = $_GET['cliente_id'];
            $status = $_GET['status'] ?? 'confirmada';
            $query = "SELECT r.*, v.marca, v.modelo, v.preco_dia
                      FROM reservas r
                      JOIN viaturas v ON r.viatura_id = v.id
                      WHERE r.utilizador_id = :cliente_id AND r.status = :status
                      ORDER BY r.data_inicio ASC";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':cliente_id', $cliente_id);
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['sucesso' => true, 'dados' => $reservas]);
        }
        break;
        
    case 'POST':
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if($acao == 'criar' && isset($_SESSION['utilizador_id'])) {
            $user_id = $_SESSION['utilizador_id'];
            $veiculo_id = $dados['veiculo_id'];
            $data_inicio = $dados['data_inicio'];
            $data_fim = $dados['data_fim'];
            
            // Calcular dias
            $inicio = new DateTime($data_inicio);
            $fim = new DateTime($data_fim);
            $dias = $inicio->diff($fim)->days + 1;
            
            // Buscar preço
            $query = "SELECT preco_dia FROM viaturas WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $veiculo_id);
            $stmt->execute();
            $viatura = $stmt->fetch(PDO::FETCH_ASSOC);
            $preco_total = $viatura['preco_dia'] * $dias;
            
            $query = "INSERT INTO reservas (utilizador_id, viatura_id, data_inicio, data_fim, total_dias, preco_total) 
                      VALUES (:user_id, :veiculo_id, :data_inicio, :data_fim, :dias, :preco_total)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':veiculo_id', $veiculo_id);
            $stmt->bindParam(':data_inicio', $data_inicio);
            $stmt->bindParam(':data_fim', $data_fim);
            $stmt->bindParam(':dias', $dias);
            $stmt->bindParam(':preco_total', $preco_total);
            
            echo json_encode(['sucesso' => $stmt->execute()]);
        }
        elseif($acao == 'confirmar') {
            $reserva_id = $dados['reserva_id'];
            $query = "UPDATE reservas SET status = 'confirmada' WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $reserva_id);
            echo json_encode(['sucesso' => $stmt->execute()]);
        }
        elseif($acao == 'cancelar') {
            $reserva_id = $dados['reserva_id'];
            $query = "UPDATE reservas SET status = 'cancelada' WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $reserva_id);
            echo json_encode(['sucesso' => $stmt->execute()]);
        }
        break;
        
    default:
        echo json_encode(['sucesso' => false, 'mensagem' => 'Método não suportado']);
}
?>