<?php
require_once '../config/auth.php';
Auth::cargo(['cliente']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$utilizador = Auth::utilizador();

// Buscar dados do utilizador
$query = "SELECT * FROM utilizadores WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $utilizador['id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$sucesso = '';
$erro = '';

// Atualizar perfil
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'perfil') {
    $nome = $_POST['nome'];
    $telefone = $_POST['telefone'];
    $morada = $_POST['morada'];
    $nif = $_POST['nif'];
    
    $query = "UPDATE utilizadores SET nome = :nome, telefone = :telefone, morada = :morada, nif = :nif WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':telefone', $telefone);
    $stmt->bindParam(':morada', $morada);
    $stmt->bindParam(':nif', $nif);
    $stmt->bindParam(':id', $utilizador['id']);
    
    if($stmt->execute()) {
        $_SESSION['utilizador_nome'] = $nome;
        $sucesso = '<i class="fas fa-check-circle"></i> Perfil atualizado com sucesso!';
        $user['nome'] = $nome;
        $user['telefone'] = $telefone;
        $user['morada'] = $morada;
        $user['nif'] = $nif;
    } else {
        $erro = '<i class="fas fa-exclamation-triangle"></i> Erro ao atualizar perfil';
    }
}

// Alterar senha
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'senha') {
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    
    if($nova_senha !== $confirmar_senha) {
        $erro = '<i class="fas fa-exclamation-triangle"></i> As senhas não coincidem';
    } elseif(strlen($nova_senha) < 6) {
        $erro = '<i class="fas fa-exclamation-triangle"></i> A senha deve ter no mínimo 6 caracteres';
    } else {
        $query = "SELECT senha FROM utilizadores WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $utilizador['id']);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(password_verify($senha_atual, $user_data['senha'])) {
            $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $query = "UPDATE utilizadores SET senha = :senha WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':senha', $nova_senha_hash);
            $stmt->bindParam(':id', $utilizador['id']);
            
            if($stmt->execute()) {
                $sucesso = '<i class="fas fa-check-circle"></i> Senha alterada com sucesso!';
            } else {
                $erro = '<i class="fas fa-exclamation-triangle"></i> Erro ao alterar senha';
            }
        } else {
            $erro = '<i class="fas fa-exclamation-triangle"></i> Senha atual incorreta';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="../assets/css/cliente.css">
    <style>
        .perfil-container {
            display: flex;
            align-items: center;
            gap: 2rem;
            background: white;
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .perfil-avatar {
            width: 100px;
            height: 100px;
            background: #FF8C00;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            color: white;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        @media (max-width: 768px) {
            .perfil-container { flex-direction: column; text-align: center; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <?php if($sucesso): ?>
                <div class="notificacao sucesso"><?= $sucesso ?></div>
            <?php endif; ?>
            
            <?php if($erro): ?>
                <div class="notificacao erro"><?= $erro ?></div>
            <?php endif; ?>
            
            <div class="perfil-container">
                <div class="perfil-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                
                <div class="perfil-info">
                    <h2><i class="fas fa-user"></i> <?= htmlspecialchars($user['nome']) ?></h2>
                    <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?></p>
                    <span class="etiqueta etiqueta-sucesso"><i class="fas fa-check-circle"></i> <?= ucfirst($user['cargo']) ?></span>
                </div>
            </div>
            
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo"><i class="fas fa-edit"></i> Editar Perfil</h3>
                </div>
                <form method="POST" class="form-perfil">
                    <input type="hidden" name="acao" value="perfil">
                    <div class="form-row">
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario"><i class="fas fa-user"></i> Nome Completo</label>
                            <input type="text" name="nome" class="controlo-formulario" value="<?= htmlspecialchars($user['nome']) ?>" required>
                        </div>
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario"><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" class="controlo-formulario" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                            <small>O email não pode ser alterado</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario"><i class="fas fa-phone"></i> Telefone</label>
                            <input type="tel" name="telefone" class="controlo-formulario" value="<?= htmlspecialchars($user['telefone']) ?>">
                        </div>
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario"><i class="fas fa-id-card"></i> NUIT</label>
                            <input type="text" name="nif" class="controlo-formulario" value="<?= htmlspecialchars($user['nif']) ?>">
                        </div>
                    </div>
                    <div class="grupo-formulario">
                        <label class="rotulo-formulario"><i class="fas fa-map-marker-alt"></i> Morada</label>
                        <textarea name="morada" class="controlo-formulario" rows="3"><?= htmlspecialchars($user['morada']) ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primario"><i class="fas fa-save"></i> Guardar Alterações</button>
                </form>
            </div>
            
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo"><i class="fas fa-key"></i> Alterar Senha</h3>
                </div>
                <form method="POST" class="form-senha">
                    <input type="hidden" name="acao" value="senha">
                    <div class="grupo-formulario">
                        <label class="rotulo-formulario"><i class="fas fa-lock"></i> Senha Atual</label>
                        <input type="password" name="senha_atual" class="controlo-formulario" required>
                    </div>
                    <div class="form-row">
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario"><i class="fas fa-key"></i> Nova Senha</label>
                            <input type="password" name="nova_senha" class="controlo-formulario" required>
                        </div>
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario"><i class="fas fa-check-double"></i> Confirmar Nova Senha</label>
                            <input type="password" name="confirmar_senha" class="controlo-formulario" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-destaque"><i class="fas fa-sync-alt"></i> Alterar Senha</button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/cliente.js"></script>
</body>
</html>