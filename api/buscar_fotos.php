<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

$viatura_id = $_GET['viatura_id'] ?? 0;

if(!$viatura_id) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID da viatura não informado']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$query = "SELECT id, imagem_path, is_capa FROM viaturas_imagens 
          WHERE viatura_id = :viatura_id 
          ORDER BY is_capa DESC, ordem ASC, id ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':viatura_id', $viatura_id);
$stmt->execute();
$fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'sucesso' => true,
    'fotos' => $fotos,
    'total' => count($fotos)
]);
?>