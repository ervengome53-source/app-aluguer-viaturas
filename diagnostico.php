<?php
echo "<h1>🔍 DIAGNÓSTICO COMPLETO DO SISTEMA</h1>";

// 1. Verificar conexão com banco
echo "<h2>1. Conexão com Banco de Dados</h2>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ Conexão com banco de dados OK!<br>";
} catch(Exception $e) {
    echo "❌ Erro de conexão: " . $e->getMessage() . "<br>";
    exit();
}

// 2. Verificar contas no banco
echo "<h2>2. Contas no Banco de Dados</h2>";
$query = "SELECT id, nome, email, cargo, status, LEFT(senha, 30) as senha_hash FROM utilizadores WHERE cargo IN ('admin', 'funcionario')";
$stmt = $db->prepare($query);
$stmt->execute();
$contas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(count($contas) > 0) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Cargo</th><th>Status</th><th>Hash (início)</th></tr>";
    foreach($contas as $c) {
        $cor = ($c['cargo'] == 'admin') ? '#ffcccc' : '#ccffcc';
        echo "<tr style='background:$cor'>";
        echo "<td>{$c['id']}</td>";
        echo "<td>{$c['nome']}</td>";
        echo "<td>{$c['email']}</td>";
        echo "<td><strong>{$c['cargo']}</strong></td>";
        echo "<td>{$c['status']}</td>";
        echo "<td>{$c['senha_hash']}...</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Nenhuma conta admin ou funcionário encontrada!<br>";
}

// 3. Testar senha específica
echo "<h2>3. Teste de Senha para Admin</h2>";
$email_teste = 'admin@rentcar.com';
$senha_teste = '123456';

$query = "SELECT * FROM utilizadores WHERE email = :email";
$stmt = $db->prepare($query);
$stmt->bindParam(':email', $email_teste);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if($user) {
    echo "✅ Utilizador encontrado: {$user['nome']} ({$user['cargo']})<br>";
    
    if(password_verify($senha_teste, $user['senha'])) {
        echo "✅ <span style='color:green'>SENHA CORRETA! (123456)</span><br>";
    } else {
        echo "❌ <span style='color:red'>SENHA INCORRETA!</span><br>";
        echo "Hash no banco: " . $user['senha'] . "<br>";
        
        // Gerar hash correto para 123456
        $hash_correto = password_hash('123456', PASSWORD_DEFAULT);
        echo "Hash correto para '123456' seria: " . $hash_correto . "<br>";
        echo "<a href='fix_senhas.php'>Clique aqui para corrigir as senhas automaticamente</a><br>";
    }
} else {
    echo "❌ Utilizador admin@rentcar.com não encontrado!<br>";
}

// 4. Verificar se os dashboards existem
echo "<h2>4. Verificar Dashboards</h2>";
$admin_dashboard = __DIR__ . '/admin/dashboard.php';
$funcionario_dashboard = __DIR__ . '/funcionario/dashboard.php';

echo "admin/dashboard.php: " . (file_exists($admin_dashboard) ? "✅ Existe" : "❌ NÃO EXISTE") . "<br>";
echo "funcionario/dashboard.php: " . (file_exists($funcionario_dashboard) ? "✅ Existe" : "❌ NÃO EXISTE") . "<br>";

// 5. Teste de sessão
echo "<h2>5. Teste de Sessão</h2>";
session_start();
$_SESSION['teste'] = 'funciona';
echo "Sessão foi iniciada. Valor de teste: " . ($_SESSION['teste'] ?? 'não definido') . "<br>";

// 6. Link para teste de login manual
echo "<h2>6. Teste de Login Manual</h2>";
echo "<a href='teste_login_manual.php' style='background:blue; color:white; padding:10px; text-decoration:none; border-radius:5px;'>Clique aqui para testar login manualmente</a><br>";
?>