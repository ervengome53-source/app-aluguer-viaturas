<?php
// controllers/FuncionarioController.php
require_once '../config/database.php';
require_once '../config/auth.php';

header('Content-Type: application/json');
session_start();

if(!isset($_SESSION['utilizador_id']) || $_SESSION['utilizador_cargo'] !== 'funcionario') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$funcionario_id = $_SESSION['utilizador_id'];

$acao = $_GET['acao'] ?? '';

switch($acao) {
    case 'confirmar_reserva':
        $dados = json_decode(file_get_contents('php://input'), true);
        $reserva_id = $dados['reserva_id'] ?? 0;
        
        $query = "UPDATE reservas SET status = 'confirmada', atualizado_em = NOW() WHERE id = :id AND status = 'pendente'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $reserva_id);
        
        if($stmt->execute() && $stmt->rowCount() > 0) {
            // Registar ação
            $log = "INSERT INTO registos (utilizador_id, acao, descricao, endereco_ip) 
                    VALUES (:user_id, 'confirmar_reserva', :desc, :ip)";
            $stmtLog = $db->prepare($log);
            $desc = "Confirmada reserva #$reserva_id";
            $stmtLog->bindParam(':user_id', $funcionario_id);
            $stmtLog->bindParam(':desc', $desc);
            $stmtLog->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
            $stmtLog->execute();
            
            echo json_encode(['sucesso' => true, 'mensagem' => 'Reserva confirmada!']);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao confirmar reserva']);
        }
        break;
        
    case 'rejeitar_reserva':
        $dados = json_decode(file_get_contents('php://input'), true);
        $reserva_id = $dados['reserva_id'] ?? 0;
        $motivo = $dados['motivo'] ?? '';
        
        $query = "UPDATE reservas SET status = 'rejeitada', observacoes = CONCAT(observacoes, ' Rejeitada: ', :motivo), atualizado_em = NOW() 
                  WHERE id = :id AND status = 'pendente'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':motivo', $motivo);
        $stmt->bindParam(':id', $reserva_id);
        
        if($stmt->execute() && $stmt->rowCount() > 0) {
            echo json_encode(['sucesso' => true, 'mensagem' => 'Reserva rejeitada!']);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao rejeitar reserva']);
        }
        break;
        
    case 'registar_aluguer':
        $dados = json_decode(file_get_contents('php://input'), true);
        
        $cliente_id = $dados['cliente_id'];
        $viatura_id = $dados['viatura_id'];
        $data_inicio = $dados['data_inicio'];
        $data_fim = $dados['data_fim'];
        $total_dias = $dados['total_dias'];
        $preco_total = $dados['preco_total'];
        $reserva_id = $dados['reserva_id'] ?? null;
        
        $db->beginTransaction();
        
        try {
            $query = "INSERT INTO alugueis (reserva_id, utilizador_id, viatura_id, funcionario_id, 
                      data_inicio, data_fim, total_dias, preco_total, status) 
                      VALUES (:reserva_id, :cliente_id, :viatura_id, :funcionario_id, 
                      :data_inicio, :data_fim, :total_dias, :preco_total, 'ativo')";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':reserva_id', $reserva_id);
            $stmt->bindParam(':cliente_id', $cliente_id);
            $stmt->bindParam(':viatura_id', $viatura_id);
            $stmt->bindParam(':funcionario_id', $funcionario_id);
            $stmt->bindParam(':data_inicio', $data_inicio);
            $stmt->bindParam(':data_fim', $data_fim);
            $stmt->bindParam(':total_dias', $total_dias);
            $stmt->bindParam(':preco_total', $preco_total);
            $stmt->execute();
            
            // Atualizar status da viatura
            $query = "UPDATE viaturas SET status = 'alugado' WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $viatura_id);
            $stmt->execute();
            
            // Se veio de reserva, atualizar status da reserva
            if($reserva_id) {
                $query = "UPDATE reservas SET status = 'confirmada' WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $reserva_id);
                $stmt->execute();
            }
            
            $db->commit();
            echo json_encode(['sucesso' => true, 'mensagem' => 'Aluguer registado com sucesso!']);
        } catch(Exception $e) {
            $db->rollBack();
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao registar aluguer: ' . $e->getMessage()]);
        }
        break;
        
    case 'registar_devolucao':
        $dados = json_decode(file_get_contents('php://input'), true);
        $aluguer_id = $dados['aluguer_id'];
        $observacoes = $dados['observacoes'] ?? '';
        $multa_paga = $dados['multa_paga'] ?? 0;
        $estado_veiculo = $dados['estado_veiculo'] ?? 'bom';
        
        $db->beginTransaction();
        
        try {
            // Buscar dados do aluguer
            $query = "SELECT a.*, v.preco_dia FROM alugueis a JOIN viaturas v ON a.viatura_id = v.id WHERE a.id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $aluguer_id);
            $stmt->execute();
            $aluguer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calcular multa
            $data_fim = new DateTime($aluguer['data_fim']);
            $hoje = new DateTime();
            $multa = 0;
            if($hoje > $data_fim) {
                $dias_atraso = $data_fim->diff($hoje)->days;
                $multa = $dias_atraso * 25;
            }
            
            // Atualizar aluguer
            $query = "UPDATE alugueis SET status = 'finalizado', data_devolucao = NOW(), 
                      observacoes = CONCAT(observacoes, ' / Devolução: ', :observacoes),
                      multa_atraso = :multa
                      WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':observacoes', $observacoes);
            $stmt->bindParam(':multa', $multa);
            $stmt->bindParam(':id', $aluguer_id);
            $stmt->execute();
            
            // Atualizar status da viatura
            $query = "UPDATE viaturas SET status = 'disponivel' WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $aluguer['viatura_id']);
            $stmt->execute();
            
            // Registrar multa se houver
            if($multa > 0) {
                $motivo = "Atraso na devolução - $dias_atraso dias";
                $query = "INSERT INTO multas (aluguer_id, utilizador_id, valor, motivo, status) 
                          VALUES (:aluguer_id, :utilizador_id, :valor, :motivo, " . ($multa_paga ? "'pago'" : "'pendente'") . ")";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':aluguer_id', $aluguer_id);
                $stmt->bindParam(':utilizador_id', $aluguer['utilizador_id']);
                $stmt->bindParam(':valor', $multa);
                $stmt->bindParam(':motivo', $motivo);
                $stmt->execute();
            }
            
            $db->commit();
            echo json_encode(['sucesso' => true, 'mensagem' => 'Devolução registada com sucesso!', 'multa' => $multa]);
        } catch(Exception $e) {
            $db->rollBack();
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao registar devolução: ' . $e->getMessage()]);
        }
        break;
        
    case 'buscar_clientes':
        $busca = $_GET['busca'] ?? '';
        $query = "SELECT id, nome, email, telefone FROM utilizadores 
                  WHERE cargo = 'cliente' AND (nome LIKE :busca OR email LIKE :busca OR telefone LIKE :busca)
                  LIMIT 10";
        $stmt = $db->prepare($query);
        $buscaParam = "%$busca%";
        $stmt->bindParam(':busca', $buscaParam);
        $stmt->execute();
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['sucesso' => true, 'dados' => $clientes]);
        break;
        
    default:
        echo json_encode(['sucesso' => false, 'mensagem' => 'Ação não encontrada']);
}
?>