<?php
// api/pagamentos.php
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
        if($acao == 'historico' && isset($_SESSION['utilizador_id'])) {
            $user_id = $_SESSION['utilizador_id'];
            $query = "SELECT p.*, 
                      CASE 
                          WHEN p.reserva_id IS NOT NULL THEN 
                              (SELECT CONCAT(v.marca, ' ', v.modelo) FROM reservas r JOIN viaturas v ON r.viatura_id = v.id WHERE r.id = p.reserva_id)
                          ELSE 
                              (SELECT CONCAT(v.marca, ' ', v.modelo) FROM alugueis a JOIN viaturas v ON a.viatura_id = v.id WHERE a.id = p.aluguer_id)
                      END as descricao
                      FROM pagamentos p
                      WHERE p.utilizador_id = :user_id
                      ORDER BY p.data_criacao DESC";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stats = [
                'total_pagamentos' => count($pagamentos),
                'total_pago' => array_sum(array_column($pagamentos, 'valor')),
                'total_pendente' => array_sum(array_column(array_filter($pagamentos, fn($p) => $p['estado'] == 'pendente'), 'valor'))
            ];
            
            echo json_encode(['sucesso' => true, 'dados' => $pagamentos, 'estatisticas' => $stats]);
        }
        elseif($acao == 'dashboard' && $_SESSION['utilizador_cargo'] == 'admin') {
            $query = "SELECT 
                      SUM(CASE WHEN estado = 'confirmado' THEN valor ELSE 0 END) as receita_total,
                      SUM(CASE WHEN estado = 'confirmado' AND MONTH(data_pagamento) = MONTH(CURRENT_DATE()) THEN valor ELSE 0 END) as receita_mes,
                      COUNT(*) as total_transacoes,
                      COUNT(CASE WHEN estado = 'pendente' THEN 1 END) as pendentes,
                      AVG(CASE WHEN estado = 'confirmado' THEN valor ELSE NULL END) as valor_medio
                      FROM pagamentos";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $kpis = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['sucesso' => true, 'dados' => $kpis]);
        }
        break;
        
    case 'POST':
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if($acao == 'confirmar' && isset($_SESSION['utilizador_id'])) {
            $pagamento_id = $dados['pagamento_id'];
            $query = "UPDATE pagamentos SET estado = 'confirmado', data_pagamento = NOW() WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $pagamento_id);
            
            if($stmt->execute()) {
                echo json_encode(['sucesso' => true, 'mensagem' => 'Pagamento confirmado']);
            } else {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao confirmar']);
            }
        }
        break;
        
    default:
        echo json_encode(['sucesso' => false, 'mensagem' => 'Método não suportado']);
}
?>