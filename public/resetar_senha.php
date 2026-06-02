<?php
session_start();
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();
$mensagem = '';
$erro = '';
$token = $_GET['token'] ?? '';

// Verificar se token é válido
$query = "SELECT r.*, u.nome, u.email FROM reset_senha r 
          JOIN utilizadores u ON r.utilizador_id = u.id 
          WHERE r.token = :token AND r.used = 0 AND r.expira_em > NOW()";
$stmt = $db->prepare($query);
$stmt->bindParam(':token', $token);
$stmt->execute();
$reset = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$reset && $token) {
    $erro = 'Link inválido ou expirado! Solicite uma nova recuperação.';
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && $reset) {
    $nova_senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    
    if($nova_senha !== $confirmar_senha) {
        $erro = '<i class="fas fa-exclamation-triangle"></i> As senhas não coincidem!';
    } elseif(strlen($nova_senha) < 6) {
        $erro = '<i class="fas fa-exclamation-triangle"></i> A senha deve ter no mínimo 6 caracteres!';
    } else {
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        
        // Atualizar senha
        $query = "UPDATE utilizadores SET senha = :senha WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':senha', $senha_hash);
        $stmt->bindParam(':id', $reset['utilizador_id']);
        $stmt->execute();
        
        // Marcar token como usado
        $query = "UPDATE reset_senha SET used = 1 WHERE token = :token";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        $mensagem = '<i class="fas fa-check-circle"></i> Senha redefinida com sucesso!';
        echo '<script>setTimeout(function(){ window.location.href = "login.php"; }, 3000);</script>';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - SIGAV</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1E3A5F 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            animation: fadeIn 0.5s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .header { text-align: center; margin-bottom: 2rem; }
        .header .logo { font-size: 2.5rem; color: #FF8C00; margin-bottom: 0.5rem; }
        .header h2 { color: #1E3A5F; font-size: 1.5rem; }
        .header p { color: #666; font-size: 0.85rem; margin-top: 0.5rem; }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #1E3A5F; font-size: 0.85rem; }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .form-group input:focus { outline: none; border-color: #FF8C00; box-shadow: 0 0 0 3px rgba(255,140,0,0.1); }
        .btn-submit {
            width: 100%;
            padding: 0.75rem;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-submit:hover { background: #218838; transform: translateY(-2px); }
        .mensagem {
            background: #d4edda;
            color: #155724;
            padding: 0.8rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            text-align: center;
        }
        .erro {
            background: #f8d7da;
            color: #721c24;
            padding: 0.8rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            text-align: center;
        }
        .voltar { text-align: center; margin-top: 1.5rem; }
        .voltar a { color: #FF8C00; text-decoration: none; }
        .info-user {
            background: #e8f4f8;
            padding: 0.8rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo"><i class="fas fa-key"></i></div>
            <h2>Redefinir Senha</h2>
            <p>Crie uma nova senha para sua conta</p>
        </div>
        
        <?php if($mensagem): ?>
            <div class="mensagem"><?= $mensagem ?></div>
            <div class="voltar"><a href="login.php"><i class="fas fa-arrow-left"></i> Ir para o Login</a></div>
        <?php elseif($erro && $token): ?>
            <div class="erro"><?= $erro ?></div>
            <div class="voltar"><a href="esqueci_senha.php"><i class="fas fa-paper-plane"></i> Solicitar novo link</a></div>
        <?php elseif($reset): ?>
            <div class="info-user">
                <i class="fas fa-user"></i> <strong><?= htmlspecialchars($reset['nome']) ?></strong><br>
                <small><?= htmlspecialchars($reset['email']) ?></small>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Nova Senha</label>
                    <input type="password" name="senha" required placeholder="Mínimo 6 caracteres">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-check-double"></i> Confirmar Nova Senha</label>
                    <input type="password" name="confirmar_senha" required placeholder="Digite a senha novamente">
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Redefinir Senha
                </button>
            </form>
            
            <div class="voltar">
                <a href="login.php"><i class="fas fa-arrow-left"></i> Voltar para o Login</a>
            </div>
        <?php else: ?>
            <div class="erro">Link inválido ou expirado! Solicite uma nova recuperação.</div>
            <div class="voltar"><a href="esqueci_senha.php"><i class="fas fa-paper-plane"></i> Solicitar novo link</a></div>
        <?php endif; ?>
    </div>
</body>
</html>