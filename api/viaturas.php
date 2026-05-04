<?php
// api/viaturas.php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$acao = $_GET['acao'] ?? '';

switch($method) {
    case 'GET':
        if($acao == 'disponiveis') {
            $query = "SELECT * FROM viaturas WHERE status = 'disponivel' ORDER BY preco_dia ASC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $viaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['sucesso' => true, 'dados' => $viaturas]);
        } 
        elseif(isset($_GET['id'])) {
            $id = $_GET['id'];
            $query = "SELECT * FROM viaturas WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $viatura = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['sucesso' => true, 'dados' => $viatura]);
        }
        else {
            $query = "SELECT * FROM viaturas ORDER BY criado_em DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $viaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['sucesso' => true, 'dados' => $viaturas]);
        }
        break;
        
    case 'POST':
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if($acao == 'criar') {
            $query = "INSERT INTO viaturas (modelo, marca, ano, matricula, preco_dia, tipo, combustivel, transmissao, lugares, descricao) 
                      VALUES (:modelo, :marca, :ano, :matricula, :preco_dia, :tipo, :combustivel, :transmissao, :lugares, :descricao)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':modelo', $dados['modelo']);
            $stmt->bindParam(':marca', $dados['marca']);
            $stmt->bindParam(':ano', $dados['ano']);
            $stmt->bindParam(':matricula', $dados['matricula']);
            $stmt->bindParam(':preco_dia', $dados['preco_dia']);
            $stmt->bindParam(':tipo', $dados['tipo']);
            $stmt->bindParam(':combustivel', $dados['combustivel']);
            $stmt->bindParam(':transmissao', $dados['transmissao']);
            $stmt->bindParam(':lugares', $dados['lugares']);
            $stmt->bindParam(':descricao', $dados['descricao']);
            
            if($stmt->execute()) {
                echo json_encode(['sucesso' => true, 'mensagem' => 'Viatura criada', 'id' => $db->lastInsertId()]);
            } else {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao criar']);
            }
        } 
        elseif($acao == 'editar') {
            $query = "UPDATE viaturas SET modelo = :modelo, marca = :marca, ano = :ano, matricula = :matricula, 
                      preco_dia = :preco_dia, tipo = :tipo, combustivel = :combustivel, transmissao = :transmissao, 
                      lugares = :lugares, descricao = :descricao, status = :status WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':modelo', $dados['modelo']);
            $stmt->bindParam(':marca', $dados['marca']);
            $stmt->bindParam(':ano', $dados['ano']);
            $stmt->bindParam(':matricula', $dados['matricula']);
            $stmt->bindParam(':preco_dia', $dados['preco_dia']);
            $stmt->bindParam(':tipo', $dados['tipo']);
            $stmt->bindParam(':combustivel', $dados['combustivel']);
            $stmt->bindParam(':transmissao', $dados['transmissao']);
            $stmt->bindParam(':lugares', $dados['lugares']);
            $stmt->bindParam(':descricao', $dados['descricao']);
            $stmt->bindParam(':status', $dados['status']);
            $stmt->bindParam(':id', $dados['id']);
            
            echo json_encode(['sucesso' => $stmt->execute()]);
        }
        break;
        
    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        $query = "DELETE FROM viaturas WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        echo json_encode(['sucesso' => $stmt->execute()]);
        break;
        
    default:
        echo json_encode(['sucesso' => false, 'mensagem' => 'Método não suportado']);
}
?>