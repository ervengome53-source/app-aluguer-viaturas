<?php
// public/login.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../config/auth.php';

$error = '';
$database = new Database();
$db = $database->getConnection();
Auth::init($db);

// Se já estiver logado, redirecionar
if(isset($_SESSION['utilizador_id'])) {
    $redirect = match($_SESSION['utilizador_cargo']) {
        'admin' => '../admin/dashboard.php',
        'funcionario' => '../funcionario/dashboard.php',
        default => '../cliente/dashboard.php'
    };
    header("Location: $redirect");
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    
    if(Auth::login($email, $senha)) {
        $redirect = match($_SESSION['utilizador_cargo']) {
            'admin' => '../admin/dashboard.php',
            'funcionario' => '../funcionario/dashboard.php',
            default => '../cliente/dashboard.php'
        };
        header("Location: $redirect");
        exit();
    } else {
        $error = 'Email ou senha inválidos!';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIGAV</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1E3A5F 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header .logo {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }
        
        .login-header h2 {
            color: #1E3A5F;
            font-size: 1.8rem;
            margin-bottom: 0.25rem;
        }
        
        .login-header p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #1E3A5F;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #FF8C00;
            box-shadow: 0 0 0 3px rgba(255,140,0,0.1);
        }
        
        .btn-login {
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
            margin-top: 0.5rem;
        }
        
        .btn-login:hover {
            background: #FF8C00;
            transform: translateY(-2px);
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 0.75rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            text-align: center;
            font-size: 0.9rem;
        }
        
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        
        .register-link a {
            color: #FF8C00;
            text-decoration: none;
            font-weight: 500;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
		
		 <div style="text-align: center; margin-bottom: 20px;">
            <img src="../assets/imagens/66.jpg" alt="SISTEMA_ALUGUER_ViATURAS logo" style="max-width: 150px; height: auto; margin-bottom: 10px;">
        </div>
            <div class="logo"></div>
            <strong><h2>SIGAV</h2></strong>
           <strong> <p>Sistema de Gestão de Aluguer de Viaturas</p></strong>
        </div>
        
        <?php if($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>EMAIL</label>
                <input type="email" name="email" required placeholder="seu@email.com">
            </div>
            <div class="form-group">
                <label>SENHA</label>
                <input type="password" name="senha" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn-login">Entrar</button>
        </form>
		<div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                <label style="display: flex; align-items: center; gap: 8px;">
        
                </label>
                <a href="#" style="color: #1a3a5f;">Esqueceu a senha?</a>
            </div>
		
        <div class="register-link">
            <a href="register.php">Criar nova conta</a>
        </div>
    </div>
</body>
</html>