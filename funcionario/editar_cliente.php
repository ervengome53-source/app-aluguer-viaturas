<?php
require_once '../config/auth.php';
Auth::cargo(['funcionario']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mensagem = '';
$erro = '';

// Buscar dados do cliente
$query = "SELECT * FROM utilizadores WHERE id = :id AND cargo = 'cliente'";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$cliente) {
    header('Location: clientes.php');
    exit();
}

// Processar edição
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $morada = $_POST['morada'];
    $nif = $_POST['nif'];
    
    $query = "UPDATE utilizadores SET nome = :nome, email = :email, telefone = :telefone, morada = :morada, nif = :nif WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':telefone', $telefone);
    $stmt->bindParam(':morada', $morada);
    $stmt->bindParam(':nif', $nif);
    $stmt->bindParam(':id', $id);
    
    if($stmt->execute()) {
        $mensagem = 'Cliente atualizado com sucesso!';
        // Atualizar dados do cliente
        $cliente['nome'] = $nome;
        $cliente['email'] = $email;
        $cliente['telefone'] = $telefone;
        $cliente['morada'] = $morada;
        $cliente['nif'] = $nif;  // Corrigido: antes era 'NUIT'
    } else {
        $erro = 'Erro ao atualizar cliente';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente - Funcionário</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7fa; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #1E3A5F; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-primary { background: #1E3A5F; color: white; }
        .btn-primary:hover { background: #FF8C00; }
        .btn-secondary { background: #6c757d; color: white; }
        .mensagem { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .erro { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h1> Editar Cliente</h1>
        
        <?php if($mensagem): ?>
            <div class="mensagem"><?= $mensagem ?></div>
        <?php endif; ?>
        
        <?php if($erro): ?>
            <div class="erro"><?= $erro ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Nome</label>
                <input type="text" name="nome" value="<?= htmlspecialchars($cliente['nome']) ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($cliente['email']) ?>" required>
            </div>
            <div class="form-group">
                <label>Telefone</label>
                <input type="tel" name="telefone" value="<?= htmlspecialchars($cliente['telefone']) ?>">
            </div>
            <div class="form-group">
                <label>NUIT</label>
                <input type="text" name="nif" value="<?= htmlspecialchars($cliente['nif']) ?>">  <!-- Corrigido: 'NUIT' mudou para 'nif' -->
            </div>
            <div class="form-group">
                <label>Morada</label>
                <textarea name="morada" rows="3"><?= htmlspecialchars($cliente['morada']) ?></textarea>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="clientes.php" class="btn btn-secondary">Voltar</a>
            </div>
        </form>
    </div>
</body>
</html>