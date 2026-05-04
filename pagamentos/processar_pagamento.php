<?php
// pagamentos/processar_pagamento.php
require_once '../config/database.php';
require_once '../config/auth.php';

header('Content-Type: application/json');
session_start();

if(!isset($_SESSION['utilizador_id'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Utilizador não autenticado']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$utilizador_id = $_SESSION['utilizador_id'];

$dados = json_decode(file_get_contents('php://input'), true);

$metodo = $dados['metodo'];
$reserva_id = $dados['reserva_id'] ?? null;
$aluguer_id = $dados['aluguer_id'] ?? null;
$valor_base = $dados['valor_base'];

// Calcular valores com taxas
$iva = $valor_base * 0.23;
$taxa_servico = $valor_base * 0.05;
$valor_total = $valor_base + $iva + $taxa_servico;

try {
    $db->beginTransaction();
    
    // Criar registo de pagamento
    $referencia_pagamento = gerarReferenciaPagamento();
    
    $query = "INSERT INTO pagamentos (utilizador_id, reserva_id, aluguer_id, valor, metodo_pagamento, 
              referencia_pagamento, dados_transacao, estado) 
              VALUES (:utilizador_id, :reserva_id, :aluguer_id, :valor, :metodo, :referencia, :dados, 'pendente')";
    
    $dados_transacao = json_encode([
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'timestamp' => date('Y-m-d H:i:s'),
        'dados_cliente' => $dados['dados_especificos'] ?? []
    ]);
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':utilizador_id', $utilizador_id);
    $stmt->bindParam(':reserva_id', $reserva_id);
    $stmt->bindParam(':aluguer_id', $aluguer_id);
    $stmt->bindParam(':valor', $valor_total);
    $stmt->bindParam(':metodo', $metodo);
    $stmt->bindParam(':referencia', $referencia_pagamento);
    $stmt->bindParam(':dados', $dados_transacao);
    $stmt->execute();
    
    $pagamento_id = $db->lastInsertId();
    
    // Processar conforme método de pagamento
    $resposta = ['sucesso' => true, 'pagamento_id' => $pagamento_id];
    
    switch($metodo) {
        case 'dinheiro':
            // Pagamento em dinheiro - aguarda confirmação do funcionário
            $resposta['mensagem'] = 'Pagamento registado. Aguarde confirmação do funcionário.';
            $resposta['redirect'] = '../cliente/pagamentos.php';
            break;
            
        case 'cartao':
            // Integração com gateway de pagamento (exemplo simulado)
            $resultado_gateway = processarPagamentoCartao($dados['dados_especificos'], $valor_total);
            if($resultado_gateway['sucesso']) {
                $query = "UPDATE pagamentos SET estado = 'confirmado', data_pagamento = NOW() WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $pagamento_id);
                $stmt->execute();
                $resposta['mensagem'] = 'Pagamento com cartão confirmado!';
                $resposta['redirect'] = '../cliente/pagamentos.php';
            } else {
                $resposta['sucesso'] = false;
                $resposta['mensagem'] = $resultado_gateway['mensagem'];
            }
            break;
            
        case 'mbway':
            // Gerar código MB WAY
            $codigo_mbway = gerarCodigoMBWAY();
            $query = "UPDATE pagamentos SET referencia_pagamento = :codigo WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':codigo', $codigo_mbway);
            $stmt->bindParam(':id', $pagamento_id);
            $stmt->execute();
            $resposta['referencia'] = $codigo_mbway;
            $resposta['mensagem'] = 'Código MB WAY gerado. Confirme na app.';
            break;
            
        case 'transferencia':
            $resposta['mensagem'] = 'Transferência registada. Aguardamos comprovativo.';
            $resposta['redirect'] = '../cliente/pagamentos.php';
            break;
    }
    
    // Se reserva, atualizar status
    if($reserva_id) {
        $query = "UPDATE reservas SET pagamento_status = 'pago' WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $reserva_id);
        $stmt->execute();
    }
    
    $db->commit();
    echo json_encode($resposta);
    
} catch(Exception $e) {
    $db->rollBack();
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao processar pagamento: ' . $e->getMessage()]);
}

function gerarReferenciaPagamento() {
    return 'RENT-' . date('Ymd') . '-' . strtoupper(uniqid());
}

function gerarCodigoMBWAY() {
    return rand(100000, 999999);
}

function processarPagamentoCartao($dados_cartao, $valor) {
    // Simulação de processamento de cartão
    // Em produção, integrar com gateway real (Stripe, PayPal, etc.)
    
    // Validação básica
    if(strlen(preg_replace('/\s+/', '', $dados_cartao['numero_cartao'])) < 16) {
        return ['sucesso' => false, 'mensagem' => 'Número de cartão inválido'];
    }
    
    if(strlen($dados_cartao['cvv']) < 3) {
        return ['sucesso' => false, 'mensagem' => 'CVV inválido'];
    }
    
    // Simular processamento bem-sucedido
    return ['sucesso' => true, 'transacao_id' => 'TXN_' . uniqid()];
}
?>