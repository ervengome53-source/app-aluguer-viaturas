<?php
// controllers/AdminController.php
require_once '../config/database.php';
require_once '../config/auth.php';

header('Content-Type: application/json');
session_start();

if(!isset($_SESSION['utilizador_id']) || $_SESSION['utilizador_cargo'] !== 'admin') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$admin_id = $_SESSION['utilizador_id'];

$acao = $_GET['acao'] ?? '';

switch($acao) {
    case 'criar_viatura':
        $dados = json_decode(file_get_contents('php://input'), true);
        
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
            echo json_encode(['sucesso' => true, 'mensagem' => 'Viatura criada com sucesso!', 'id' => $db->lastInsertId()]);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao criar viatura']);
        }
        break;
        
    case 'editar_viatura':
        $dados = json_decode(file_get_contents('php://input'), true);
        
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
        
        if($stmt->execute()) {
            echo json_encode(['sucesso' => true, 'mensagem' => 'Viatura atualizada com sucesso!']);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar viatura']);
        }
        break;
        
    case 'excluir_viatura':
        $id = $_GET['id'] ?? 0;
        
        // Verificar se a viatura tem aluguéis ativos
        $check = $db->prepare("SELECT id FROM alugueis WHERE viatura_id = :id AND status = 'ativo'");
        $check->bindParam(':id', $id);
        $check->execute();
        
        if($check->rowCount() > 0) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Não é possível excluir: viatura tem aluguéis ativos']);
            break;
        }
        
        $query = "DELETE FROM viaturas WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if($stmt->execute()) {
            echo json_encode(['sucesso' => true, 'mensagem' => 'Viatura excluída com sucesso!']);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao excluir viatura']);
        }
        break;
        
    case 'criar_funcionario':
        $dados = json_decode(file_get_contents('php://input'), true);
        $nome = $dados['nome'];
        $email = $dados['email'];
        $senha = password_hash('123456', PASSWORD_DEFAULT);
        $telefone = $dados['telefone'] ?? '';
        
        $query = "INSERT INTO utilizadores (nome, email, senha, telefone, cargo) 
                  VALUES (:nome, :email, :senha, :telefone, 'funcionario')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':senha', $senha);
        $stmt->bindParam(':telefone', $telefone);
        
        if($stmt->execute()) {
            echo json_encode(['sucesso' => true, 'mensagem' => 'Funcionário criado! Senha: 123456']);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao criar funcionário']);
        }
        break;
        
    case 'estatisticas_dashboard':
        $query = "SELECT 
                  (SELECT COUNT(*) FROM utilizadores WHERE cargo = 'cliente') as total_clientes,
                  (SELECT COUNT(*) FROM utilizadores WHERE cargo = 'funcionario') as total_funcionarios,
                  (SELECT COUNT(*) FROM viaturas) as total_viaturas,
                  (SELECT COUNT(*) FROM viaturas WHERE status = 'disponivel') as viaturas_disponiveis,
                  (SELECT COUNT(*) FROM reservas WHERE status = 'pendente') as reservas_pendentes,
                  (SELECT COUNT(*) FROM alugueis WHERE status = 'ativo') as alugueis_ativos,
                  (SELECT SUM(valor) FROM pagamentos WHERE estado = 'confirmado') as receita_total,
                  (SELECT SUM(valor) FROM pagamentos WHERE estado = 'confirmado' AND MONTH(data_pagamento) = MONTH(CURRENT_DATE())) as receita_mes";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['sucesso' => true, 'dados' => $stats]);
        break;
        
    default:
        echo json_encode(['sucesso' => false, 'mensagem' => 'Ação não encontrada']);
}
?>