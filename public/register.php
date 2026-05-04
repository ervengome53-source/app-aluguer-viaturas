<?php
// public/register.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

$error = '';
$success = '';

$database = new Database();
$db = $database->getConnection();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $telefone = $_POST['telefone'] ?? '';
    $morada = $_POST['morada'] ?? '';
    $nif = $_POST['nif'] ?? '';
    
    $check = $db->prepare("SELECT id FROM utilizadores WHERE email = :email");
    $check->bindParam(':email', $email);
    $check->execute();
    
    if($check->rowCount() > 0) {
        $error = 'Email já registado!';
    } else {
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
            $success = 'Conta criada com sucesso! Faça login.';
        } else {
            $error = 'Erro ao criar conta. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registo - SIGAV </title>
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
            padding: 2rem;
        }
        
        .register-container {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .register-header .logo {
            font-size: 2.5rem;
        }
        
        .register-header h2 {
            color: #1E3A5F;
            font-size: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            color: #1E3A5F;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 0.7rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #FF8C00;
            box-shadow: 0 0 0 3px rgba(255,140,0,0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 70px;
        }
        
        .btn-register {
            width: 100%;
            padding: 0.7rem;
            background: #1E3A5F;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
        }
        
        .btn-register:hover {
            background: #FF8C00;
            transform: translateY(-2px);
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 0.7rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 0.7rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .login-link {
            text-align: center;
            margin-top: 1rem;
            padding-top: 0.8rem;
            border-top: 1px solid #eee;
        }
        
        .login-link a {
            color: #FF8C00;
            text-decoration: none;
        }
        
        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        @media (max-width: 500px) {
            .row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <div class="logo"></div>
			 <div style="text-align: center; margin-bottom: 20px;">
            <img src="../assets/imagens/66.jpg" alt="SIGAV Logo" style="max-width: 160px; height: auto; margin-bottom: 10px;">
        </div>
			
            <h2>Criar Conta</h2>
        </div>
        
        <?php if($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="success-message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
               <strong> <label>NOME COMPLETO</label></strong>
                <input type="text" name="nome" required>
            </div>
            <div class="form-group">
                <label>EMAIL</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>SENHA</label>
                <input type="password" name="senha" required>
            </div>
            <div class="row">
                <div class="form-group">
                    <label>TELEFONE</label>
                    <input type="tel" name="telefone">
                </div>
                <div class="form-group">
                    <label>NUIT</label>
                    <input type="text" name="NUIT">
                </div>
            </div>
            <div class="form-group">
                <label>MORADA</label>
                <textarea name="morada"></textarea>
            </div>
            <button type="submit" class="btn-register">Registar</button>
        </form>
        
        <div class="login-link">
            <a href="login.php">Já tem conta? Faça login</a>
        </div>
    </div>
</body>
</html>