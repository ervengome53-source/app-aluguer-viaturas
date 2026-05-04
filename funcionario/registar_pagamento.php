<?php
require_once '../config/auth.php';
Auth::cargo(['funcionario']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$funcionario_id = $_SESSION['utilizador_id'];

$mensagem = '';
$erro = '';

// Processar registo de pagamento manual
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cliente_id = $_POST['cliente_id'];
    $aluguer_id = $_POST['aluguer_id'] ?? null;
    $reserva_id = $_POST['reserva_id'] ?? null;
    $valor = $_POST['valor'];
    $metodo = $_POST['metodo_pagamento'];
    $observacoes = $_POST['observacoes'];
    
    $referencia = 'MANUAL-' . date('Ymd') . '-' . strtoupper(uniqid());
    
    $query = "INSERT INTO pagamentos (utilizador_id, aluguer_id, reserva_id, valor, metodo_pagamento, 
              referencia_pagamento, estado, data_pagamento, dados_transacao) 
              VALUES (:cliente_id, :aluguer_id, :reserva_id, :valor, :metodo, :referencia, 'confirmado', NOW(), :observacoes)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':cliente_id', $cliente_id);
    $stmt->bindParam(':aluguer_id', $aluguer_id);
    $stmt->bindParam(':reserva_id', $reserva_id);
    $stmt->bindParam(':valor', $valor);
    $stmt->bindParam(':metodo', $metodo);
    $stmt->bindParam(':referencia', $referencia);
    $stmt->bindParam(':observacoes', $observacoes);
    
    if($stmt->execute()) {
        $pagamento_id = $db->lastInsertId();
        
        // Atualizar status da reserva se existir
        if($reserva_id) {
            $query = "UPDATE reservas SET pagamento_status = 'pago' WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $reserva_id);
            $stmt->execute();
        }
        
        $mensagem = 'Pagamento registado com sucesso! Referência: ' . $referencia;
        
        // Redirecionar para recibo
        echo "<script>setTimeout(() => { window.open('/pagamentos/recibo.php?id={$pagamento_id}', '_blank'); window.location.href = 'pagamentos.php'; }, 1500);</script>";
    } else {
        $erro = 'Erro ao registar pagamento';
    }
}

// Buscar clientes
$query = "SELECT id, nome, email FROM utilizadores WHERE cargo = 'cliente' ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->execute();
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registar Pagamento Manual - Funcionário</title>
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
                    <h3 class="cartao-titulo"> Registar Pagamento Manual</h3>
                </div>
                
                <form method="POST" class="form-pagamento-manual">
                    <div class="form-row">
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Cliente *</label>
                            <select name="cliente_id" id="cliente_id" class="controlo-formulario" required>
                                <option value="">Selecionar cliente</option>
                                <?php foreach($clientes as $cliente): ?>
                                    <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['nome'] . ' - ' . $cliente['email']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Valor (MZN) *</label>
                            <input type="number" step="0.01" name="valor" class="controlo-formulario" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Método de Pagamento *</label>
                            <select name="metodo_pagamento" class="controlo-formulario" required>
                                <option value="dinheiro"> Dinheiro</option>
                                <option value="cartao_credito"> Cartão Crédito</option>
                                <option value="cartao_debito"> Cartão Débito</option>
                                <option value="transferencia"> Transferência Bancária</option>
                                <option value="mbway"> MB WAY</option>
                                <option value="paypal"> PayPal</option>
                            </select>
                        </div>
                        
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Referência (opcional)</label>
                            <input type="text" name="referencia" class="controlo-formulario" placeholder="Referência do cliente">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Aluguer (opcional)</label>
                            <select name="aluguer_id" id="aluguer_id" class="controlo-formulario">
                                <option value="">Selecionar aluguer</option>
                            </select>
                        </div>
                        
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Reserva (opcional)</label>
                            <select name="reserva_id" id="reserva_id" class="controlo-formulario">
                                <option value="">Selecionar reserva</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grupo-formulario">
                        <label class="rotulo-formulario">Observações</label>
                        <textarea name="observacoes" class="controlo-formulario" rows="3" placeholder="Motivo do pagamento, notas adicionais..."></textarea>
                    </div>
                    
                    <div class="info-pagamento">
                        <h4>Informações importantes:</h4>
                        <ul>
                            <li>Este pagamento será registado como CONFIRMADO imediatamente</li>
                            <li>O recibo será gerado automaticamente</li>
                            <li>O cliente receberá notificação por email</li>
                        </ul>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secundario" onclick="window.location.href='pagamentos.php'">Cancelar</button>
                        <button type="submit" class="btn btn-destaque"> Registrar Pagamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <style>
        .info-pagamento {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        .info-pagamento ul {
            margin: 0.5rem 0 0 1.5rem;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1rem;
        }
    </style>
    
    <script>
        document.getElementById('cliente_id').addEventListener('change', async function() {
            const clienteId = this.value;
            if(clienteId) {
                // Carregar aluguéis do cliente
                const alugueisResult = await API.get(`../api/alugueis.php?acao=por_cliente&cliente_id=${clienteId}&status=ativo`);
                const alugueisSelect = document.getElementById('aluguer_id');
                if(alugueisResult && alugueisResult.sucesso) {
                    alugueisSelect.innerHTML = '<option value="">Selecionar aluguer</option>' + 
                        alugueisResult.dados.map(a => `<option value="${a.id}">${a.marca} ${a.modelo} - € ${parseFloat(a.preco_total).toFixed(2)}</option>`).join('');
                }
                
                // Carregar reservas do cliente
                const reservasResult = await API.get(`../api/reservas.php?acao=por_cliente&cliente_id=${clienteId}&status=confirmada`);
                const reservasSelect = document.getElementById('reserva_id');
                if(reservasResult && reservasResult.sucesso) {
                    reservasSelect.innerHTML = '<option value="">Selecionar reserva</option>' + 
                        reservasResult.dados.map(r => `<option value="${r.id}">${r.marca} ${r.modelo} - ${r.data_inicio} a ${r.data_fim} - € ${parseFloat(r.preco_total).toFixed(2)}</option>`).join('');
                }
            }
        });
    </script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/funcionario.js"></script>
</body>
</html>