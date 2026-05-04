<?php
require_once '../config/auth.php';
Auth::cargo(['admin']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$mensagem = '';
$erro = '';

// Processar ações
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    
    if($acao == 'criar') {
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $senha = password_hash('123456', PASSWORD_DEFAULT);
        $cargo = $_POST['cargo'];
        $telefone = $_POST['telefone'] ?? '';
        
        $query = "INSERT INTO utilizadores (nome, email, senha, cargo, telefone) VALUES (:nome, :email, :senha, :cargo, :telefone)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':senha', $senha);
        $stmt->bindParam(':cargo', $cargo);
        $stmt->bindParam(':telefone', $telefone);
        
        if($stmt->execute()) {
            $mensagem = 'Utilizador criado com sucesso! Senha padrão: 123456';
        } else {
            $erro = 'Erro ao criar utilizador';
        }
    }
    
    if($acao == 'alterar_status') {
        $id = $_POST['id'];
        $status = $_POST['status'];
        
        $query = "UPDATE utilizadores SET status = :status WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        
        if($stmt->execute()) {
            $mensagem = 'Status atualizado com sucesso!';
        } else {
            $erro = 'Erro ao atualizar status';
        }
    }
    
    if($acao == 'resetar_senha') {
        $id = $_POST['id'];
        $nova_senha = password_hash('123456', PASSWORD_DEFAULT);
        
        $query = "UPDATE utilizadores SET senha = :senha WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':senha', $nova_senha);
        $stmt->bindParam(':id', $id);
        
        if($stmt->execute()) {
            $mensagem = 'Senha resetada para: 123456';
        } else {
            $erro = 'Erro ao resetar senha';
        }
    }
}

// Buscar todos os utilizadores
$query = "SELECT * FROM utilizadores ORDER BY criado_em DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$utilizadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Utilizadores - Admin</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <?php if($mensagem): ?>
                <div class="notificacao sucesso"><?= $mensagem ?></div>
            <?php endif; ?>
            
            <?php if($erro): ?>
                <div class="notificacao erro"><?= $erro ?></div>
            <?php endif; ?>
            
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo"> Gestão de Utilizadores</h3>
                    <button class="btn btn-primario" onclick="abrirModalNovoUtilizador()">+ Novo Utilizador</button>
                </div>
                
                <div class="container-tabela">
                    <table class="tabela">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Cargo</th>
                                <th>Status</th>
                                <th>Data Registo</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($utilizadores as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['nome']) ?> <br>
                                    <small><?= $user['telefone'] ?? 'Sem telefone' ?></small>
                                </td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <span class="etiqueta etiqueta-<?= $user['cargo'] == 'admin' ? 'perigo' : ($user['cargo'] == 'funcionario' ? 'info' : 'sucesso') ?>">
                                        <?= ucfirst($user['cargo']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="etiqueta etiqueta-<?= $user['status'] == 'ativo' ? 'sucesso' : 'perigo' ?>">
                                        <?= ucfirst($user['status']) ?>
                                    </span>
                                 </td>
                                 <td><?= date('d/m/Y', strtotime($user['criado_em'])) ?></td>
                                <td class="tabela-acoes">
                                    <?php if($user['id'] != 1): ?>
                                        <button class="btn btn-<?= $user['status'] == 'ativo' ? 'perigo' : 'sucesso' ?> btn-sm" 
                                                onclick="alterarStatus(<?= $user['id'] ?>, '<?= $user['status'] ?>')">
                                            <?= $user['status'] == 'ativo' ? 'Bloquear' : 'Ativar' ?>
                                        </button>
                                  
                                    <?php else: ?>
                                        <small>Admin principal</small>
                                    <?php endif; ?>
                                 </td>
                             </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Novo Utilizador -->
    <div id="modalUtilizador" class="modal" style="display: none;">
        <div class="modal-conteudo" style="max-width: 500px;">
            <div class="modal-cabecalho">
                <h3>Novo Utilizador</h3>
                <button class="modal-fechar" onclick="fecharModalUtilizador()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="acao" value="criar">
                <div class="modal-corpo">
                    <div class="grupo-formulario">
                        <label class="rotulo-formulario">Nome Completo</label>
                        <input type="text" name="nome" class="controlo-formulario" required>
                    </div>
                    <div class="grupo-formulario">
                        <label class="rotulo-formulario">Email</label>
                        <input type="email" name="email" class="controlo-formulario" required>
                    </div>
                    <div class="grupo-formulario">
                        <label class="rotulo-formulario">Telefone</label>
                        <input type="tel" name="telefone" class="controlo-formulario">
                    </div>
                    <div class="grupo-formulario">
                        <label class="rotulo-formulario">Cargo</label>
                        <select name="cargo" class="controlo-formulario" required>
                            <option value="cliente">Cliente</option>
                            <option value="funcionario">Funcionário</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <small> A senha padrão será: <strong>123456</strong></small>
                    </div>
                </div>
                <div class="modal-rodape">
                    <button type="button" class="btn btn-secundario" onclick="fecharModalUtilizador()">Cancelar</button>
                    <button type="submit" class="btn btn-primario">Criar Utilizador</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function abrirModalNovoUtilizador() {
            document.getElementById('modalUtilizador').style.display = 'flex';
        }
        
        function fecharModalUtilizador() {
            document.getElementById('modalUtilizador').style.display = 'none';
        }
        
        function alterarStatus(id, statusAtual) {
            const novoStatus = statusAtual === 'ativo' ? 'inativo' : 'ativo';
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="acao" value="alterar_status">
                <input type="hidden" name="id" value="${id}">
                <input type="hidden" name="status" value="${novoStatus}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        function resetarSenha(id) {
            modal.confirmar('Resetar senha para 123456?', () => {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="acao" value="resetar_senha">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            });
        }
    </script>
    
    <style>
        .alert {
            padding: 0.75rem;
            border-radius: 8px;
            margin-top: 0.5rem;
        }
        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
    </style>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>