<?php
require_once '../config/auth.php';
Auth::cargo(['cliente']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$utilizador = Auth::utilizador();

header('Content-Type: application/json');

$reserva_id = $_POST['reserva_id'] ?? 0;
$metodo = $_POST['metodo_pagamento'] ?? '';
$observacoes = $_POST['observacoes'] ?? '';

if(!$reserva_id || !$metodo) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados incompletos']);
    exit();
}

// Verificar se a reserva existe e pertence ao cliente
$query = "SELECT * FROM reservas WHERE id = :id AND utilizador_id = :user_id AND status = 'confirmada'";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $reserva_id);
$stmt->bindParam(':user_id', $utilizador['id']);
$stmt->execute();
$reserva = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$reserva) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Reserva não encontrada']);
    exit();
}

// Verificar se já existe pagamento
$query = "SELECT id FROM pagamentos WHERE reserva_id = :reserva_id AND estado = 'confirmado'";
$stmt = $db->prepare($query);
$stmt->bindParam(':reserva_id', $reserva_id);
$stmt->execute();

if($stmt->rowCount() > 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Esta reserva já foi paga']);
    exit();
}

// Registrar pagamento
$referencia = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

$query = "INSERT INTO pagamentos (utilizador_id, reserva_id, valor, metodo_pagamento, referencia_pagamento, estado, dados_transacao) 
          VALUES (:user_id, :reserva_id, :valor, :metodo, :referencia, 'pendente', :observacoes)";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $utilizador['id']);
$stmt->bindParam(':reserva_id', $reserva_id);
$stmt->bindParam(':valor', $reserva['preco_total']);
$stmt->bindParam(':metodo', $metodo);
$stmt->bindParam(':referencia', $referencia);
$stmt->bindParam(':observacoes', $observacoes);

if($stmt->execute()) {
    echo json_encode([
        'sucesso' => true, 
        'mensagem' => 'Pagamento registado! Aguarde confirmação do funcionário.',
        'referencia' => $referencia
    ]);
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao processar pagamento']);
}
?>