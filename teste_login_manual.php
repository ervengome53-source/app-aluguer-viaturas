<?php
session_start();
require_once 'config/database.php';
require_once 'config/auth.php';

$database = new Database();
$db = $database->getConnection();
Auth::init($db);

$mensagem = '';
$erro = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    
    if(Auth::login($email, $senha)) {
        $mensagem = "✅ Login bem sucedido! Redirecionando...";
        $redirect = match($_SESSION['utilizador_cargo']) {
            'admin' => 'admin/dashboard.php',
            'funcionario' => 'funcionario/dashboard.php',
            default => 'cliente/dashboard.php'
        };
        echo "<script>setTimeout(() => { window.location.href = '$redirect'; }, 2000);</script>";
    } else {
        $erro = "❌ Email ou senha inválidos!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Teste de Login Manual</title>
    <style>
        body { font-family: Arial; padding: 50px; background: #f0f2f5; }
        .container { max-width: 400px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #1E3A5F; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; width: 100%; }
        button:hover { background: #FF8C00; }
        .sucesso { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 10px; }
        .erro { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 10px; }
        h2 { color: #1E3A5F; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🧪 Teste de Login Manual</h2>
        
        <?php if($mensagem): ?>
            <div class="sucesso"><?= $mensagem ?></div>
        <?php endif; ?>
        
        <?php if($erro): ?>
            <div class="erro"><?= $erro ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <label>Email:</label>
            <input type="email" name="email" value="admin@rentcar.com" required>
            
            <label>Senha:</label>
            <input type="password" name="senha" value="123456" required>
            
            <button type="submit">Entrar</button>
        </form>
        
        <hr>
        <p><strong>Credenciais para testar:</strong></p>
        <ul>
            <li><strong>Admin:</strong> admin@rentcar.com / 123456</li>
            <li><strong>Funcionário:</strong> funcionario@rentcar.com / 123456</li>
            <li><strong>Cliente:</strong> cliente@rentcar.com / 123456</li>
        </ul>
    </div>
</body>
</html>