<?php
session_start();
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();
$mensagem = '';
$erro = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    
    // Verificar se email existe
    $query = "SELECT id, nome, email FROM utilizadores WHERE email = :email AND status = 'ativo'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($user) {
        // Gerar token único
        $token = bin2hex(random_bytes(32));
        $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Salvar token no banco
        $query = "INSERT INTO reset_senha (utilizador_id, token, expira_em) VALUES (:user_id, :token, :expiracao)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user['id']);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expiracao', $expiracao);
        $stmt->execute();
        
        // Link de redefinição
        $link = "http://" . $_SERVER['HTTP_HOST'] . "/SISTEMA_ALUGUER_ViATURAS/public/resetar_senha.php?token=" . $token;
        
        // Enviar email (simplificado - você pode usar PHPMailer)
        $assunto = "Recuperação de Senha - SIGAV";
        $mensagem_email = "
        <html>
        <head><style>body{font-family:Arial,sans-serif}</style></head>
        <body>
            <div style='max-width:600px;margin:0 auto;padding:20px;border:1px solid #ddd;border-radius:10px;'>
                <h2 style='color:#1E3A5F;'>SIGAV - Recuperação de Senha</h2>
                <p>Olá <strong>{$user['nome']}</strong>,</p>
                <p>Recebemos uma solicitação para redefinir sua senha.</p>
                <p>Clique no botão abaixo para criar uma nova senha:</p>
                <p style='text-align:center;'>
                    <a href='{$link}' style='display:inline-block;background:#FF8C00;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Redefinir Senha</a>
                </p>
                <p>Este link é válido por <strong>1 hora</strong>.</p>
                <p>Se você não solicitou esta alteração, ignore este email.</p>
                <hr>
                <small>SIGAV - Sistema de Gestão de Aluguer de Viaturas</small>
            </div>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: noreply@sigav.com" . "\r\n";
        
        if(mail($email, $assunto, $mensagem_email, $headers)) {
            $mensagem = '<i class="fas fa-envelope"></i> Link de recuperação enviado para seu email! Verifique sua caixa de entrada.';
        } else {
            // Se email não funcionar, mostra o link diretamente (apenas para desenvolvimento)
            $mensagem = '<i class="fas fa-link"></i> Clique no link para redefinir sua senha: <br><a href="' . $link . '">' . $link . '</a>';
        }
    } else {
        $erro = '<i class="fas fa-exclamation-triangle"></i> Email não encontrado ou utilizador inativo!';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci a Senha - SIGAV</title>
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
            background: #1E3A5F;
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
        .btn-submit:hover { background: #FF8C00; transform: translateY(-2px); }
        .mensagem {
            background: #d4edda;
            color: #155724;
            padding: 0.8rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            text-align: center;
            font-size: 0.85rem;
        }
        .erro {
            background: #f8d7da;
            color: #721c24;
            padding: 0.8rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            text-align: center;
            font-size: 0.85rem;
        }
        .voltar {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        .voltar a { color: #FF8C00; text-decoration: none; }
        .voltar a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo"><i class="fas fa-car"></i></div>
            <h2>Esqueceu a senha?</h2>
            <p>Digite seu email e enviaremos um link para redefinir sua senha</p>
        </div>
        
        <?php if($mensagem): ?>
            <div class="mensagem"><?= $mensagem ?></div>
        <?php endif; ?>
        
        <?php if($erro): ?>
            <div class="erro"><?= $erro ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email cadastrado</label>
                <input type="email" name="email" required placeholder="seu@email.com">
            </div>
            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i> Enviar Link
            </button>
        </form>
        
        <div class="voltar">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Voltar para o Login</a>
        </div>
    </div>
</body>
</html>