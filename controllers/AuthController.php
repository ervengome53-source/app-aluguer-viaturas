<?php
// controllers/AuthController.php
require_once '../config/database.php';
require_once '../config/auth.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();
Auth::init($db);

$acao = $_GET['acao'] ?? '';

switch($acao) {
    case 'login':
        $dados = json_decode(file_get_contents('php://input'), true);
        $email = $dados['email'] ?? '';
        $senha = $dados['senha'] ?? '';
        
        if(Auth::login($email, $senha)) {
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Login realizado com sucesso!',
                'redirect' => match($_SESSION['utilizador_cargo']) {
                    'admin' => '/admin/dashboard.php',
                    'funcionario' => '/funcionario/dashboard.php',
                    default => '/cliente/dashboard.php'
                }
            ]);
        } else {
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Email ou senha inválidos!'
            ]);
        }
        break;
        
    case 'logout':
        Auth::logout();
        echo json_encode(['sucesso' => true, 'redirect' => '/public/login.php']);
        break;
        
    case 'verificar_sessao':
        if(isset($_SESSION['utilizador_id'])) {
            echo json_encode([
                'sucesso' => true,
                'utilizador' => Auth::utilizador()
            ]);
        } else {
            echo json_encode(['sucesso' => false]);
        }
        break;
        
    case 'registar':
        $dados = json_decode(file_get_contents('php://input'), true);
        $nome = $dados['nome'] ?? '';
        $email = $dados['email'] ?? '';
        $senha = password_hash($dados['senha'] ?? '', PASSWORD_DEFAULT);
        $telefone = $dados['telefone'] ?? '';
        $morada = $dados['morada'] ?? '';
        $nif = $dados['nif'] ?? '';
        
        // Verificar se email já existe
        $check = $db->prepare("SELECT id FROM utilizadores WHERE email = :email");
        $check->bindParam(':email', $email);
        $check->execute();
        
        if($check->rowCount() > 0) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Email já registado!']);
            break;
        }
        
        $query = "INSERT INTO utilizadores (nome, email, senha, telefone, morada, nif, cargo) 
                  VALUES (:nome, :email, :senha, :telefone, :morada, :nif, 'cliente')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':senha', $senha);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':morada', $morada);
        $stmt->bindParam(':nif', $nif);
        
        if($stmt->execute()) {
            echo json_encode(['sucesso' => true, 'mensagem' => 'Registo realizado com sucesso!']);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao registar']);
        }
        break;
        
    default:
        echo json_encode(['sucesso' => false, 'mensagem' => 'Ação não encontrada']);
}
?>