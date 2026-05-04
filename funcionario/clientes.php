<?php
require_once '../config/auth.php';
Auth::cargo(['funcionario']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$busca = $_GET['busca'] ?? '';
$mensagem = '';
$erro = '';

// Processar criação/edição de cliente
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    
    if($acao == 'criar') {
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $senha = password_hash('123456', PASSWORD_DEFAULT);
        $telefone = $_POST['telefone'];
        $morada = $_POST['morada'];
        $nif = $_POST['nif'];
        
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
            $mensagem = 'Cliente criado com sucesso! Senha padrão: 123456';
        } else {
            $erro = 'Erro ao criar cliente';
        }
    }
    
    if($acao == 'editar') {
        $id = $_POST['id'];
        $nome = $_POST['nome'];
        $telefone = $_POST['telefone'];
        $morada = $_POST['morada'];
        $nif = $_POST['nif'];
        
        $query = "UPDATE utilizadores SET nome = :nome, telefone = :telefone, morada = :morada, nif = :nif 
                  WHERE id = :id AND cargo = 'cliente'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':morada', $morada);
        $stmt->bindParam(':nif', $nif);
        $stmt->bindParam(':id', $id);
        
        if($stmt->execute()) {
            $mensagem = 'Cliente atualizado com sucesso!';
        } else {
            $erro = 'Erro ao atualizar cliente';
        }
    }
}

// Buscar clientes
$query = "SELECT * FROM utilizadores WHERE cargo = 'cliente'";
if($busca) {
    $query .= " AND (nome LIKE :busca OR email LIKE :busca OR telefone LIKE :busca)";
}
$query .= " ORDER BY nome ASC";
$stmt = $db->prepare($query);
if($busca) {
    $buscaParam = "%$busca%";
    $stmt->bindParam(':busca', $buscaParam);
}
$stmt->execute();
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Clientes - Funcionário</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="../assets/css/funcionario.css">
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
                    <h3 class="cartao-titulo">Gestão de Clientes</h3>
                    <div style="display: flex; gap: 0.5rem;">
                        <form method="GET" style="display: flex; gap: 0.5rem;">
                            <input type="text" name="busca" class="controlo-formulario" placeholder="Buscar cliente..." 
                                   value="<?= htmlspecialchars($busca) ?>" style="width: 200px;">
                            <button type="submit" class="btn btn-info"> Buscar</button>
                            <?php if($busca): ?>
                                <a href="clientes.php" class="btn btn-secundario">Limpar</a>
                            <?php endif; ?>
                        </form>
                        <button class="btn btn-primario" onclick="abrirModalNovoCliente()">+ Novo Cliente</button>
                    </div>
                </div>
                
                <?php if(count($clientes) > 0): ?>
                <div class="container-tabela">
                    <table class="tabela">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Telefone</th>
                                <th>NUIT</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($clientes as $cliente): ?>
                            <tr>
                                <td><?= $cliente['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($cliente['nome']) ?></strong><br>
                                    <small><?= htmlspecialchars($cliente['morada'] ?? '---') ?></small>
                                </td>
                                <td><?= htmlspecialchars($cliente['email']) ?></td>
                                <td><?= $cliente['telefone'] ?? '---' ?></td>
                                <td><?= $cliente['nif'] ?? '---' ?></td>
                                <td>
                                    <span class="etiqueta etiqueta-<?= $cliente['status'] == 'ativo' ? 'sucesso' : 'perigo' ?>">
                                        <?= ucfirst($cliente['status']) ?>
                                    </span>
                                </td>
                                <td class="tabela-acoes">
									<a href="editar_cliente.php?id=<?= $cliente['id'] ?>" class="btn btn-info btn-sm">Editar</a>
									<a href="historico_cliente.php?id=<?= $cliente['id'] ?>" class="btn btn-destaque btn-sm">Histórico</a>
								</td>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="texto-centro" style="padding: 3rem;">
                    <div style="font-size: 3rem;"></div>
                    <h3>Nenhum cliente encontrado</h3>
                    <p>Clique em "Novo Cliente" para adicionar.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal Novo Cliente -->
    <div id="modalCliente" class="modal" style="display: none;">
        <div class="modal-conteudo" style="max-width: 600px;">
            <div class="modal-cabecalho">
                <h3 id="modalTitulo">Novo Cliente</h3>
                <button class="modal-fechar" onclick="fecharModalCliente()">&times;</button>
            </div>
            <form method="POST" id="formCliente">
                <input type="hidden" name="acao" id="formAcao" value="criar">
                <input type="hidden" name="id" id="clienteId">
                <div class="modal-corpo">
                    <div class="form-row">
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Nome Completo *</label>
                            <input type="text" name="nome" id="nome" class="controlo-formulario" required>
                        </div>
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Email *</label>
                            <input type="email" name="email" id="email" class="controlo-formulario" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Telefone</label>
                            <input type="tel" name="telefone" id="telefone" class="controlo-formulario">
                        </div>
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">NUIT</label>
                            <input type="text" name="NUIT" id="NUIT" class="controlo-formulario">
                        </div>
                    </div>
                    <div class="grupo-formulario">
                        <label class="rotulo-formulario">Morada</label>
                        <textarea name="morada" id="morada" class="controlo-formulario" rows="2"></textarea>
                    </div>
                    <div class="alert alert-info" id="infoSenha" style="display: none;">
                        <small> A senha padrão será: <strong>123456</strong></small>
                    </div>
                </div>
                <div class="modal-rodape">
                    <button type="button" class="btn btn-secundario" onclick="fecharModalCliente()">Cancelar</button>
                    <button type="submit" class="btn btn-primario">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    
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
    
    <script>
        function abrirModalNovoCliente() {
            document.getElementById('modalTitulo').innerText = 'Novo Cliente';
            document.getElementById('formAcao').value = 'criar';
            document.getElementById('formCliente').reset();
            document.getElementById('clienteId').value = '';
            document.getElementById('email').disabled = false;
            document.getElementById('infoSenha').style.display = 'block';
            document.getElementById('modalCliente').style.display = 'flex';
        }
        
        async function editarCliente(id) {
            const resultado = await API.get(`../api/clientes.php?id=${id}`);
            if(resultado && resultado.sucesso) {
                const c = resultado.dados;
                document.getElementById('modalTitulo').innerText = 'Editar Cliente';
                document.getElementById('formAcao').value = 'editar';
                document.getElementById('clienteId').value = c.id;
                document.getElementById('nome').value = c.nome;
                document.getElementById('email').value = c.email;
                document.getElementById('email').disabled = true;
                document.getElementById('telefone').value = c.telefone;
                document.getElementById('nif').value = c.nif;
                document.getElementById('morada').value = c.morada;
                document.getElementById('infoSenha').style.display = 'none';
                document.getElementById('modalCliente').style.display = 'flex';
            }
        }
        
        function fecharModalCliente() {
            document.getElementById('modalCliente').style.display = 'none';
        }
        
        function verHistorico(id) {
            window.location.href = `historico_cliente.php?id=${id}`;
        }
        
        function novoAluguer(id) {
            window.location.href = `aluguer.php?cliente_id=${id}`;
        }
    </script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/funcionario.js"></script>
</body>
</html>