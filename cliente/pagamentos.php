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
// SE TEM RESERVA_ID -> MOSTRA FORMULÁRIO DE PAGAMENTO (COM BARRA LATERAL)
// ============================================
if($reserva_id > 0) {
    // Buscar dados da reserva e VERIFICAR se já tem pagamento
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
    
    // Processar pagamento
    if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao'])) {
        // VERIFICAR novamente se já existe pagamento (proteção contra duplicados)
        $checkQuery = "SELECT COUNT(*) as total FROM pagamentos WHERE reserva_id = :reserva_id AND estado IN ('confirmado', 'pendente')";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':reserva_id', $reserva_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if($checkResult['total'] > 0) {
            $erro = "Esta reserva já possui um pagamento registado. Não é possível efetuar outro pagamento.";
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
                $_SESSION['mensagem_sucesso'] = "Pagamento registado com sucesso! Aguarde confirmação do funcionário.";
                header('Location: reservas.php');
                exit();
            } else {
                $erro = "Erro ao processar pagamento. Tente novamente.";
            }
        }
    }
    
    // MOSTRAR FORMULÁRIO DE PAGAMENTO COM A BARRA LATERAL PADRÃO
    ?>
    <!DOCTYPE html>
    <html lang="pt">
    <head>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pagar Reserva - SIGAV</title>
        <link rel="stylesheet" href="../assets/css/estilo.css">
        <style>
            .pagamento-content {
                max-width: 600px;
                margin: 0 auto;
                background: white;
                border-radius: 16px;
                padding: 1.5rem;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            .pagamento-header {
                text-align: center;
                margin-bottom: 1.5rem;
                padding-bottom: 1rem;
                border-bottom: 2px solid #FF8C00;
            }
            .pagamento-header h1 {
                color: #1E3A5F;
                font-size: 1.5rem;
                margin-bottom: 0.3rem;
            }
            .info-reserva {
                background: #f8f9fa;
                border-radius: 12px;
                padding: 1rem;
                margin-bottom: 1.5rem;
            }
            .info-linha {
                display: flex;
                justify-content: space-between;
                margin-bottom: 8px;
                font-size: 0.85rem;
            }
            .total { border-top: 1px solid #ddd; margin-top: 8px; padding-top: 8px; }
            .total .info-valor { color: #28a745; font-weight: bold; }
            .form-group { margin-bottom: 1.2rem; }
            .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #1E3A5F; font-size: 0.85rem; }
            .metodos {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
                margin-bottom: 1.5rem;
            }
            .metodo {
                border: 2px solid #e9ecef;
                border-radius: 12px;
                padding: 1rem;
                text-align: center;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            .metodo:hover { border-color: #1E3A5F; }
            .metodo.selected { border-color: #1E3A5F; background: rgba(30,58,95,0.05); }
            .metodo i { font-size: 1.8rem; display: block; margin-bottom: 0.5rem; color: #1E3A5F; }
            .metodo-text { font-size: 0.8rem; font-weight: 500; }
            .dados-pagamento {
                background: #f8f9fa;
                border-radius: 12px;
                padding: 1rem;
                margin-bottom: 1.5rem;
                display: none;
            }
            .dados-pagamento.active { display: block; }
            .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
            input, select {
                width: 100%;
                padding: 0.7rem;
                border: 1px solid #ddd;
                border-radius: 8px;
                font-size: 0.85rem;
            }
            textarea {
                width: 100%;
                padding: 0.7rem;
                border: 1px solid #ddd;
                border-radius: 8px;
                font-size: 0.85rem;
                resize: vertical;
            }
            .btn-pagar {
                width: 100%;
                padding: 0.8rem;
                background: #1E3A5F;
                color: white;
                border: none;
                border-radius: 10px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            .btn-pagar:hover { background: #FF8C00; transform: translateY(-2px); }
            .mensagem { background: #d4edda; color: #155724; padding: 0.8rem; border-radius: 10px; margin-bottom: 1rem; text-align: center; }
            .erro { background: #f8d7da; color: #721c24; padding: 0.8rem; border-radius: 10px; margin-bottom: 1rem; text-align: center; }
            .voltar { display: block; text-align: center; margin-top: 1rem; color: #FF8C00; text-decoration: none; }
            .alert-info {
                background: #d1ecf1;
                border: 1px solid #bee5eb;
                color: #0c5460;
                padding: 0.8rem;
                border-radius: 8px;
            }
            .modal {
                display: none;
                position: fixed;
                z-index: 9999;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.5);
                justify-content: center;
                align-items: center;
            }
            .modal-content {
                background: white;
                border-radius: 20px;
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
                background: #1E3A5F;
                color: white;
                padding: 1rem 1.5rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .modal-header h3 {
                margin: 0;
                font-size: 1.2rem;
            }
            .modal-close {
                background: none;
                border: none;
                color: white;
                font-size: 1.5rem;
                cursor: pointer;
            }
            .modal-close:hover { opacity: 0.8; }
            .modal-body {
                padding: 1.5rem;
            }
            .modal-resumo {
                background: #f8f9fa;
                border-radius: 12px;
                padding: 1rem;
                margin-bottom: 1rem;
            }
            .modal-resumo-linha {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #e9ecef;
            }
            .modal-resumo-linha:last-child {
                border-bottom: none;
            }
            .modal-resumo-total {
                background: #e8f5e9;
                border-radius: 10px;
                padding: 10px;
                margin-top: 10px;
                font-weight: bold;
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
                border-radius: 10px;
                cursor: pointer;
                font-size: 0.9rem;
            }
            .btn-modal-confirmar {
                flex: 1;
                padding: 0.7rem;
                background: #28a745;
                color: white;
                border: none;
                border-radius: 10px;
                cursor: pointer;
                font-size: 0.9rem;
            }
            .btn-modal-confirmar:hover { background: #218838; }
            .btn-modal-cancelar:hover { background: #5a6268; }
            
            @media (max-width: 768px) {
                .metodos { grid-template-columns: 1fr; }
                .form-row { grid-template-columns: 1fr; }
            }
        </style>
    </head>
    <body>
        <div class="container-app">
            <?php include '../components/barra_lateral.php'; ?>
            
            <div class="conteudo-principal">
                <?php include '../components/cabecalho.php'; ?>
                
                <div class="pagamento-content">
                    <div class="pagamento-header">
                        <h1><i class="fas fa-credit-card"></i> Pagar Reserva</h1>
                        <p>Conclua o seu pagamento de forma segura</p>
                    </div>
                    
                    <?php if($mensagem): ?>
                        <div class="mensagem"><i class="fas fa-check-circle"></i> <?= $mensagem ?></div>
                        <a href="reservas.php" class="voltar"><i class="fas fa-arrow-left"></i> Voltar para Minhas Reservas</a>
                    <?php elseif($erro): ?>
                        <div class="erro"><i class="fas fa-exclamation-triangle"></i> <?= $erro ?></div>
                        <a href="reservas.php" class="voltar"><i class="fas fa-arrow-left"></i> Voltar para Minhas Reservas</a>
                    <?php elseif($reserva): ?>
                    
                    <!-- VERIFICAÇÃO: Se já tem pagamento, mostra erro e não exibe formulário -->
                    <?php if($total_pagamentos > 0): ?>
                        <div class="erro" style="background: #fff3cd; color: #856404; border-color: #ffeeba;">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>Pagamento já efetuado!</strong> Esta reserva já possui um pagamento registado e aguarda confirmação.
                        </div>
                        <div style="text-align: center; margin-top: 1rem;">
                            <a href="reservas.php" class="voltar"><i class="fas fa-arrow-left"></i> Voltar para Minhas Reservas</a>
                        </div>
                    <?php else: ?>
                    
                    <div class="info-reserva">
                        <div class="info-linha"><span class="info-label"><i class="fas fa-car"></i> Viatura</span><span class="info-valor"><?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?></span></div>
                        <div class="info-linha"><span class="info-label"><i class="fas fa-calendar"></i> Período</span><span class="info-valor"><?= date('d/m/Y', strtotime($reserva['data_inicio'])) ?> até <?= date('d/m/Y', strtotime($reserva['data_fim'])) ?></span></div>
                        <div class="info-linha total"><span class="info-label"><i class="fas fa-money-bill-wave"></i> Valor total</span><span class="info-valor">MZN <?= number_format($valor_total, 2) ?></span></div>
                    </div>
                    
                    <form method="POST" id="formPagamento">
                        <input type="hidden" name="acao" value="pagar">
                        <input type="hidden" name="metodo_pagamento" id="metodo_selecionado" required>
                        
                        <div class="form-group">
                            <label><i class="fas fa-credit-card"></i> Método de Pagamento</label>
                            <div class="metodos">
                                <div class="metodo" data-metodo="cartao">
                                    <i class="fas fa-credit-card"></i>
                                    <span class="metodo-text">Cartão Crédito</span>
                                </div>
                                <div class="metodo" data-metodo="carteira_movel">
                                    <i class="fas fa-mobile-alt"></i>
                                    <span class="metodo-text">Carteira Móvel</span>
                                </div>
                                <div class="metodo" data-metodo="dinheiro">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span class="metodo-text">Dinheiro</span>
                                </div>
                                <div class="metodo" data-metodo="transferencia">
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
                                    <input type="text" name="numero_cartao" placeholder="1234 5678 9012 3456">
                                </div>
                                <div class="form-group">
                                    <label>Validade</label>
                                    <input type="text" name="validade" placeholder="MM/AA">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>CVV</label>
                                    <input type="password" name="cvv" placeholder="123">
                                </div>
                                <div class="form-group">
                                    <label>Nome do Titular</label>
                                    <input type="text" name="nome_titular" placeholder="Como no cartão">
                                </div>
                            </div>
                        </div>
                        
                        <div id="dados_carteira_movel" class="dados-pagamento">
                            <h4><i class="fas fa-mobile-alt"></i> Dados da Carteira Móvel</h4>
                            <div class="form-group">
                                <label>Operadora</label>
                                <select name="operadora">
                                    <option value="mpesa">M-Pesa</option>
                                    <option value="emola">E-MOLA</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Número de Telemóvel</label>
                                <input type="tel" name="telefone_carteira" placeholder="84XXXXXXX">
                            </div>
                            <div class="form-group">
                                <label>PIN de Confirmação</label>
                                <input type="password" name="pin_carteira" placeholder="****" maxlength="4">
                            </div>
                        </div>
                        
                        <div id="dados_dinheiro" class="dados-pagamento">
                            <div class="alert-info">
                                <i class="fas fa-info-circle"></i> Pagamento em dinheiro - será processado no balcão.
                            </div>
                        </div>
                        
                        <div id="dados_transferencia" class="dados-pagamento">
                            <div class="alert-info">
                                <i class="fas fa-info-circle"></i> Transferência bancária - utilize os dados abaixo:
                                <br><strong>IBAN:</strong> PT50 0000 0000 0000 0000 0000 0
                                <br><strong>SWIFT:</strong> EXMPPT3X
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-comment"></i> Observações</label>
                            <textarea name="observacoes" rows="2" placeholder="Notas adicionais..."></textarea>
                        </div>
                        
                        <button type="button" class="btn-pagar" onclick="abrirModal()">
                            <i class="fas fa-check-circle"></i> Confirmar Pagamento
                        </button>
                    </form>
                    
                    <a href="reservas.php" class="voltar"><i class="fas fa-arrow-left"></i> Voltar para Minhas Reservas</a>
                    
                    <?php endif; ?>
                    
                    <?php else: ?>
                    <div style="text-align: center; padding: 2rem;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #ffc107;"></i>
                        <p>Reserva não encontrada ou já foi paga.</p>
                        <a href="reservas.php" class="voltar"><i class="fas fa-arrow-left"></i> Voltar</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- MODAL DE CONFIRMAÇÃO -->
        <div id="modalConfirmacao" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-shield-alt"></i> Confirmar Pagamento</h3>
                    <button class="modal-close" onclick="fecharModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <p style="margin-bottom: 1rem; color: #666;">Revise os dados antes de confirmar:</p>
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
                                <strong style="color: #28a745; font-size: 1.1rem;">MZN <?= number_format($valor_total, 2) ?></strong>
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
            // Seleção do método de pagamento
            let metodoAtual = '';
            
            document.querySelectorAll('.metodo').forEach(el => {
                el.addEventListener('click', function() {
                    const metodo = this.dataset.metodo;
                    metodoAtual = metodo;
                    
                    document.querySelectorAll('.metodo').forEach(m => m.classList.remove('selected'));
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
            
            // Funções do Modal
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
                
                document.getElementById('modalConfirmacao').style.display = 'flex';
            }
            
            function fecharModal() {
                document.getElementById('modalConfirmacao').style.display = 'none';
            }
            
            function finalizarPagamento() {
                document.getElementById('modalConfirmacao').style.display = 'none';
                document.getElementById('formPagamento').submit();
            }
            
            window.onclick = function(event) {
                const modal = document.getElementById('modalConfirmacao');
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            }
        </script>
        
        <script src="../assets/js/main.js"></script>
        <script src="../assets/js/cliente.js"></script>
    </body>
    </html>
    <?php
    exit();
}

// ============================================
// SE NÃO TEM RESERVA_ID -> MOSTRA HISTÓRICO DE PAGAMENTOS
// ============================================

// Buscar pagamentos do cliente
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

// Estatísticas
$query = "SELECT 
          COUNT(*) as total,
          SUM(CASE WHEN estado = 'confirmado' THEN valor ELSE 0 END) as total_pago,
          SUM(CASE WHEN estado = 'pendente' THEN valor ELSE 0 END) as total_pendente
          FROM pagamentos WHERE utilizador_id = :utilizador_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':utilizador_id', $utilizador['id']);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Pagamentos - SIGAV</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <style>
        .stats-pagamentos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.2rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .stat-value { font-size: 1.8rem; font-weight: bold; color: #1E3A5F; }
        .stat-label { font-size: 0.75rem; color: #666; margin-top: 0.3rem; }
        .filtros { display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .filtro-btn {
            padding: 0.4rem 1rem;
            border: none;
            background: #e9ecef;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        .filtro-btn.ativo, .filtro-btn:hover { background: #1E3A5F; color: white; }
        .pagamento-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .pagamento-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        .status-confirmado {
            background: #28a745;
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .status-pendente {
            background: #ffc107;
            color: #333;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .btn-recibo {
            background: #17a2b8;
            color: white;
            padding: 0.3rem 0.8rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.7rem;
        }
        @media (max-width: 768px) {
            .stats-pagamentos { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <div class="stats-pagamentos">
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['total'] ?? 0 ?></div>
                    <div class="stat-label"><i class="fas fa-receipt"></i> Total de Pagamentos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">MZN <?= number_format($stats['total_pago'] ?? 0, 2) ?></div>
                    <div class="stat-label"><i class="fas fa-check-circle"></i> Total Confirmado</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">MZN <?= number_format($stats['total_pendente'] ?? 0, 2) ?></div>
                    <div class="stat-label"><i class="fas fa-clock"></i> Pendente</div>
                </div>
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
                                <strong><i class="fas fa-hashtag"></i> <?= $p['referencia_pagamento'] ?></strong>
                                <br><small><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y H:i', strtotime($p['data_criacao'])) ?></small>
                            </div>
                            <div style="font-weight: bold; color: #28a745;">
                                <i class="fas fa-money-bill-wave"></i> MZN <?= number_format($p['valor'], 2) ?>
                            </div>
                        </div>
                        <div><strong><i class="fas fa-car"></i> Descrição:</strong> <?= htmlspecialchars($p['descricao'] ?? '-') ?></div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem;">
                            <div><i class="fas fa-credit-card"></i> <strong>Método:</strong> <?= ucfirst(str_replace('_', ' ', $p['metodo_pagamento'])) ?></div>
                            <div>
                                <?php if($p['estado'] == 'confirmado'): ?>
                                    <span class="status-confirmado"><i class="fas fa-check-circle"></i> Confirmado</span>
                                <?php else: ?>
                                    <span class="status-pendente"><i class="fas fa-clock"></i> Pendente</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if($p['estado'] == 'confirmado'): ?>
                        <div style="margin-top: 0.75rem; text-align: right;">
                            <button class="btn-recibo" onclick="window.open('../pagamentos/recibo.php?id=<?= $p['id'] ?>', '_blank')">
                                <i class="fas fa-file-pdf"></i> Baixar Recibo
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="pagamento-card" style="text-align: center; padding: 2rem;">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: #ccc;"></i>
                    <p>Nenhum pagamento encontrado.</p>
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
    <script src="../assets/js/cliente.js"></script>
</body>
</html>