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
        $sucesso = 'Perfil atualizado com sucesso!';
        $user['nome'] = $nome;
        $user['telefone'] = $telefone;
        $user['morada'] = $morada;
        $user['nif'] = $nif;
    } else {
        $erro = 'Erro ao atualizar perfil';
    }
}

// Alterar senha
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'senha') {
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    
    if($nova_senha !== $confirmar_senha) {
        $erro = 'As senhas não coincidem';
    } elseif(strlen($nova_senha) < 6) {
        $erro = 'A senha deve ter no mínimo 6 caracteres';
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
                $sucesso = 'Senha alterada com sucesso!';
            } else {
                $erro = 'Erro ao alterar senha';
            }
        } else {
            $erro = 'Senha atual incorreta';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="../assets/css/cliente.css">
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
                    <?= strtoupper(substr($user['nome'], 0, 1)) ?>
                </div>
                
                <div class="perfil-info">
                    <h2><?= htmlspecialchars($user['nome']) ?></h2>
                    <p><?= htmlspecialchars($user['email']) ?></p>
                    <p class="etiqueta etiqueta-sucesso"><?= ucfirst($user['cargo']) ?></p>
                </div>
            </div>
            
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo"> Editar Perfil</h3>
                </div>
                <form method="POST" class="form-perfil">
                    <input type="hidden" name="acao" value="perfil">
                    <div class="form-row">
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Nome Completo</label>
                            <input type="text" name="nome" class="controlo-formulario" value="<?= htmlspecialchars($user['nome']) ?>" required>
                        </div>
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Email</label>
                            <input type="email" class="controlo-formulario" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                            <small>O email não pode ser alterado</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Telefone</label>
                            <input type="tel" name="telefone" class="controlo-formulario" value="<?= htmlspecialchars($user['telefone']) ?>">
                        </div>
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">NUIT</label>
                            <input type="text" name="NUIT" class="controlo-formulario" value="<?= htmlspecialchars($user['nif']) ?>">
                        </div>
                    </div>
                    <div class="grupo-formulario">
                        <label class="rotulo-formulario">Morada</label>
                        <textarea name="morada" class="controlo-formulario" rows="3"><?= htmlspecialchars($user['morada']) ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primario"> Guardar Alterações</button>
                </form>
            </div>
            
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo"> Alterar Senha</h3>
                </div>
                <form method="POST" class="form-senha">
                    <input type="hidden" name="acao" value="senha">
                    <div class="grupo-formulario">
                        <label class="rotulo-formulario">Senha Atual</label>
                        <input type="password" name="senha_atual" class="controlo-formulario" required>
                    </div>
                    <div class="form-row">
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Nova Senha</label>
                            <input type="password" name="nova_senha" class="controlo-formulario" required>
                        </div>
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Confirmar Nova Senha</label>
                            <input type="password" name="confirmar_senha" class="controlo-formulario" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-destaque"> Alterar Senha</button>
                </form>
            </div>
        </div>
    </div>
    
    <style>
        .perfil-container {
            display: flex;
            align-items: center;
            gap: 2rem;
            background: var(--branco);
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            box-shadow: var(--sombra-peq);
        }
        
        .perfil-avatar {
            width: 100px;
            height: 100px;
            background: var(--laranja);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            color: var(--branco);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        @media (max-width: 768px) {
            .perfil-container {
                flex-direction: column;
                text-align: center;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/cliente.js"></script>
</body>
</html>