<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

if(!isset($_SESSION['utilizador_id']) || $_SESSION['utilizador_cargo'] !== 'admin') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$foto_id = $data['foto_id'] ?? 0;
$viatura_id = $data['viatura_id'] ?? 0;

if(!$foto_id || !$viatura_id) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados incompletos']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Remover capa de todas as fotos da viatura
$query = "UPDATE viaturas_imagens SET is_capa = 0 WHERE viatura_id = :viatura_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':viatura_id', $viatura_id);
$stmt->execute();

// Definir nova capa
$query = "UPDATE viaturas_imagens SET is_capa = 1 WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $foto_id);

if($stmt->execute()) {
    echo json_encode(['sucesso' => true, 'mensagem' => 'Capa definida com sucesso']);
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao definir capa']);
}
?>