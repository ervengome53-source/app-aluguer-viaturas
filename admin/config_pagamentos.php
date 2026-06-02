<?php
require_once '../config/auth.php';
Auth::cargo(['admin']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$mensagem = '';
$erro = '';

// Buscar configurações de pagamento
$query = "SELECT * FROM config_pagamentos WHERE id = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$config = $stmt->fetch(PDO::FETCH_ASSOC);

// Criar configuração padrão se não existir
if(!$config) {
    $query = "INSERT INTO config_pagamentos (id, metodos_ativos, mbway_ativado, cartao_credito_ativado, 
              transferencia_ativada, paypal_ativado, mbway_valor_maximo, cartao_taxa, paypal_email) 
              VALUES (1, 'dinheiro,cartao_credito,mbway,transferencia', 1, 1, 1, 0, 500, 2.5, 'rentcar@paypal.com')";
    $db->exec($query);
    $query = "SELECT * FROM config_pagamentos WHERE id = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Atualizar configurações
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $metodos_ativos = isset($_POST['metodos']) ? implode(',', $_POST['metodos']) : '';
    $mbway_ativado = isset($_POST['mbway_ativado']) ? 1 : 0;
    $cartao_credito_ativado = isset($_POST['cartao_credito_ativado']) ? 1 : 0;
    $transferencia_ativada = isset($_POST['transferencia_ativada']) ? 1 : 0;
    $paypal_ativado = isset($_POST['paypal_ativado']) ? 1 : 0;
    $mbway_valor_maximo = $_POST['mbway_valor_maximo'];
    $cartao_taxa = $_POST['cartao_taxa'];
    $paypal_email = $_POST['paypal_email'];
    $banco_iban = $_POST['banco_iban'];
    $banco_swift = $_POST['banco_swift'];
    $banco_nome = $_POST['banco_nome'];
    
    $query = "UPDATE config_pagamentos SET 
              metodos_ativos = :metodos_ativos,
              mbway_ativado = :mbway_ativado,
              cartao_credito_ativado = :cartao_credito_ativado,
              transferencia_ativada = :transferencia_ativada,
              paypal_ativado = :paypal_ativado,
              mbway_valor_maximo = :mbway_valor_maximo,
              cartao_taxa = :cartao_taxa,
              paypal_email = :paypal_email,
              banco_iban = :banco_iban,
              banco_swift = :banco_swift,
              banco_nome = :banco_nome,
              updated_at = NOW()
              WHERE id = 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':metodos_ativos', $metodos_ativos);
    $stmt->bindParam(':mbway_ativado', $mbway_ativado);
    $stmt->bindParam(':cartao_credito_ativado', $cartao_credito_ativado);
    $stmt->bindParam(':transferencia_ativada', $transferencia_ativada);
    $stmt->bindParam(':paypal_ativado', $paypal_ativado);
    $stmt->bindParam(':mbway_valor_maximo', $mbway_valor_maximo);
    $stmt->bindParam(':cartao_taxa', $cartao_taxa);
    $stmt->bindParam(':paypal_email', $paypal_email);
    $stmt->bindParam(':banco_iban', $banco_iban);
    $stmt->bindParam(':banco_swift', $banco_swift);
    $stmt->bindParam(':banco_nome', $banco_nome);
    
    if($stmt->execute()) {
        $mensagem = 'Configurações de pagamento atualizadas com sucesso!';
        // Recarregar configurações
        $query = "SELECT * FROM config_pagamentos WHERE id = 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $erro = 'Erro ao atualizar configurações';
    }
}

$metodosAtivos = explode(',', $config['metodos_ativos'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt">
<head>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações de Pagamento - Admin</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <?php if($mensagem): ?>
                <div class="notificacao sucesso"><i class="fas fa-check-circle"></i> <?= $mensagem ?></div>
            <?php endif; ?>
            
            <?php if($erro): ?>
                <div class="notificacao erro"><i class="fas fa-exclamation-triangle"></i> <?= $erro ?></div>
            <?php endif; ?>
            
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo"><i class="fas fa-credit-card"></i> Configurações de Pagamento</h3>
                </div>
                
                <form method="POST" class="form-config-pagamentos">
                    <div class="config-section">
                        <h4><i class="fas fa-bolt"></i> Métodos de Pagamento Ativos</h4>
                        <div class="metodos-grid">
                            <label class="metodo-checkbox">
                                <input type="checkbox" name="metodos[]" value="dinheiro" 
                                       <?= in_array('dinheiro', $metodosAtivos) ? 'checked' : '' ?>>
                                <span class="metodo-nome"><i class="fas fa-money-bill-wave"></i> Dinheiro</span>
                            </label>
                            <label class="metodo-checkbox">
                                <input type="checkbox" name="metodos[]" value="cartao_credito" 
                                       id="chk_cartao" <?= in_array('cartao_credito', $metodosAtivos) ? 'checked' : '' ?>>
                                <span class="metodo-nome"><i class="fas fa-credit-card"></i> Cartão Crédito/Débito</span>
                            </label>
                            <label class="metodo-checkbox">
                                <input type="checkbox" name="metodos[]" value="mbway" 
                                       id="chk_mbway" <?= in_array('mbway', $metodosAtivos) ? 'checked' : '' ?>>
                                <span class="metodo-nome"><i class="fas fa-mobile-alt"></i> MB WAY</span>
                            </label>
                            <label class="metodo-checkbox">
                                <input type="checkbox" name="metodos[]" value="transferencia" 
                                       id="chk_transferencia" <?= in_array('transferencia', $metodosAtivos) ? 'checked' : '' ?>>
                                <span class="metodo-nome"><i class="fas fa-university"></i> Transferência Bancária</span>
                            </label>
                            <label class="metodo-checkbox">
                                <input type="checkbox" name="metodos[]" value="paypal" 
                                       id="chk_paypal" <?= in_array('paypal', $metodosAtivos) ? 'checked' : '' ?>>
                                <span class="metodo-nome"><i class="fab fa-paypal"></i> PayPal</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="config-section" id="section_mbway">
                        <h4><i class="fas fa-mobile-alt"></i> Configurações MB WAY</h4>
                        <div class="form-row">
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario">
                                    <input type="checkbox" name="mbway_ativado" value="1" 
                                           <?= ($config['mbway_ativado'] ?? 0) ? 'checked' : '' ?>>
                                    <i class="fas fa-toggle-on"></i> Ativar MB WAY
                                </label>
                            </div>
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario"><i class="fas fa-euro-sign"></i> Valor Máximo por Transação (€)</label>
                                <input type="number" step="1" name="mbway_valor_maximo" class="controlo-formulario" 
                                       value="<?= $config['mbway_valor_maximo'] ?? 500 ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="config-section" id="section_cartao">
                        <h4><i class="fas fa-credit-card"></i> Configurações Cartão</h4>
                        <div class="form-row">
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario">
                                    <input type="checkbox" name="cartao_credito_ativado" value="1" 
                                           <?= ($config['cartao_credito_ativado'] ?? 0) ? 'checked' : '' ?>>
                                    <i class="fas fa-toggle-on"></i> Ativar Pagamento com Cartão
                                </label>
                            </div>
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario"><i class="fas fa-percent"></i> Taxa Cartão (%)</label>
                                <input type="number" step="0.01" name="cartao_taxa" class="controlo-formulario" 
                                       value="<?= $config['cartao_taxa'] ?? 2.5 ?>">
                                <small><i class="fas fa-info-circle"></i> Taxa cobrada pelo gateway de pagamento</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="config-section" id="section_transferencia">
                        <h4><i class="fas fa-university"></i> Dados Bancários</h4>
                        <div class="form-row">
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario">
                                    <input type="checkbox" name="transferencia_ativada" value="1" 
                                           <?= ($config['transferencia_ativada'] ?? 0) ? 'checked' : '' ?>>
                                    <i class="fas fa-toggle-on"></i> Ativar Transferência Bancária
                                </label>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario"><i class="fas fa-building"></i> Nome do Banco</label>
                                <input type="text" name="banco_nome" class="controlo-formulario" 
                                       value="<?= htmlspecialchars($config['banco_nome'] ?? '') ?>">
                            </div>
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario"><i class="fas fa-barcode"></i> IBAN</label>
                                <input type="text" name="banco_iban" class="controlo-formulario" 
                                       value="<?= htmlspecialchars($config['banco_iban'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario"><i class="fas fa-code"></i> SWIFT/BIC</label>
                                <input type="text" name="banco_swift" class="controlo-formulario" 
                                       value="<?= htmlspecialchars($config['banco_swift'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="config-section" id="section_paypal">
                        <h4><i class="fab fa-paypal"></i> Configurações PayPal</h4>
                        <div class="form-row">
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario">
                                    <input type="checkbox" name="paypal_ativado" value="1" 
                                           <?= ($config['paypal_ativado'] ?? 0) ? 'checked' : '' ?>>
                                    <i class="fas fa-toggle-on"></i> Ativar PayPal
                                </label>
                            </div>
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario"><i class="fas fa-envelope"></i> Email PayPal</label>
                                <input type="email" name="paypal_email" class="controlo-formulario" 
                                       value="<?= htmlspecialchars($config['paypal_email'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="config-section">
                        <h4><i class="fas fa-cloud-upload-alt"></i> Gateway de Pagamento (Stripe)</h4>
                        <div class="form-row">
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario"><i class="fas fa-key"></i> Stripe Public Key</label>
                                <input type="text" name="stripe_public_key" class="controlo-formulario" 
                                       value="<?= htmlspecialchars($config['stripe_public_key'] ?? '') ?>">
                            </div>
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario"><i class="fas fa-lock"></i> Stripe Secret Key</label>
                                <input type="password" name="stripe_secret_key" class="controlo-formulario" 
                                       value="<?= htmlspecialchars($config['stripe_secret_key'] ?? '') ?>">
                            </div>
                        </div>
                        <small><i class="fas fa-info-circle"></i> Configure as chaves da API Stripe para processar pagamentos com cartão</small>
                    </div>
                    
                    <div class="form-actions" style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primario"><i class="fas fa-save"></i> Guardar Configurações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <style>
        .metodos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .metodo-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            border: 2px solid var(--cinza);
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transicao);
        }
        
        .metodo-checkbox:hover {
            border-color: var(--laranja);
            background: rgba(255, 140, 0, 0.05);
        }
        
        .metodo-checkbox input {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .metodo-nome {
            font-size: 1rem;
        }
        
        .config-section {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--cinza);
        }
        
        .config-section:last-child {
            border-bottom: none;
        }
        
        .config-section h4 {
            color: var(--azul-escuro);
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        small {
            display: block;
            font-size: 0.75rem;
            color: var(--cinza-escuro);
            margin-top: 0.25rem;
        }
    </style>
    
    <script>
        // Mostrar/esconder secções baseado nos checkboxes
        document.getElementById('chk_mbway')?.addEventListener('change', function() {
            const section = document.getElementById('section_mbway');
            if(section) section.style.display = this.checked ? 'block' : 'none';
        });
        
        document.getElementById('chk_cartao')?.addEventListener('change', function() {
            const section = document.getElementById('section_cartao');
            if(section) section.style.display = this.checked ? 'block' : 'none';
        });
        
        document.getElementById('chk_transferencia')?.addEventListener('change', function() {
            const section = document.getElementById('section_transferencia');
            if(section) section.style.display = this.checked ? 'block' : 'none';
        });
        
        document.getElementById('chk_paypal')?.addEventListener('change', function() {
            const section = document.getElementById('section_paypal');
            if(section) section.style.display = this.checked ? 'block' : 'none';
        });
        
        // Inicializar visibilidade
        document.getElementById('section_mbway').style.display = document.getElementById('chk_mbway')?.checked ? 'block' : 'none';
        document.getElementById('section_cartao').style.display = document.getElementById('chk_cartao')?.checked ? 'block' : 'none';
        document.getElementById('section_transferencia').style.display = document.getElementById('chk_transferencia')?.checked ? 'block' : 'none';
        document.getElementById('section_paypal').style.display = document.getElementById('chk_paypal')?.checked ? 'block' : 'none';
    </script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>