<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

// Contas a criar/atualizar
$contas = [
    ['Administrador', 'admin@rentcar.com', 'admin', $hash],
    ['João Silva', 'funcionario@rentcar.com', 'funcionario', $hash],
    ['Maria Santos', 'cliente@rentcar.com', 'cliente', $hash]
];

foreach($contas as $conta) {
    $check = $db->prepare("SELECT id FROM utilizadores WHERE email = :email");
    $check->bindParam(':email', $conta[1]);
    $check->execute();
    
    if($check->rowCount() == 0) {
        // Criar conta
        $stmt = $db->prepare("INSERT INTO utilizadores (nome, email, senha, cargo, status) VALUES (:nome, :email, :senha, :cargo, 'ativo')");
        $stmt->bindParam(':nome', $conta[0]);
        $stmt->bindParam(':email', $conta[1]);
        $stmt->bindParam(':senha', $conta[3]);
        $stmt->bindParam(':cargo', $conta[2]);
        $stmt->execute();
        echo " Conta {$conta[0]} criada!<br>";
    } else {
        // Atualizar senha
        $stmt = $db->prepare("UPDATE utilizadores SET senha = :senha, cargo = :cargo, status = 'ativo' WHERE email = :email");
        $stmt->bindParam(':senha', $conta[3]);
        $stmt->bindParam(':cargo', $conta[2]);
        $stmt->bindParam(':email', $conta[1]);
        $stmt->execute();
        echo " Conta {$conta[0]} atualizada!<br>";
    }
}

echo "<br><strong>Todas as contas estão prontas!</strong><br>";
echo "<hr>";
echo "<h3>Credenciais:</h3>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin@rentcar.com / 123456</li>";
echo "<li><strong>Funcionário:</strong> funcionario@rentcar.com / 123456</li>";
echo "<li><strong>Cliente:</strong> cliente@rentcar.com / 123456</li>";
echo "</ul>";
echo "<a href='public/login.php'>Ir para o Login</a>";
?>