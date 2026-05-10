<?php
require_once '../config/auth.php';
Auth::cargo(['funcionario']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$funcionario_id = $_SESSION['utilizador_id'];

$mensagem = '';
$erro = '';

// Buscar clientes
$query = "SELECT id, nome, email FROM utilizadores WHERE cargo = 'cliente' ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->execute();
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar registo de pagamento manual
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cliente_id = $_POST['cliente_id'];
    $valor = $_POST['valor'];
    $metodo = $_POST['metodo_pagamento'];
    $observacoes = $_POST['observacoes'];
    $descricao = $_POST['descricao'];
    
    $referencia = 'MAN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    
    $query = "INSERT INTO pagamentos (utilizador_id, valor, metodo_pagamento, referencia_pagamento, dados_transacao, estado) 
              VALUES (:cliente_id, :valor, :metodo, :referencia, :observacoes, 'confirmado')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':cliente_id', $cliente_id);
    $stmt->bindParam(':valor', $valor);
    $stmt->bindParam(':metodo', $metodo);
    $stmt->bindParam(':referencia', $referencia);
    $stmt->bindParam(':observacoes', $observacoes);
    
    if($stmt->execute()) {
        $pagamento_id = $db->lastInsertId();
        $mensagem = " Pagamento registado com sucesso! Referência: $referencia";
        echo "<script>setTimeout(() => { window.open('../pagamentos/recibo.php?id=$pagamento_id', '_blank'); window.location.href = 'pagamentos.php'; }, 2000);</script>";
    } else {
        $erro = ' Erro ao registar pagamento';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registar Pagamento Manual - Funcionário</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <style>
        .form-pagamento {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 1.2rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #1E3A5F;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 0.7rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .btn-submit {
            background: #28a745;
            color: white;
            padding: 0.7rem;
            border: none;
            border-radius: 8px;
            width: 100%;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            background: #FF8C00;
        }
        
        .mensagem {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .erro {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <div class="form-pagamento">
                <h2 style="margin-bottom: 1.5rem;">Registar Pagamento Manual</h2>
                
                <?php if($mensagem): ?>
                    <div class="mensagem"><?= $mensagem ?></div>
                <?php endif; ?>
                
                <?php if($erro): ?>
                    <div class="erro"><?= $erro ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Cliente *</label>
                        <select name="cliente_id" required>
                            <option value="">Selecionar cliente</option>
                            <?php foreach($clientes as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome'] . ' - ' . $c['email']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Valor (MZN) *</label>
                        <input type="number" step="0.01" name="valor" required placeholder="0.00">
                    </div>
                    
                    <div class="form-group">
                        <label>Método de Pagamento *</label>
                        <select name="metodo_pagamento" required>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="cartao">Cartão Crédito</option>
                            <option value="transferencia">Transferência Bancária</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Descrição</label>
                        <input type="text" name="descricao" placeholder="Ex: Pagamento de reserva #123">
                    </div>
                    
                    <div class="form-group">
                        <label>Observações</label>
                        <textarea name="observacoes" rows="3" placeholder="Notas adicionais..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">Registrar Pagamento</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>