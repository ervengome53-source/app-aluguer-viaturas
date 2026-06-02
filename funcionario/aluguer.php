<?php
require_once '../config/auth.php';
Auth::cargo(['funcionario']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$funcionario_id = $_SESSION['utilizador_id'];

$reserva_id = $_GET['reserva_id'] ?? null;
$cliente_id = $_GET['cliente_id'] ?? null;
$mensagem = '';
$erro = '';
$aluguer_registado = false;

// Processar registo de aluguer
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'registar_aluguer') {
    $cliente_id = $_POST['cliente_id'];
    $viatura_id = $_POST['viatura_id'];
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    $total_dias = $_POST['total_dias'];
    $preco_total = $_POST['preco_total'];
    $reserva_id = !empty($_POST['reserva_id']) ? $_POST['reserva_id'] : null;
    
    $db->beginTransaction();
    
    try {
        $reserva_id_param = ($reserva_id && $reserva_id != 'null' && $reserva_id != '') ? $reserva_id : null;
        
        $query = "INSERT INTO alugueis (reserva_id, utilizador_id, viatura_id, funcionario_id, 
                  data_inicio, data_fim, total_dias, preco_total, status) 
                  VALUES (:reserva_id, :cliente_id, :viatura_id, :funcionario_id, 
                  :data_inicio, :data_fim, :total_dias, :preco_total, 'ativo')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':reserva_id', $reserva_id_param);
        $stmt->bindParam(':cliente_id', $cliente_id);
        $stmt->bindParam(':viatura_id', $viatura_id);
        $stmt->bindParam(':funcionario_id', $funcionario_id);
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->bindParam(':total_dias', $total_dias);
        $stmt->bindParam(':preco_total', $preco_total);
        $stmt->execute();
        $aluguer_id = $db->lastInsertId();
        
        $query = "UPDATE viaturas SET status = 'alugado' WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $viatura_id);
        $stmt->execute();
        
        if($reserva_id_param) {
            $query = "UPDATE reservas SET status = 'confirmada' WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $reserva_id_param);
            $stmt->execute();
        }
        
        $db->commit();
        $aluguer_registado = true;
        $mensagem = 'Aluguer registado com sucesso!';
        
    } catch(Exception $e) {
        $db->rollBack();
        $erro = 'Erro ao registar aluguer: ' . $e->getMessage();
    }
}

// Processar cadastro rápido de cliente
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'cadastrar_cliente') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'] ?? '';
    $morada = $_POST['morada'] ?? '';
    $nif = $_POST['nif'] ?? '';
    $senha = password_hash('123456', PASSWORD_DEFAULT);
    
    $check = $db->prepare("SELECT id FROM utilizadores WHERE email = :email");
    $check->bindParam(':email', $email);
    $check->execute();
    
    if($check->rowCount() > 0) {
        $erro_cadastro = 'Email já registado!';
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
            $novo_cliente_id = $db->lastInsertId();
            $sucesso_cadastro = true;
            $mensagem_cadastro = 'Cliente cadastrado com sucesso! Senha padrão: 123456';
            echo "<script>setTimeout(() => { window.location.href = 'aluguer.php?cliente_id={$novo_cliente_id}'; }, 2000);</script>";
        } else {
            $erro_cadastro = 'Erro ao cadastrar cliente';
        }
    }
}

// Buscar cliente se fornecida
$cliente = null;
if($cliente_id) {
    $query = "SELECT * FROM utilizadores WHERE id = :id AND cargo = 'cliente'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $cliente_id);
    $stmt->execute();
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Buscar reserva se fornecida
$reserva = null;
if($reserva_id) {
    $query = "SELECT r.*, u.nome as cliente_nome, u.email as cliente_email, u.telefone as cliente_telefone,
              v.marca, v.modelo, v.matricula, v.preco_dia, v.id as viatura_id
              FROM reservas r
              JOIN utilizadores u ON r.utilizador_id = u.id
              JOIN viaturas v ON r.viatura_id = v.id
              WHERE r.id = :id AND r.status = 'confirmada'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $reserva_id);
    $stmt->execute();
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($reserva) {
        $cliente = [
            'id' => $reserva['utilizador_id'],
            'nome' => $reserva['cliente_nome'],
            'email' => $reserva['cliente_email'],
            'telefone' => $reserva['cliente_telefone']
        ];
    }
}

// Buscar viaturas disponíveis
$query = "SELECT * FROM viaturas WHERE status = 'disponivel' ORDER BY marca, modelo";
$stmt = $db->prepare($query);
$stmt->execute();
$viaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Registar Aluguer - SIGAV</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fb;
            overflow-x: hidden;
        }

        .container-app {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        .barra-lateral {
            width: 280px;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            transition: all 0.3s ease;
        }

        .conteudo-principal {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            background: #f5f7fb;
            min-height: 100vh;
            width: calc(100% - 280px);
        }

        .barra-superior {
            background: white;
            border-radius: 1rem;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #666;
            font-size: 0.95rem;
        }

        /* Card Principal */
        .card {
            background: white;
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            max-width: 1200px;
            margin: 0 auto;
        }

        .card-header {
            padding: 1.5rem 2rem;
            background: white;
            border-bottom: 1px solid #eee;
        }

        .card-header h2 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1a1a2e;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-body {
            padding: 2rem;
        }

        /* Formulário */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group label i {
            color: #FF8C00;
            width: 18px;
        }

        .form-control {
            padding: 0.75rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 0.8rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            font-family: inherit;
            width: 100%;
        }

        .form-control:focus {
            outline: none;
            border-color: #FF8C00;
            box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.1);
        }

        /* Busca Cliente */
        .busca-container {
            position: relative;
        }

        .resultados-busca {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 0.8rem;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .resultados-busca.active {
            display: block;
        }

        .resultado-item {
            padding: 0.8rem 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .resultado-item:hover {
            background: #fef9e6;
        }

        .resultado-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #FF8C00, #FF6B00);
            border-radius: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .resultado-info strong {
            display: block;
            color: #1a1a2e;
        }

        .resultado-info small {
            color: #999;
            font-size: 0.7rem;
        }

        .btn-add-cliente {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.7rem 1rem;
            border-radius: 0.8rem;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
            width: 100%;
        }

        .btn-add-cliente:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        /* Cliente Info */
        .cliente-info {
            background: #fef9e6;
            border-radius: 1rem;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-left: 4px solid #FF8C00;
        }

        .cliente-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #FF8C00, #FF6B00);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .cliente-detalhes h4 {
            font-size: 1rem;
            margin-bottom: 0.2rem;
        }

        .cliente-detalhes p {
            font-size: 0.8rem;
            color: #666;
        }

        .btn-trocar {
            background: #17a2b8;
            margin-top: 0;
        }

        .btn-trocar:hover {
            background: #138496;
        }

        /* Resumo */
        .resumo-card {
            background: linear-gradient(135deg, #f8f9fa, #fff);
            border-radius: 1rem;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border: 1px solid #eee;
        }

        .resumo-card h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .resumo-linha {
            display: flex;
            justify-content: space-between;
            padding: 0.6rem 0;
            border-bottom: 1px dashed #e0e0e0;
        }

        .resumo-linha.total {
            margin-top: 0.5rem;
            padding-top: 0.8rem;
            border-top: 2px solid #FF8C00;
            border-bottom: none;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .resumo-linha.total span:last-child {
            color: #FF8C00;
            font-size: 1.2rem;
        }

        /* Botões */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 0.75rem 1.8rem;
            border-radius: 0.8rem;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FF8C00, #FF6B00);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 140, 0, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 1.5rem;
            width: 90%;
            max-width: 600px;
            animation: modalFadeIn 0.3s ease;
            overflow: hidden;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            padding: 1.2rem 1.5rem;
            background: white;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a1a2e;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-close {
            background: none;
            border: none;
            color: #666;
            font-size: 1.3rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            transform: rotate(90deg);
            color: #dc3545;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1rem 1.5rem 1.5rem;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            border-top: 1px solid #eee;
        }

        /* Modal Confirmação */
        .modal-confirmacao {
            max-width: 500px;
        }

        .modal-confirmacao .modal-header {
            background: white;
            border-bottom: 1px solid #eee;
        }

        .modal-confirmacao .modal-header h3 {
            color: #1a1a2e;
        }

        .modal-confirmacao .resumo-confirmacao {
            background: #f8f9fa;
            border-radius: 1rem;
            padding: 1rem;
            margin: 1rem 0;
        }

        .alert-info {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 0.8rem;
            border-radius: 0.8rem;
            margin-bottom: 1rem;
            font-size: 0.8rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        /* Responsivo */
        @media (max-width: 768px) {
            .barra-lateral {
                width: 0;
                transform: translateX(-100%);
            }
            .conteudo-principal {
                margin-left: 0;
                width: 100%;
                padding: 1rem;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-group.full-width {
                grid-column: span 1;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
            .card-body {
                padding: 1rem;
            }
            .form-actions {
                flex-direction: column;
            }
            .btn {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <div class="page-header">
                <h1>Registar Novo Aluguer</h1>
                <p>Registe um novo aluguer de viatura</p>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-key"></i>
                        Dados do Aluguer
                    </h2>
                </div>
                <div class="card-body">
                    <form method="POST" id="formAluguer">
                        <input type="hidden" name="acao" value="registar_aluguer">
                        <div class="form-grid">
                            <!-- Cliente -->
                            <div class="form-group full-width">
                                <label><i class="fas fa-user"></i> Cliente</label>
                                <?php if($cliente): ?>
                                    <input type="hidden" name="cliente_id" value="<?= $cliente['id'] ?>">
                                    <div class="cliente-info">
                                        <div class="cliente-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="cliente-detalhes">
                                            <h4><?= htmlspecialchars($cliente['nome']) ?></h4>
                                            <p><i class="fas fa-envelope"></i> <?= $cliente['email'] ?> | <i class="fas fa-phone"></i> <?= $cliente['telefone'] ?? '---' ?></p>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-add-cliente btn-trocar" onclick="trocarCliente()">
                                        <i class="fas fa-exchange-alt"></i> Trocar Cliente
                                    </button>
                                <?php else: ?>
                                    <div class="busca-container">
                                        <input type="text" id="busca_cliente" class="form-control" placeholder="Digite nome, email ou telefone do cliente..." autocomplete="off">
                                        <input type="hidden" name="cliente_id" id="cliente_id" required>
                                        <div id="resultadosBusca" class="resultados-busca"></div>
                                    </div>
                                    <button type="button" class="btn-add-cliente" onclick="abrirModalCadastroCliente()">
                                        <i class="fas fa-user-plus"></i> Registar Novo Cliente
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Viatura -->
                            <div class="form-group">
                                <label><i class="fas fa-car"></i> Viatura</label>
                                <select name="viatura_id" id="viatura_id" class="form-control" required <?= $reserva ? 'disabled' : '' ?>>
                                    <option value="">Selecionar viatura</option>
                                    <?php foreach($viaturas as $v): ?>
                                        <option value="<?= $v['id'] ?>" data-preco="<?= $v['preco_dia'] ?>" data-modelo="<?= htmlspecialchars($v['marca'] . ' ' . $v['modelo']) ?>" data-matricula="<?= $v['matricula'] ?>"
                                            <?= ($reserva && $reserva['viatura_id'] == $v['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($v['marca'] . ' ' . $v['modelo'] . ' - ' . $v['matricula']) ?> 
                                            (MZN <?= number_format($v['preco_dia'], 2) ?>/dia)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if($reserva): ?>
                                    <input type="hidden" name="viatura_id" value="<?= $reserva['viatura_id'] ?>">
                                <?php endif; ?>
                            </div>
                            
                            <!-- Data Início -->
                            <div class="form-group">
                                <label><i class="fas fa-calendar-alt"></i> Data de Início</label>
                                <input type="date" name="data_inicio" id="data_inicio" class="form-control" 
                                       value="<?= $reserva ? $reserva['data_inicio'] : date('Y-m-d') ?>" required>
                            </div>
                            
                            <!-- Data Fim -->
                            <div class="form-group">
                                <label><i class="fas fa-calendar-check"></i> Data de Fim</label>
                                <input type="date" name="data_fim" id="data_fim" class="form-control" 
                                       value="<?= $reserva ? $reserva['data_fim'] : '' ?>" required>
                            </div>
                        </div>
                        
                        <input type="hidden" name="reserva_id" id="reserva_id" value="<?= $reserva_id ?>">
                        <input type="hidden" name="total_dias" id="total_dias">
                        <input type="hidden" name="preco_total" id="preco_total">
                        
                        <div class="resumo-card">
                            <h3><i class="fas fa-calculator"></i> Resumo do Aluguer</h3>
                            <div class="resumo-linha">
                                <span><i class="fas fa-calendar-week"></i> Dias</span>
                                <span id="resumo_dias">0</span>
                            </div>
                            <div class="resumo-linha">
                                <span><i class="fas fa-tag"></i> Preço por dia</span>
                                <span id="resumo_preco_dia">MZN 0,00</span>
                            </div>
                            <div class="resumo-linha">
                                <span><i class="fas fa-car"></i> Viatura</span>
                                <span id="resumo_viatura">---</span>
                            </div>
                            <div class="resumo-linha total">
                                <span><i class="fas fa-money-bill-wave"></i> Total</span>
                                <span id="resumo_total">MZN 0,00</span>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="window.location.href='dashboard.php'">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                            <button type="button" class="btn btn-primary" onclick="confirmarAluguer()">
                                <i class="fas fa-save"></i> Registar Aluguer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- MODAL CADASTRO RÁPIDO DE CLIENTE (EM BRANCO) -->
    <div id="modalCadastroCliente" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Registar Novo Cliente</h3>
                <button class="modal-close" onclick="fecharModalCadastro()">&times;</button>
            </div>
            <form method="POST" id="formCadastroCliente">
                <input type="hidden" name="acao" value="cadastrar_cliente">
                <div class="modal-body">
                    <?php if(isset($erro_cadastro)): ?>
                        <div class="alert-info" style="background: #f8d7da; border-left-color: #dc3545; color: #721c24; margin-bottom: 1rem;">
                            <i class="fas fa-exclamation-circle"></i> <?= $erro_cadastro ?>
                        </div>
                    <?php endif; ?>
                    <div class="form-row">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-user"></i> Nome Completo *</label>
                            <input type="text" name="nome" id="cadastro_nome" class="form-control" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-envelope"></i> Email *</label>
                            <input type="email" name="email" id="cadastro_email" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-phone"></i> Telefone</label>
                            <input type="tel" name="telefone" id="cadastro_telefone" class="form-control" placeholder="+258 84 123 4567">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-id-card"></i> NUIT</label>
                            <input type="text" name="nif" id="cadastro_nif" class="form-control" placeholder="Número de identificação fiscal">
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt"></i> Morada</label>
                        <textarea name="morada" id="cadastro_morada" class="form-control" rows="2" placeholder="Morada completa do cliente"></textarea>
                    </div>
                    <div class="alert-info">
                        <i class="fas fa-info-circle"></i>
                        A senha padrão será: <strong>******</strong>. O cliente deverá alterar no primeiro acesso.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="fecharModalCadastro()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Registar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- MODAL CONFIRMAÇÃO DE ALUGUER -->
    <div id="modalConfirmacaoAluguer" class="modal">
        <div class="modal-content modal-confirmacao">
            <div class="modal-header">
                <h3><i class="fas fa-question-circle"></i> Confirmar Aluguer</h3>
                <button class="modal-close" onclick="fecharModalConfirmacao()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja registar este aluguer?</p>
                <div class="resumo-confirmacao">
                    <div class="resumo-linha">
                        <span><i class="fas fa-user"></i> Cliente:</span>
                        <span id="confirm_cliente">---</span>
                    </div>
                    <div class="resumo-linha">
                        <span><i class="fas fa-car"></i> Viatura:</span>
                        <span id="confirm_viatura">---</span>
                    </div>
                    <div class="resumo-linha">
                        <span><i class="fas fa-calendar"></i> Período:</span>
                        <span id="confirm_periodo">---</span>
                    </div>
                    <div class="resumo-linha total">
                        <span><i class="fas fa-money-bill-wave"></i> Total:</span>
                        <span id="confirm_total">---</span>
                    </div>
                </div>
                <div class="alert-info">
                    <i class="fas fa-info-circle"></i>
                    Ao confirmar, a viatura será marcada como <strong>ALUGADA</strong> e o status será atualizado automaticamente.
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="fecharModalConfirmacao()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button class="btn btn-primary" onclick="submitFormulario()">
                    <i class="fas fa-check"></i> Confirmar Aluguer
                </button>
            </div>
        </div>
    </div>
    
    <!-- MODAL SUCESSO -->
    <div id="modalSucesso" class="modal">
        <div class="modal-content modal-confirmacao">
            <div class="modal-header" style="background: white; border-bottom: 1px solid #eee;">
                <h3 style="color: #28a745;"><i class="fas fa-check-circle"></i> Aluguer Registado!</h3>
                <button class="modal-close" onclick="fecharModalSucesso()">&times;</button>
            </div>
            <div class="modal-body" style="text-align: center;">
                <i class="fas fa-check-circle" style="font-size: 4rem; color: #28a745; margin-bottom: 1rem;"></i>
                <p style="font-size: 1.1rem; margin-bottom: 0.5rem;"><?= $mensagem ?></p>
                <p style="color: #666;">Redirecionando para o dashboard...</p>
            </div>
        </div>
    </div>
    
    <script>
        let precoDia = <?= $reserva ? $reserva['preco_dia'] : 0 ?>;
        let viaturaSelecionada = '';
        let clienteSelecionado = '';
        
        <?php if($aluguer_registado): ?>
        // Mostrar modal de sucesso
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('modalSucesso').classList.add('active');
            setTimeout(() => {
                window.location.href = 'dashboard.php';
            }, 3000);
        });
        <?php endif; ?>
        
        function calcularTotal() {
            const dataInicio = document.getElementById('data_inicio').value;
            const dataFim = document.getElementById('data_fim').value;
            const viaturaSelect = document.getElementById('viatura_id');
            
            if(viaturaSelect && viaturaSelect.selectedIndex > 0) {
                const selected = viaturaSelect.options[viaturaSelect.selectedIndex];
                const modeloViatura = selected.dataset.modelo || '';
                const matriculaViatura = selected.dataset.matricula || '';
                viaturaSelecionada = `${modeloViatura} - ${matriculaViatura}`;
                document.getElementById('resumo_viatura').innerHTML = viaturaSelecionada;
            }
            
            if(dataInicio && dataFim && precoDia > 0) {
                const inicio = new Date(dataInicio);
                const fim = new Date(dataFim);
                const diffTime = Math.abs(fim - inicio);
                const dias = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                const total = dias * precoDia;
                
                document.getElementById('total_dias').value = dias;
                document.getElementById('preco_total').value = total.toFixed(2);
                document.getElementById('resumo_dias').innerHTML = dias + ' dia(s)';
                document.getElementById('resumo_preco_dia').innerHTML = `MZN ${precoDia.toFixed(2)}`;
                document.getElementById('resumo_total').innerHTML = `MZN ${total.toFixed(2)}`;
            }
        }
        
        const viaturaSelect = document.getElementById('viatura_id');
        if(viaturaSelect) {
            viaturaSelect.addEventListener('change', function() {
                const selected = this.options[this.selectedIndex];
                precoDia = parseFloat(selected.dataset.preco || 0);
                calcularTotal();
            });
        }
        
        const dataInicioInput = document.getElementById('data_inicio');
        const dataFimInput = document.getElementById('data_fim');
        
        if(dataInicioInput) dataInicioInput.addEventListener('change', calcularTotal);
        if(dataFimInput) dataFimInput.addEventListener('change', calcularTotal);
        
        // Busca de cliente com autocomplete
        const buscaInput = document.getElementById('busca_cliente');
        if(buscaInput) {
            let timeout;
            buscaInput.addEventListener('input', function(e) {
                clearTimeout(timeout);
                timeout = setTimeout(async () => {
                    if(this.value.length >= 2) {
                        try {
                            const response = await fetch(`../api/clientes.php?busca=${encodeURIComponent(this.value)}`);
                            const resultado = await response.json();
                            const container = document.getElementById('resultadosBusca');
                            
                            if(resultado && resultado.sucesso && resultado.dados.length > 0) {
                                container.innerHTML = resultado.dados.map(c => `
                                    <div class="resultado-item" onclick="selecionarCliente(${c.id}, '${c.nome}', '${c.email}')">
                                        <div class="resultado-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="resultado-info">
                                            <strong>${c.nome}</strong>
                                            <small>${c.email} | ${c.telefone || 'Sem telefone'}</small>
                                        </div>
                                    </div>
                                `).join('');
                                container.classList.add('active');
                            } else {
                                container.innerHTML = `
                                    <div class="resultado-item" onclick="abrirModalCadastroCliente()">
                                        <div class="resultado-avatar">
                                            <i class="fas fa-user-plus"></i>
                                        </div>
                                        <div class="resultado-info">
                                            <strong>Nenhum cliente encontrado</strong>
                                            <small>Clique aqui para cadastrar um novo cliente</small>
                                        </div>
                                    </div>
                                `;
                                container.classList.add('active');
                            }
                        } catch(error) {
                            console.error('Erro na busca:', error);
                        }
                    } else {
                        document.getElementById('resultadosBusca').classList.remove('active');
                    }
                }, 500);
            });
            
            document.addEventListener('click', function(e) {
                if(!e.target.closest('.busca-container')) {
                    document.getElementById('resultadosBusca')?.classList.remove('active');
                }
            });
        }
        
        function selecionarCliente(id, nome, email) {
            document.getElementById('cliente_id').value = id;
            if(buscaInput) buscaInput.value = `${nome} - ${email}`;
            clienteSelecionado = nome;
            document.getElementById('resultadosBusca').classList.remove('active');
        }
        
        function trocarCliente() {
            window.location.href = 'aluguer.php';
        }
        
        function confirmarAluguer() {
            const clienteId = document.getElementById('cliente_id')?.value;
            const viaturaId = document.getElementById('viatura_id')?.value;
            const dataInicio = document.getElementById('data_inicio')?.value;
            const dataFim = document.getElementById('data_fim')?.value;
            
            if(!clienteId || clienteId === '') {
                alert('Por favor, selecione um cliente ou cadastre um novo');
                return false;
            }
            
            if(!viaturaId || viaturaId === '') {
                alert('Por favor, selecione uma viatura');
                return false;
            }
            
            if(!dataInicio || !dataFim) {
                alert('Por favor, selecione as datas');
                return false;
            }
            
            const inicio = new Date(dataInicio);
            const fim = new Date(dataFim);
            
            if(fim < inicio) {
                alert('A data de fim deve ser maior ou igual à data de início');
                return false;
            }
            
            // Preencher dados da confirmação
            const nomeCliente = document.getElementById('busca_cliente')?.value || clienteSelecionado;
            const viaturaNome = document.getElementById('viatura_id').options[document.getElementById('viatura_id').selectedIndex]?.text || '';
            const total = document.getElementById('resumo_total').innerHTML;
            
            document.getElementById('confirm_cliente').innerHTML = nomeCliente || 'Cliente selecionado';
            document.getElementById('confirm_viatura').innerHTML = viaturaNome.split(' - ')[0] || viaturaSelecionada;
            document.getElementById('confirm_periodo').innerHTML = `${formatarData(dataInicio)} até ${formatarData(dataFim)}`;
            document.getElementById('confirm_total').innerHTML = total;
            
            document.getElementById('modalConfirmacaoAluguer').classList.add('active');
        }
        
        function formatarData(data) {
            const d = new Date(data);
            return d.toLocaleDateString('pt-PT');
        }
        
        function submitFormulario() {
            document.getElementById('formAluguer').submit();
        }
        
        function abrirModalCadastroCliente() {
            // Limpar o formulário antes de abrir
            document.getElementById('cadastro_nome').value = '';
            document.getElementById('cadastro_email').value = '';
            document.getElementById('cadastro_telefone').value = '';
            document.getElementById('cadastro_nif').value = '';
            document.getElementById('cadastro_morada').value = '';
            document.getElementById('modalCadastroCliente').classList.add('active');
        }
        
        function fecharModalCadastro() {
            document.getElementById('modalCadastroCliente').classList.remove('active');
        }
        
        function fecharModalConfirmacao() {
            document.getElementById('modalConfirmacaoAluguer').classList.remove('active');
        }
        
        function fecharModalSucesso() {
            document.getElementById('modalSucesso').classList.remove('active');
            window.location.href = 'dashboard.php';
        }
        
        // Fechar modais com ESC
        document.addEventListener('keydown', function(event) {
            if(event.key === 'Escape') {
                fecharModalCadastro();
                fecharModalConfirmacao();
            }
        });
        
        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modalCadastro = document.getElementById('modalCadastroCliente');
            const modalConfirmacao = document.getElementById('modalConfirmacaoAluguer');
            const modalSucesso = document.getElementById('modalSucesso');
            
            if(event.target === modalCadastro) fecharModalCadastro();
            if(event.target === modalConfirmacao) fecharModalConfirmacao();
            if(event.target === modalSucesso) fecharModalSucesso();
        }
        
        if(<?= $reserva ? 'true' : 'false' ?>) {
            window.addEventListener('DOMContentLoaded', () => {
                calcularTotal();
            });
        }
    </script>
</body>
</html>