<?php
session_start();
require_once '../config/auth.php';
Auth::cargo(['cliente']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$utilizador = Auth::utilizador();

$reserva_id = $_GET['reserva_id'] ?? 0;
$mensagem = '';
$erro = '';

// ============================================
// SE TEM RESERVA_ID -> MOSTRA FORMULÁRIO DE PAGAMENTO
// ============================================
if($reserva_id > 0) {
    $query = "SELECT r.*, v.marca, v.modelo, v.preco_dia,
              (SELECT COUNT(*) FROM pagamentos WHERE reserva_id = r.id AND estado IN ('confirmado', 'pendente')) as total_pagamentos
              FROM reservas r 
              JOIN viaturas v ON r.viatura_id = v.id 
              WHERE r.id = :id AND r.utilizador_id = :utilizador_id AND r.status = 'confirmada'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $reserva_id);
    $stmt->bindParam(':utilizador_id', $utilizador['id']);
    $stmt->execute();
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    $valor_total = $reserva['preco_total'] ?? 0;
    $total_pagamentos = $reserva['total_pagamentos'] ?? 0;
    
    if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao'])) {
        $checkQuery = "SELECT COUNT(*) as total FROM pagamentos WHERE reserva_id = :reserva_id AND estado IN ('confirmado', 'pendente')";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':reserva_id', $reserva_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if($checkResult['total'] > 0) {
            $erro = "Esta reserva já possui um pagamento registado.";
        } else {
            $metodo = $_POST['metodo_pagamento'];
            $referencia = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            $observacoes = $_POST['observacoes'] ?? '';
            
            $dados_especificos = [];
            if($metodo == 'cartao') {
                $dados_especificos = [
                    'numero_cartao' => $_POST['numero_cartao'] ?? '',
                    'validade' => $_POST['validade'] ?? '',
                    'cvv' => $_POST['cvv'] ?? '',
                    'nome_titular' => $_POST['nome_titular'] ?? ''
                ];
            } elseif($metodo == 'carteira_movel') {
                $dados_especificos = [
                    'operadora' => $_POST['operadora'] ?? '',
                    'telefone' => $_POST['telefone_carteira'] ?? '',
                    'pin' => $_POST['pin_carteira'] ?? ''
                ];
            }
            
            $dados_json = json_encode($dados_especificos);
            
            $query = "INSERT INTO pagamentos (utilizador_id, reserva_id, valor, metodo_pagamento, referencia_pagamento, estado, dados_transacao) 
                      VALUES (:user_id, :reserva_id, :valor, :metodo, :referencia, 'pendente', :dados)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $utilizador['id']);
            $stmt->bindParam(':reserva_id', $reserva_id);
            $stmt->bindParam(':valor', $valor_total);
            $stmt->bindParam(':metodo', $metodo);
            $stmt->bindParam(':referencia', $referencia);
            $stmt->bindParam(':dados', $dados_json);
            
            if($stmt->execute()) {
                $_SESSION['mensagem_sucesso'] = "Pagamento registado com sucesso! Aguarde confirmação.";
                header('Location: pagamentos.php');
                exit();
            } else {
                $erro = "Erro ao processar pagamento. Tente novamente.";
            }
        }
    }
    
    ?>
    <!DOCTYPE html>
    <html lang="pt">
    <head>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
        <title>Pagar Reserva - SIGAV</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Inter', sans-serif; background: #f5f7fb; }
            
            .container-app { display: flex; min-height: 100vh; }
            .barra-lateral { width: 280px; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: white; position: fixed; left: 0; top: 0; height: 100vh; overflow-y: auto; z-index: 100; transition: all 0.3s ease; }
            .conteudo-principal { flex: 1; margin-left: 280px; padding: 2rem; background: #f5f7fb; min-height: 100vh; width: calc(100% - 280px); }
            .barra-superior { background: white; border-radius: 1rem; padding: 1rem 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
            
            .page-header { margin-bottom: 2rem; }
            .page-header h1 { font-size: 2rem; font-weight: 700; color: #1a1a2e; margin-bottom: 0.5rem; }
            .page-header p { color: #666; font-size: 0.95rem; }
            
            .pagamento-card {
                background: white;
                border-radius: 1.5rem;
                max-width: 650px;
                margin: 0 auto;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                overflow: hidden;
            }
            
            .card-header {
                padding: 1.5rem;
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
                padding: 1.5rem;
            }
            
            .info-reserva {
                background: #f8f9fa;
                border-radius: 1rem;
                padding: 1rem;
                margin-bottom: 1.5rem;
            }
            
            .info-linha {
                display: flex;
                justify-content: space-between;
                padding: 0.5rem 0;
                border-bottom: 1px solid #eee;
                font-size: 0.85rem;
            }
            
            .info-linha:last-child {
                border-bottom: none;
            }
            
            .info-label { color: #666; }
            .info-valor { font-weight: 600; color: #1a1a2e; }
            .total .info-valor { color: #FF8C00; font-size: 1rem; }
            
            .form-group { margin-bottom: 1.2rem; }
            .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333; font-size: 0.85rem; }
            .form-group label i { color: #FF8C00; width: 20px; }
            
            .form-control {
                width: 100%;
                padding: 0.75rem 1rem;
                border: 2px solid #e0e0e0;
                border-radius: 0.8rem;
                font-size: 0.9rem;
                transition: all 0.3s ease;
            }
            
            .form-control:focus {
                outline: none;
                border-color: #FF8C00;
                box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.1);
            }
            
            select.form-control {
                cursor: pointer;
                background: white;
            }
            
            textarea.form-control {
                resize: vertical;
                min-height: 80px;
            }
            
            .metodos-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
                margin-bottom: 1.5rem;
            }
            
            .metodo-item {
                border: 2px solid #e0e0e0;
                border-radius: 1rem;
                padding: 1rem;
                text-align: center;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            
            .metodo-item:hover {
                border-color: #FF8C00;
                background: #fef9e6;
            }
            
            .metodo-item.selected {
                border-color: #FF8C00;
                background: #fef9e6;
            }
            
            .metodo-item i {
                font-size: 1.8rem;
                color: #FF8C00;
                display: block;
                margin-bottom: 0.5rem;
            }
            
            .metodo-text {
                font-size: 0.8rem;
                font-weight: 500;
                color: #333;
            }
            
            .dados-pagamento {
                background: #f8f9fa;
                border-radius: 1rem;
                padding: 1rem;
                margin-bottom: 1.5rem;
                display: none;
            }
            
            .dados-pagamento.active {
                display: block;
            }
            
            .dados-pagamento h4 {
                font-size: 0.9rem;
                font-weight: 600;
                color: #1a1a2e;
                margin-bottom: 1rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            
            .form-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
                margin-bottom: 1rem;
            }
            
            .alert-info {
                background: #e7f3ff;
                border-left: 4px solid #FF8C00;
                padding: 0.8rem;
                border-radius: 0.8rem;
                font-size: 0.8rem;
                color: #666;
            }
            
            .btn-pagar {
                width: 100%;
                padding: 0.8rem;
                background: #FF8C00;
                color: white;
                border: none;
                border-radius: 0.8rem;
                font-size: 0.9rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
            }
            
            .btn-pagar:hover {
                background: #e67e00;
                transform: translateY(-2px);
            }
            
            .btn-secondary {
                background: #6c757d;
                color: white;
                border: none;
                padding: 0.5rem 1rem;
                border-radius: 0.8rem;
                cursor: pointer;
                font-size: 0.8rem;
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                text-decoration: none;
            }
            
            .btn-secondary:hover {
                background: #5a6268;
                transform: translateY(-2px);
            }
            
            .mensagem { background: #d4edda; color: #155724; padding: 1rem; border-radius: 1rem; margin-bottom: 1rem; border-left: 4px solid #28a745; }
            .erro { background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 1rem; margin-bottom: 1rem; border-left: 4px solid #dc3545; }
            
            .voltar-link {
                display: block;
                text-align: center;
                margin-top: 1rem;
                color: #FF8C00;
                text-decoration: none;
                font-size: 0.85rem;
            }
            
            .voltar-link:hover { text-decoration: underline; }
            
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
            
            .modal.active { display: flex; }
            
            .modal-content {
                background: white;
                border-radius: 1.5rem;
                width: 90%;
                max-width: 450px;
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
                color: #999;
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
            
            .modal-resumo {
                background: #f8f9fa;
                border-radius: 1rem;
                padding: 1rem;
                margin: 1rem 0;
            }
            
            .modal-resumo-linha {
                display: flex;
                justify-content: space-between;
                padding: 0.5rem 0;
                border-bottom: 1px solid #eee;
                font-size: 0.85rem;
            }
            
            .modal-resumo-linha:last-child {
                border-bottom: none;
            }
            
            .modal-resumo-total {
                background: #fef9e6;
                border-radius: 0.8rem;
                padding: 0.8rem;
                margin-top: 0.8rem;
            }
            
            .modal-botoes {
                display: flex;
                gap: 1rem;
                margin-top: 1.5rem;
            }
            
            .btn-modal-cancelar {
                flex: 1;
                padding: 0.7rem;
                background: #6c757d;
                color: white;
                border: none;
                border-radius: 0.8rem;
                cursor: pointer;
                font-weight: 500;
                transition: all 0.3s ease;
            }
            
            .btn-modal-cancelar:hover {
                background: #5a6268;
                transform: translateY(-2px);
            }
            
            .btn-modal-confirmar {
                flex: 1;
                padding: 0.7rem;
                background: #FF8C00;
                color: white;
                border: none;
                border-radius: 0.8rem;
                cursor: pointer;
                font-weight: 500;
                transition: all 0.3s ease;
            }
            
            .btn-modal-confirmar:hover {
                background: #e67e00;
                transform: translateY(-2px);
            }
            
            @media (max-width: 768px) {
                .barra-lateral { width: 0; transform: translateX(-100%); }
                .conteudo-principal { margin-left: 0; width: 100%; padding: 1rem; }
                .metodos-grid { grid-template-columns: 1fr; }
                .form-row { grid-template-columns: 1fr; }
            }
        </style>
    </head>
    <body>
        <div class="container-app">
            <?php include '../components/barra_lateral.php'; ?>
            
            <div class="conteudo-principal">
                <?php include '../components/cabecalho.php'; ?>
                
                <div class="page-header">
                    <h1>Pagar Reserva</h1>
                    <p>Conclua o seu pagamento de forma segura</p>
                </div>
                
                <div class="pagamento-card">
                    <?php if($erro): ?>
                        <div class="erro"><i class="fas fa-exclamation-triangle"></i> <?= $erro ?></div>
                        <a href="reservas.php" class="voltar-link"><i class="fas fa-arrow-left"></i> Voltar para Minhas Reservas</a>
                    <?php elseif($reserva && $total_pagamentos == 0): ?>
                    
                    <div class="card-header">
                        <h2><i class="fas fa-credit-card"></i> Dados do Pagamento</h2>
                    </div>
                    <div class="card-body">
                        <div class="info-reserva">
                            <div class="info-linha">
                                <span class="info-label"><i class="fas fa-car"></i> Viatura</span>
                                <span class="info-valor"><?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?></span>
                            </div>
                            <div class="info-linha">
                                <span class="info-label"><i class="fas fa-calendar"></i> Período</span>
                                <span class="info-valor"><?= date('d/m/Y', strtotime($reserva['data_inicio'])) ?> até <?= date('d/m/Y', strtotime($reserva['data_fim'])) ?></span>
                            </div>
                            <div class="info-linha total">
                                <span class="info-label"><i class="fas fa-money-bill-wave"></i> Valor total</span>
                                <span class="info-valor">MZN <?= number_format($valor_total, 2) ?></span>
                            </div>
                        </div>
                        
                        <form method="POST" id="formPagamento">
                            <input type="hidden" name="acao" value="pagar">
                            <input type="hidden" name="metodo_pagamento" id="metodo_selecionado" required>
                            
                            <div class="form-group">
                                <label><i class="fas fa-credit-card"></i> Método de Pagamento</label>
                                <div class="metodos-grid">
                                    <div class="metodo-item" data-metodo="cartao">
                                        <i class="fas fa-credit-card"></i>
                                        <span class="metodo-text">Cartão Crédito</span>
                                    </div>
                                    <div class="metodo-item" data-metodo="carteira_movel">
                                        <i class="fas fa-mobile-alt"></i>
                                        <span class="metodo-text">Carteira Móvel</span>
                                    </div>
                                    <div class="metodo-item" data-metodo="dinheiro">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <span class="metodo-text">Dinheiro</span>
                                    </div>
                                    <div class="metodo-item" data-metodo="transferencia">
                                        <i class="fas fa-university"></i>
                                        <span class="metodo-text">Transferência</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="dados_cartao" class="dados-pagamento">
                                <h4><i class="fas fa-credit-card"></i> Dados do Cartão</h4>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Número do Cartão</label>
                                        <input type="text" name="numero_cartao" class="form-control" placeholder="1234 5678 9012 3456">
                                    </div>
                                    <div class="form-group">
                                        <label>Validade</label>
                                        <input type="text" name="validade" class="form-control" placeholder="MM/AA">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>CVV</label>
                                        <input type="password" name="cvv" class="form-control" placeholder="123">
                                    </div>
                                    <div class="form-group">
                                        <label>Nome do Titular</label>
                                        <input type="text" name="nome_titular" class="form-control" placeholder="Como no cartão">
                                    </div>
                                </div>
                            </div>
                            
                            <div id="dados_carteira_movel" class="dados-pagamento">
                                <h4><i class="fas fa-mobile-alt"></i> Dados da Carteira Móvel</h4>
                                <div class="form-group">
                                    <label>Operadora</label>
                                    <select name="operadora" class="form-control">
                                        <option value="mpesa">M-Pesa</option>
                                        <option value="emola">E-MOLA</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Número de Telemóvel</label>
                                    <input type="tel" name="telefone_carteira" class="form-control" placeholder="84XXXXXXX">
                                </div>
                                <div class="form-group">
                                    <label>PIN de Confirmação</label>
                                    <input type="password" name="pin_carteira" class="form-control" placeholder="****" maxlength="4">
                                </div>
                            </div>
                            
                            <div id="dados_dinheiro" class="dados-pagamento">
                                <div class="alert-info">
                                    <i class="fas fa-info-circle"></i> Pagamento em dinheiro - será processado no balcão da loja.
                                </div>
                            </div>
                            
                            <div id="dados_transferencia" class="dados-pagamento">
                                <div class="alert-info">
                                    <i class="fas fa-info-circle"></i> Transferência bancária - utilize os dados abaixo:
                                    <br><strong>IBAN:</strong> PT50 0000 0000 0000 0000 0000 0
                                    <br><strong>SWIFT:</strong> EXMPPT3X
                                    <br><br>Após a transferência, o pagamento será confirmado manualmente.
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-comment"></i> Observações</label>
                                <textarea name="observacoes" class="form-control" rows="2" placeholder="Notas adicionais..."></textarea>
                            </div>
                            
                            <button type="button" class="btn-pagar" onclick="abrirModal()">
                                <i class="fas fa-check-circle"></i> Confirmar Pagamento
                            </button>
                        </form>
                        
                        <a href="reservas.php" class="voltar-link"><i class="fas fa-arrow-left"></i> Voltar para Minhas Reservas</a>
                    </div>
                    
                    <?php elseif($reserva && $total_pagamentos > 0): ?>
                        <div class="erro" style="background: #fff3cd; color: #856404;">
                            <i class="fas fa-exclamation-triangle"></i> Esta reserva já possui um pagamento registado e aguarda confirmação.
                        </div>
                        <a href="reservas.php" class="voltar-link"><i class="fas fa-arrow-left"></i> Voltar para Minhas Reservas</a>
                    <?php else: ?>
                        <div class="erro"><i class="fas fa-exclamation-triangle"></i> Reserva não encontrada ou não está confirmada.</div>
                        <a href="reservas.php" class="voltar-link"><i class="fas fa-arrow-left"></i> Voltar para Minhas Reservas</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- MODAL DE CONFIRMAÇÃO -->
        <div id="modalConfirmacao" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-question-circle"></i> Confirmar Pagamento</h3>
                    <button class="modal-close" onclick="fecharModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja confirmar este pagamento?</p>
                    <div class="modal-resumo">
                        <div class="modal-resumo-linha">
                            <span><i class="fas fa-car"></i> Viatura:</span>
                            <strong><?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?></strong>
                        </div>
                        <div class="modal-resumo-linha">
                            <span><i class="fas fa-calendar"></i> Período:</span>
                            <strong><?= date('d/m/Y', strtotime($reserva['data_inicio'])) ?> até <?= date('d/m/Y', strtotime($reserva['data_fim'])) ?></strong>
                        </div>
                        <div class="modal-resumo-linha">
                            <span><i class="fas fa-credit-card"></i> Método:</span>
                            <strong id="modalMetodo">-</strong>
                        </div>
                        <div class="modal-resumo-total">
                            <div style="display: flex; justify-content: space-between;">
                                <span><i class="fas fa-money-bill-wave"></i> Valor Total:</span>
                                <strong style="color: #FF8C00;">MZN <?= number_format($valor_total, 2) ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="modal-botoes">
                        <button type="button" class="btn-modal-cancelar" onclick="fecharModal()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="button" class="btn-modal-confirmar" onclick="finalizarPagamento()">
                            <i class="fas fa-check"></i> Confirmar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
            let metodoAtual = '';
            
            document.querySelectorAll('.metodo-item').forEach(el => {
                el.addEventListener('click', function() {
                    const metodo = this.dataset.metodo;
                    metodoAtual = metodo;
                    
                    document.querySelectorAll('.metodo-item').forEach(m => m.classList.remove('selected'));
                    this.classList.add('selected');
                    
                    document.querySelectorAll('.dados-pagamento').forEach(div => div.classList.remove('active'));
                    
                    if(metodo === 'cartao') {
                        document.getElementById('dados_cartao').classList.add('active');
                    } else if(metodo === 'carteira_movel') {
                        document.getElementById('dados_carteira_movel').classList.add('active');
                    } else if(metodo === 'dinheiro') {
                        document.getElementById('dados_dinheiro').classList.add('active');
                    } else if(metodo === 'transferencia') {
                        document.getElementById('dados_transferencia').classList.add('active');
                    }
                    
                    document.getElementById('metodo_selecionado').value = metodo;
                });
            });
            
            function abrirModal() {
                const metodo = document.getElementById('metodo_selecionado').value;
                
                if(!metodo) {
                    alert('Por favor, selecione um método de pagamento');
                    return;
                }
                
                let metodoTexto = '';
                switch(metodo) {
                    case 'cartao': metodoTexto = 'Cartão de Crédito'; break;
                    case 'carteira_movel': metodoTexto = 'Carteira Móvel'; break;
                    case 'dinheiro': metodoTexto = 'Dinheiro'; break;
                    case 'transferencia': metodoTexto = 'Transferência Bancária'; break;
                    default: metodoTexto = metodo;
                }
                document.getElementById('modalMetodo').innerHTML = metodoTexto;
                document.getElementById('modalConfirmacao').classList.add('active');
            }
            
            function fecharModal() {
                document.getElementById('modalConfirmacao').classList.remove('active');
            }
            
            function finalizarPagamento() {
                document.getElementById('formPagamento').submit();
            }
            
            window.onclick = function(event) {
                const modal = document.getElementById('modalConfirmacao');
                if(event.target === modal) {
                    fecharModal();
                }
            }
            
            document.addEventListener('keydown', function(event) {
                if(event.key === 'Escape') {
                    fecharModal();
                }
            });
        </script>
        
        <script src="../assets/js/main.js"></script>
    </body>
    </html>
    <?php
    exit();
}

// ============================================
// SE NÃO TEM RESERVA_ID -> LISTA DE PAGAMENTOS
// ============================================

$query = "SELECT p.*, 
          CASE 
              WHEN p.reserva_id IS NOT NULL THEN 
                  (SELECT CONCAT(v.marca, ' ', v.modelo) FROM reservas r JOIN viaturas v ON r.viatura_id = v.id WHERE r.id = p.reserva_id)
              ELSE 
                  (SELECT CONCAT(v.marca, ' ', v.modelo) FROM alugueis a JOIN viaturas v ON a.viatura_id = v.id WHERE a.id = p.aluguer_id)
          END as descricao
          FROM pagamentos p 
          WHERE p.utilizador_id = :utilizador_id 
          ORDER BY p.data_criacao DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':utilizador_id', $utilizador['id']);
$stmt->execute();
$pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mensagem_sucesso = $_SESSION['mensagem_sucesso'] ?? '';
unset($_SESSION['mensagem_sucesso']);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Meus Pagamentos - SIGAV</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fb; }
        
        .container-app { display: flex; min-height: 100vh; }
        .barra-lateral { width: 280px; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: white; position: fixed; left: 0; top: 0; height: 100vh; overflow-y: auto; z-index: 100; transition: all 0.3s ease; }
        .conteudo-principal { flex: 1; margin-left: 280px; padding: 2rem; background: #f5f7fb; min-height: 100vh; width: calc(100% - 280px); }
        .barra-superior { background: white; border-radius: 1rem; padding: 1rem 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        
        .page-header { margin-bottom: 2rem; }
        .page-header h1 { font-size: 2rem; font-weight: 700; color: #1a1a2e; margin-bottom: 0.5rem; }
        .page-header p { color: #666; font-size: 0.95rem; }
        
        .filtros {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .filtro-btn {
            padding: 0.4rem 1rem;
            border: 1px solid #e0e0e0;
            background: white;
            border-radius: 2rem;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .filtro-btn:hover {
            border-color: #FF8C00;
            color: #FF8C00;
        }
        
        .filtro-btn.ativo {
            background: #FF8C00;
            border-color: #FF8C00;
            color: white;
        }
        
        .pagamento-card {
            background: white;
            border-radius: 1rem;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .pagamento-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .pagamento-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 0.8rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .referencia {
            font-weight: 600;
            color: #1a1a2e;
            font-size: 0.9rem;
        }
        
        .valor {
            font-weight: 700;
            color: #FF8C00;
            font-size: 1rem;
        }
        
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.6rem;
            border-radius: 2rem;
            font-size: 0.65rem;
            font-weight: 500;
        }
        
        .badge-confirmado {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-pendente {
            background: #fff3cd;
            color: #856404;
        }
        
        .btn-recibo {
            background: none;
            border: 1px solid #17a2b8;
            color: #17a2b8;
            padding: 0.3rem 0.8rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.7rem;
            transition: all 0.3s ease;
        }
        
        .btn-recibo:hover {
            background: #17a2b8;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #999;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .toast-success {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            background: white;
            border-left: 4px solid #28a745;
            border-radius: 0.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 2000;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @media (max-width: 768px) {
            .barra-lateral { width: 0; transform: translateX(-100%); }
            .conteudo-principal { margin-left: 0; width: 100%; padding: 1rem; }
            .pagamento-header { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <?php if($mensagem_sucesso): ?>
            <div class="toast-success" id="toast">
                <i class="fas fa-check-circle" style="color: #28a745;"></i> <?= htmlspecialchars($mensagem_sucesso) ?>
            </div>
            <script>setTimeout(() => { const t = document.getElementById('toast'); if(t) t.style.display = 'none'; }, 3000);</script>
            <?php endif; ?>
            
            <div class="page-header">
                <h1>Meus Pagamentos</h1>
                <p>Consulte todos os seus pagamentos e recibos</p>
            </div>
            
            <div class="filtros">
                <button class="filtro-btn ativo" data-filtro="todos"><i class="fas fa-list"></i> Todos</button>
                <button class="filtro-btn" data-filtro="confirmado"><i class="fas fa-check-circle"></i> Confirmados</button>
                <button class="filtro-btn" data-filtro="pendente"><i class="fas fa-clock"></i> Pendentes</button>
            </div>
            
            <div id="lista-pagamentos">
                <?php if(count($pagamentos) > 0): ?>
                    <?php foreach($pagamentos as $p): ?>
                    <div class="pagamento-card" data-status="<?= $p['estado'] ?>">
                        <div class="pagamento-header">
                            <div>
                                <div class="referencia"><i class="fas fa-hashtag"></i> <?= $p['referencia_pagamento'] ?></div>
                                <small style="color: #999;"><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y H:i', strtotime($p['data_criacao'])) ?></small>
                            </div>
                            <div class="valor">MZN <?= number_format($p['valor'], 2) ?></div>
                        </div>
                        <div style="margin-bottom: 0.5rem;">
                            <i class="fas fa-car" style="color: #999;"></i> <?= htmlspecialchars($p['descricao'] ?? '-') ?>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                            <div>
                                <i class="fas fa-credit-card" style="color: #999;"></i> <?= ucfirst(str_replace('_', ' ', $p['metodo_pagamento'])) ?>
                            </div>
                            <div>
                                <?php if($p['estado'] == 'confirmado'): ?>
                                    <span class="badge-status badge-confirmado"><i class="fas fa-check-circle"></i> Confirmado</span>
                                    <button class="btn-recibo" onclick="window.open('../pagamentos/recibo.php?id=<?= $p['id'] ?>', '_blank')">
                                        <i class="fas fa-receipt"></i> Recibo
                                    </button>
                                <?php else: ?>
                                    <span class="badge-status badge-pendente"><i class="fas fa-clock"></i> Pendente</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>Nenhum pagamento encontrado</p>
                        <small>Os seus pagamentos aparecerão aqui</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        document.querySelectorAll('.filtro-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const filtro = this.dataset.filtro;
                document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('ativo'));
                this.classList.add('ativo');
                document.querySelectorAll('.pagamento-card').forEach(card => {
                    card.style.display = (filtro === 'todos' || card.dataset.status === filtro) ? 'block' : 'none';
                });
            });
        });
    </script>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>