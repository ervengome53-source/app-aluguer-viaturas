<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h1>🔧 CORRIGINDO SENHAS</h1>";

$hash_correto = password_hash('123456', PASSWORD_DEFAULT);
echo "Novo hash gerado: " . $hash_correto . "<br><br>";

// Atualizar admin
$stmt = $db->prepare("UPDATE utilizadores SET senha = :senha WHERE email = 'admin@rentcar.com'");
$stmt->bindParam(':senha', $hash_correto);
$stmt->execute();
echo "✅ Senha do admin atualizada!<br>";

// Atualizar funcionario
$stmt = $db->prepare("UPDATE utilizadores SET senha = :senha WHERE email = 'funcionario@rentcar.com'");
$stmt->bindParam(':senha', $hash_correto);
$stmt->execute();
echo "✅ Senha do funcionario atualizada!<br>";

// Atualizar outros funcionários
$stmt = $db->prepare("UPDATE utilizadores SET senha = :senha WHERE cargo = 'funcionario'");
$stmt->bindParam(':senha', $hash_correto);
$stmt->execute();
echo "✅ Senhas de todos os funcionários atualizadas!<br>";

echo "<br><strong>Todas as senhas foram corrigidas para: 123456</strong><br>";
echo "<a href='public/login.php'>Ir para o Login</a><br>";
echo "<a href='diagnostico.php'>Voltar ao Diagnóstico</a>";
?>