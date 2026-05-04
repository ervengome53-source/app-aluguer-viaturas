<?php
// config/auth.php

// Só iniciar sessão se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Auth {
    private static $db;
    
    public static function init($database) {
        self::$db = $database;
    }
    
    public static function login($email, $senha) {
        $query = "SELECT * FROM utilizadores WHERE email = :email AND status = 'ativo'";
        $stmt = self::$db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $utilizador = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($utilizador && password_verify($senha, $utilizador['senha'])) {
            $_SESSION['utilizador_id'] = $utilizador['id'];
            $_SESSION['utilizador_nome'] = $utilizador['nome'];
            $_SESSION['utilizador_email'] = $utilizador['email'];
            $_SESSION['utilizador_cargo'] = $utilizador['cargo'];
            return true;
        }
        return false;
    }
    
    public static function logout() {
        session_destroy();
        header('Location: ../public/index.php');
        exit();
    }
    
    public static function check() {
        if(!isset($_SESSION['utilizador_id'])) {
            header('Location: ../public/login.php');
            exit();
        }
    }
    
    public static function cargo($cargos_permitidos = []) {
        self::check();
        if(!in_array($_SESSION['utilizador_cargo'], $cargos_permitidos)) {
            header('Location: ../public/index.php');
            exit();
        }
    }
    
    public static function utilizador() {
        return [
            'id' => $_SESSION['utilizador_id'] ?? null,
            'nome' => $_SESSION['utilizador_nome'] ?? null,
            'email' => $_SESSION['utilizador_email'] ?? null,
            'cargo' => $_SESSION['utilizador_cargo'] ?? null
        ];
    }
}
?>