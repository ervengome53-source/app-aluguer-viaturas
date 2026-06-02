<?php
require_once '../config/auth.php';
Auth::cargo(['funcionario']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$busca = $_GET['busca'] ?? '';
$mensagem = '';
$erro = '';

// Processar criação de cliente
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'criar') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = password_hash('123456', PASSWORD_DEFAULT);
    $telefone = $_POST['telefone'] ?? '';
    $morada = $_POST['morada'] ?? '';
    $nif = $_POST['nif'] ?? '';
    
    $check = $db->prepare("SELECT id FROM utilizadores WHERE email = :email");
    $check->bindParam(':email', $email);
    $check->execute();
    
    if($check->rowCount() > 0) {
        $erro = '<i class="fas fa-exclamation-triangle"></i> Email já registado!';
    } else {
        $query = "INSERT INTO utilizadores (nome, email, senha, telefone, morada, nif, cargo, status) 
                  VALUES (:nome, :email, :senha, :telefone, :morada, :nif, 'cliente', 'ativo')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':senha', $senha);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':morada', $morada);
        $stmt->bindParam(':nif', $nif);
        
        if($stmt->execute()) {
            $mensagem = '<i class="fas fa-check-circle"></i> Cliente criado com sucesso! Senha: 123456';
            echo '<script>setTimeout(function(){ window.location.href = "clientes.php"; }, 2000);</script>';
        } else {
            $erro = '<i class="fas fa-exclamation-triangle"></i> Erro ao criar cliente';
        }
    }
}

// Processar ativação/desativação
if(isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = $_GET['status'] ?? '';
    $novoStatus = $status == 'ativo' ? 'inativo' : 'ativo';
    
    $query = "UPDATE utilizadores SET status = :status WHERE id = :id AND cargo = 'cliente'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $novoStatus);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    header('Location: clientes.php');
    exit();
}

// Buscar clientes
$query = "SELECT * FROM utilizadores WHERE cargo = 'cliente'";
if($busca) {
    $query .= " AND (nome LIKE :busca OR email LIKE :busca OR telefone LIKE :busca)";
}
$query .= " ORDER BY id DESC";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Clientes - Funcionário</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .container-app { display: flex; min-height: 100vh; }
        .conteudo-principal { flex: 1; margin-left: 270px; padding: 1.5rem; }
        
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #1E3A5F, #2a5298);
        }
        
        .card-header h3 { color: white; margin: 0; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        
        .btn-primary {
            background: #FF8C00;
            color: white;
            border: none;
            padding: 0.4rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
        }
        .btn-primary:hover { background: #e67e00; transform: translateY(-2px); }
        
        .btn-info {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 0.3rem 0.7rem;
            border-radius: 5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.7rem;
            text-decoration: none;
        }
        .btn-info:hover { background: #138496; }
        
        .btn-warning {
            background: #ffc107;
            color: #333;
            border: none;
            padding: 0.3rem 0.7rem;
            border-radius: 5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.7rem;
        }
        .btn-warning:hover { background: #e0a800; }
        
        .btn-success {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.3rem 0.7rem;
            border-radius: 5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.7rem;
        }
        .btn-success:hover { background: #218838; }
        
        .btn-secundario {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.3rem 0.7rem;
            border-radius: 5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.7rem;
            text-decoration: none;
        }
        
        .table-container { overflow-x: auto; }
        .table { width: 100%; border-collapse: collapse; }
        .table th {
            padding: 0.8rem;
            text-align: left;
            font-weight: 600;
            color: #1E3A5F;
            border-bottom: 2px solid #FF8C00;
            font-size: 0.75rem;
            text-transform: uppercase;
        }
        .table td {
            padding: 0.8rem;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }
        .table tr:hover td { background: #fef9e6; }
        
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        
        .form-control {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.85rem;
        }
        
        .tabela-acoes { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        
        /* MODAL */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 600px;
            width: 90%;
            overflow: hidden;
            animation: modalFadeIn 0.3s ease;
        }
        
        @keyframes modalFadeIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        
        .modal-header {
            background: linear-gradient(135deg, #1E3A5F, #2a5298);
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 { color: white; margin: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem; }
        .modal-close { background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; }
        
        .modal-body { padding: 1.5rem; }
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.3rem;
            font-size: 0.8rem;
            font-weight: 500;
            color: #1E3A5F;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .btn-save {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .alert-info {
            background: #d1ecf1;
            padding: 0.8rem;
            border-radius: 8px;
            margin-top: 0.5rem;
            font-size: 0.8rem;
        }
        
        /* MODAL DE CONFIRMAÇÃO */
        .modal-confirm {
            background: white;
            border-radius: 16px;
            max-width: 420px;
            width: 90%;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        
        .modal-confirm .modal-header {
            background: linear-gradient(135deg, #1E3A5F, #2a5298);
            padding: 1rem 1.5rem;
        }
        
        .modal-confirm .modal-header h3 { color: white; margin: 0; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .modal-confirm .modal-body { padding: 1.5rem; text-align: center; }
        .modal-confirm .modal-icon { font-size: 3rem; margin-bottom: 1rem; }
        .modal-confirm .modal-message { font-size: 0.95rem; color: #333; margin-bottom: 0.5rem; }
        .modal-confirm .modal-details { font-size: 0.8rem; color: #666; }
        .modal-confirm .modal-footer {
            padding: 1rem 1.5rem 1.5rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
            border-top: none;
        }
        
        .btn-cancel-modal {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        .btn-cancel-modal:hover { background: #5a6268; transform: translateY(-2px); }
        
        .btn-confirm-modal {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        .btn-confirm-modal:hover { background: #c82333; transform: translateY(-2px); }
        .btn-confirm-success { background: #28a745; }
        .btn-confirm-success:hover { background: #218838; }
        
        .notificacao {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            z-index: 9999;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .notificacao.sucesso { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .notificacao.erro { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @media (max-width: 768px) {
            .conteudo-principal { margin-left: 0; padding: 1rem; }
            .form-row { grid-template-columns: 1fr; }
            .tabela-acoes { flex-direction: column; }
        }
    </style>
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
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> Gestão de Clientes</h3>
                    <div style="display: flex; gap: 0.5rem;">
                        <form method="GET" style="display: flex; gap: 0.3rem;">
                            <input type="text" name="busca" class="form-control" placeholder="Buscar cliente..." value="<?= htmlspecialchars($busca) ?>" style="width: 200px;">
                            <button type="submit" class="btn-info"><i class="fas fa-search"></i> Buscar</button>
                            <?php if($busca): ?>
                                <a href="clientes.php" class="btn-secundario"><i class="fas fa-times"></i> Limpar</a>
                            <?php endif; ?>
                        </form>
                        <button class="btn-primary" onclick="abrirModalNovoCliente()">
                            <i class="fas fa-plus"></i> Novo Cliente
                        </button>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th><i class="fas fa-user"></i> Utilizador</th>
                                <th><i class="fas fa-envelope"></i> Email</th>
                                <th><i class="fas fa-phone"></i> Telefone</th>
                                <th><i class="fas fa-id-card"></i> NUIT</th>
                                <th><i class="fas fa-chart-line"></i> Status</th>
                                <th><i class="fas fa-cogs"></i> Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($clientes as $cliente): ?>
                            <tr>
                                <td><span style="font-weight: bold; color: #1E3A5F;">#<?= $cliente['id'] ?></span></td>
                                <td>
                                    <strong><?= htmlspecialchars($cliente['nome']) ?></strong>
                                    <?php if($cliente['morada']): ?>
                                        <br><small><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars(substr($cliente['morada'], 0, 30)) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><i class="fas fa-envelope"></i> <?= htmlspecialchars($cliente['email']) ?></td>
                                <td><i class="fas fa-phone"></i> <?= $cliente['telefone'] ?? '---' ?></td>
                                <td><i class="fas fa-id-card"></i> <?= $cliente['nif'] ?? '---' ?></td>
                                <td>
                                    <?php if($cliente['status'] == 'ativo'): ?>
                                        <span class="badge badge-success"><i class="fas fa-check-circle"></i> Ativo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><i class="fas fa-ban"></i> Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="tabela-acoes">
                                    <a href="historico_cliente.php?id=<?= $cliente['id'] ?>" class="btn-info">
                                        <i class="fas fa-history"></i> Histórico
                                    </a>
                                    <?php if($cliente['status'] == 'ativo'): ?>
                                        <button class="btn-warning" onclick="confirmarDesativar(<?= $cliente['id'] ?>, '<?= addslashes($cliente['nome']) ?>')">
                                            <i class="fas fa-ban"></i> Desativar
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-success" onclick="confirmarAtivar(<?= $cliente['id'] ?>, '<?= addslashes($cliente['nome']) ?>')">
                                            <i class="fas fa-check"></i> Ativar
                                        </button>
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
    
    <!-- MODAL NOVO CLIENTE -->
    <div id="modalNovoCliente" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Novo Cliente</h3>
                <button class="modal-close" onclick="fecharModalNovoCliente()">&times;</button>
            </div>
            <form method="POST" action="clientes.php">
                <input type="hidden" name="acao" value="criar">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Nome Completo *</label>
                            <input type="text" name="nome" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Telefone</label>
                            <input type="tel" name="telefone" class="form-control">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-id-card"></i> NUIT</label>
                            <input type="text" name="nif" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Morada</label>
                        <textarea name="morada" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="alert-info">
                        <i class="fas fa-info-circle"></i> A senha padrão será: <strong>123456</strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="fecharModalNovoCliente()"><i class="fas fa-times"></i> Cancelar</button>
                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- MODAL DE CONFIRMAÇÃO -->
    <div id="modalConfirmacao" class="modal">
        <div class="modal-confirm">
            <div class="modal-header">
                <h3><i class="fas fa-question-circle"></i> Confirmar Ação</h3>
            </div>
            <div class="modal-body">
                <div class="modal-icon" id="modalIcone">
                    <i class="fas fa-exclamation-triangle" style="color: #ffc107; font-size: 3rem;"></i>
                </div>
                <div class="modal-message" id="modalMensagem">
                    Tem certeza que deseja realizar esta ação?
                </div>
                <div class="modal-details" id="modalDetalhes"></div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel-modal" onclick="fecharModalConfirmacao()"><i class="fas fa-times"></i> Cancelar</button>
                <button class="btn-confirm-modal" id="btnConfirmarAcao"><i class="fas fa-check"></i> Confirmar</button>
            </div>
        </div>
    </div>
    
    <script>
        let acaoConfirmada = null;
        let idConfirmado = null;
        
        function abrirModalNovoCliente() {
            document.getElementById('modalNovoCliente').style.display = 'flex';
        }
        
        function fecharModalNovoCliente() {
            document.getElementById('modalNovoCliente').style.display = 'none';
        }
        
        function confirmarDesativar(id, nome) {
            document.getElementById('modalIcone').innerHTML = '<i class="fas fa-ban" style="color: #dc3545; font-size: 3rem;"></i>';
            document.getElementById('modalMensagem').innerHTML = 'Desativar este cliente?';
            document.getElementById('modalDetalhes').innerHTML = '<strong>Cliente:</strong> ' + nome;
            
            const btnConfirmar = document.getElementById('btnConfirmarAcao');
            btnConfirmar.className = 'btn-confirm-modal';
            btnConfirmar.innerHTML = '<i class="fas fa-ban"></i> Sim, desativar';
            
            idConfirmado = id;
            acaoConfirmada = 'desativar';
            document.getElementById('modalConfirmacao').style.display = 'flex';
        }
        
        function confirmarAtivar(id, nome) {
            document.getElementById('modalIcone').innerHTML = '<i class="fas fa-check-circle" style="color: #28a745; font-size: 3rem;"></i>';
            document.getElementById('modalMensagem').innerHTML = 'Ativar este cliente?';
            document.getElementById('modalDetalhes').innerHTML = '<strong>Cliente:</strong> ' + nome;
            
            const btnConfirmar = document.getElementById('btnConfirmarAcao');
            btnConfirmar.className = 'btn-confirm-modal btn-confirm-success';
            btnConfirmar.innerHTML = '<i class="fas fa-check"></i> Sim, ativar';
            
            idConfirmado = id;
            acaoConfirmada = 'ativar';
            document.getElementById('modalConfirmacao').style.display = 'flex';
        }
        
        function fecharModalConfirmacao() {
            document.getElementById('modalConfirmacao').style.display = 'none';
            idConfirmado = null;
            acaoConfirmada = null;
        }
        
        function executarAcao() {
            if (acaoConfirmada === 'desativar' && idConfirmado) {
                window.location.href = '?toggle_status=1&id=' + idConfirmado + '&status=ativo';
            } else if (acaoConfirmada === 'ativar' && idConfirmado) {
                window.location.href = '?toggle_status=1&id=' + idConfirmado + '&status=inativo';
            }
        }
        
        document.getElementById('btnConfirmarAcao').onclick = function() {
            executarAcao();
            fecharModalConfirmacao();
        };
        
        window.onclick = function(event) {
            const modalNovo = document.getElementById('modalNovoCliente');
            const modalConfirm = document.getElementById('modalConfirmacao');
            if (event.target === modalNovo) fecharModalNovoCliente();
            if (event.target === modalConfirm) fecharModalConfirmacao();
        }
        
        document.getElementById('modalNovoCliente').style.display = 'none';
        document.getElementById('modalConfirmacao').style.display = 'none';
    </script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/funcionario.js"></script>
</body>
</html>