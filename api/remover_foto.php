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

// Buscar caminho da imagem
$query = "SELECT imagem_path FROM viaturas_imagens WHERE id = :id AND viatura_id = :viatura_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $foto_id);
$stmt->bindParam(':viatura_id', $viatura_id);
$stmt->execute();
$foto = $stmt->fetch(PDO::FETCH_ASSOC);

if($foto) {
    // Remover arquivo do servidor
    $caminho_arquivo = '../' . $foto['imagem_path'];
    if(file_exists($caminho_arquivo)) {
        unlink($caminho_arquivo);
    }
    
    // Remover do banco
    $query = "DELETE FROM viaturas_imagens WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $foto_id);
    
    if($stmt->execute()) {
        // Se a foto removida era capa, definir outra como capa
        $query = "SELECT id FROM viaturas_imagens WHERE viatura_id = :viatura_id ORDER BY ordem ASC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':viatura_id', $viatura_id);
        $stmt->execute();
        $primeira = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($primeira) {
            $query = "UPDATE viaturas_imagens SET is_capa = 1 WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $primeira['id']);
            $stmt->execute();
        }
        
        echo json_encode(['sucesso' => true, 'mensagem' => 'Foto removida com sucesso']);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao remover do banco']);
    }
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Foto não encontrada']);
}
?>