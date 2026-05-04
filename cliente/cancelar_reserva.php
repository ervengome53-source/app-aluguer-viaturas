<?php
require_once '../config/auth.php';
Auth::cargo(['cliente']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$utilizador = Auth::utilizador();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id > 0) {
    // Verificar se a reserva pertence ao cliente e está pendente
    $query = "UPDATE reservas SET status = 'cancelada' 
              WHERE id = :id AND utilizador_id = :user_id AND status = 'pendente'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':user_id', $utilizador['id']);
    
    if($stmt->execute()) {
        $_SESSION['mensagem'] = 'Reserva cancelada com sucesso!';
        $_SESSION['mensagem_tipo'] = 'sucesso';
    } else {
        $_SESSION['mensagem'] = 'Erro ao cancelar reserva.';
        $_SESSION['mensagem_tipo'] = 'erro';
    }
}

header('Location: reservas.php');
exit();
?>