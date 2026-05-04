<?php
// components/formulario_pagamento.php
function exibirFormularioPagamento($valor_total, $reserva_id = null, $aluguer_id = null) {
    ?>
    <div class="formulario-pagamento">
        <h3> Forma de Pagamento</h3>
        
        <div class="metodos-pagamento">
            <div class="metodo-pagamento" onclick="selecionarMetodo('dinheiro')">
                <input type="radio" name="metodo_pagamento" value="dinheiro" id="dinheiro">
                <label for="dinheiro">
                    <i class="icon"></i> Dinheiro
                </label>
            </div>
            
            <div class="metodo-pagamento" onclick="selecionarMetodo('cartao')">
                <input type="radio" name="metodo_pagamento" value="cartao" id="cartao">
                <label for="cartao">
                    <i class="icon"></i> Cartão de Crédito/Débito
                </label>
            </div>
            
            <div class="metodo-pagamento" onclick="selecionarMetodo('mbway')">
                <input type="radio" name="metodo_pagamento" value="mbway" id="mbway">
                <label for="mbway">
                    <i class="icon"></i> MB WAY
                </label>
            </div>
            
            <div class="metodo-pagamento" onclick="selecionarMetodo('transferencia')">
                <input type="radio" name="metodo_pagamento" value="transferencia" id="transferencia">
                <label for="transferencia">
                    <i class="icon"></i> Transferência Bancária
                </label>
            </div>
            
            <div class="metodo-pagamento" onclick="selecionarMetodo('paypal')">
                <input type="radio" name="metodo_pagamento" value="paypal" id="paypal">
                <label for="paypal">
                    <i class="icon"></i> PayPal
                </label>
            </div>
        </div>
        
        <div id="dados_cartao" class="dados-pagamento" style="display: none;">
            <h4>Dados do Cartão</h4>
            <div class="form-group">
                <label>Número do Cartão</label>
                <input type="text" id="numero_cartao" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Validade</label>
                    <input type="text" id="validade" class="form-control" placeholder="MM/AA">
                </div>
                <div class="form-group">
                    <label>CVV</label>
                    <input type="password" id="cvv" class="form-control" placeholder="123" maxlength="4">
                </div>
            </div>
            <div class="form-group">
                <label>Nome do Titular</label>
                <input type="text" id="nome_titular" class="form-control" placeholder="Como no cartão">
            </div>
        </div>
        
        <div id="dados_mbway" class="dados-pagamento" style="display: none;">
            <h4>MB WAY</h4>
            <div class="form-group">
                <label>Número de Telemóvel</label>
                <input type="tel" id="telefone_mbway" class="form-control" placeholder="912345678">
            </div>
            <div class="alert alert-info">
                <small>Será enviado um código de confirmação para o seu telemóvel</small>
            </div>
        </div>
        
        <div id="dados_transferencia" class="dados-pagamento" style="display: none;">
            <h4>Dados para Transferência Bancária</h4>
            <div class="alert alert-info">
                <p><strong>Banco:</strong> Banco Exemplo</p>
                <p><strong>IBAN:</strong> PT50 0000 0000 0000 0000 0000 0</p>
                <p><strong>SWIFT/BIC:</strong> EXMPPT3X</p>
                <p><strong>Referência:</strong> SIGAV-<span id="ref_transferencia"></span></p>
            </div>
            <div class="form-group">
                <label>Comprovativo de Pagamento (PDF/Imagem)</label>
                <input type="file" id="comprovativo" accept=".pdf,.jpg,.png">
            </div>
        </div>
        
        <div id="resumo_pagamento" class="resumo-pagamento">
            <h4>Resumo do Pagamento</h4>
            <div class="linha-resumo">
                <span>Subtotal:</span>
                <span id="subtotal">MZN<?= number_format($valor_total, 2) ?></span>
            </div>
            <div class="linha-resumo">
                <span>IVA (23%):</span>
                <span id="iva_valor">MZN<?= number_format($valor_total * 0.23, 2) ?></span>
            </div>
            <div class="linha-resumo">
                <span>Taxa de Serviço (5%):</span>
                <span id="taxa_servico">MZN<?= number_format($valor_total * 0.05, 2) ?></span>
            </div>
            <div class="linha-resumo total">
                <strong>TOTAL:</strong>
                <strong id="total">MZN<?= number_format($valor_total * 1.28, 2) ?></strong>
            </div>
        </div>
        
        <input type="hidden" id="reserva_id" value="<?= $reserva_id ?>">
        <input type="hidden" id="aluguer_id" value="<?= $aluguer_id ?>">
        <input type="hidden" id="valor_base" value="<?= $valor_total ?>">
        
        <button type="button" class="btn btn-success btn-lg" onclick="processarPagamento()" style="width: 100%; margin-top: 1rem;">
             Confirmar Pagamento
        </button>
    </div>
    
    <style>
        .formulario-pagamento {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: var(--sombra-med);
        }
        
        .metodos-pagamento {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
        }
        
        .metodo-pagamento {
            border: 2px solid var(--cinza);
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: var(--transicao);
        }
        
        .metodo-pagamento:hover {
            border-color: var(--laranja);
            background: rgba(255, 140, 0, 0.05);
        }
        
        .metodo-pagamento.selecionado {
            border-color: var(--laranja);
            background: rgba(255, 140, 0, 0.1);
        }
        
        .metodo-pagamento input {
            display: none;
        }
        
        .metodo-pagamento label {
            cursor: pointer;
            display: block;
        }
        
        .metodo-pagamento .icon {
            font-size: 2rem;
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .dados-pagamento {
            margin: 1.5rem 0;
            padding: 1rem;
            background: var(--cinza-claro);
            border-radius: 10px;
        }
        
        .resumo-pagamento {
            margin-top: 1.5rem;
            padding: 1rem;
            border-top: 2px solid var(--cinza);
        }
        
        .linha-resumo {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .linha-resumo.total {
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid var(--cinza);
            font-size: 1.2rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .alert {
            padding: 0.75rem;
            border-radius: 5px;
            margin: 0.5rem 0;
        }
        
        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
    </style>
    
    <script>
        let metodoSelecionado = null;
        
        function selecionarMetodo(metodo) {
            metodoSelecionado = metodo;
            
            // Atualizar UI dos métodos
            document.querySelectorAll('.metodo-pagamento').forEach(el => {
                el.classList.remove('selecionado');
            });
            event.currentTarget.classList.add('selecionado');
            
            // Esconder todos os formulários
            document.querySelectorAll('.dados-pagamento').forEach(el => {
                el.style.display = 'none';
            });
            
            // Mostrar formulário específico
            if(metodo === 'cartao') {
                document.getElementById('dados_cartao').style.display = 'block';
            } else if(metodo === 'mbway') {
                document.getElementById('dados_mbway').style.display = 'block';
            } else if(metodo === 'transferencia') {
                document.getElementById('dados_transferencia').style.display = 'block';
                document.getElementById('ref_transferencia').innerText = Math.random().toString(36).substring(2, 10).toUpperCase();
            }
            
            // Marcar radio button correspondente
            document.querySelector(`input[value="${metodo}"]`).checked = true;
        }
        
        function processarPagamento() {
            if(!metodoSelecionado) {
                Utilitarios.mostrarNotificacao('Selecione um método de pagamento', 'aviso');
                return;
            }
            
            const dadosPagamento = {
                metodo: metodoSelecionado,
                reserva_id: document.getElementById('reserva_id')?.value,
                aluguer_id: document.getElementById('aluguer_id')?.value,
                valor_base: document.getElementById('valor_base').value,
                dados_especificos: {}
            };
            
            // Recolher dados específicos do método
            if(metodoSelecionado === 'cartao') {
                dadosPagamento.dados_especificos = {
                    numero_cartao: document.getElementById('numero_cartao').value,
                    validade: document.getElementById('validade').value,
                    cvv: document.getElementById('cvv').value,
                    nome_titular: document.getElementById('nome_titular').value
                };
            } else if(metodoSelecionado === 'mbway') {
                dadosPagamento.dados_especificos = {
                    telefone: document.getElementById('telefone_mbway').value
                };
            }
            
            modal.confirmar('Confirmar pagamento no valor total?', async () => {
                const resultado = await API.post('../pagamentos/processar_pagamento.php', dadosPagamento);
                
                if(resultado && resultado.sucesso) {
                    if(metodoSelecionado === 'mbway') {
                        // Mostrar modal para código MB WAY
                        mostrarModalCodigoMBWAY(resultado.referencia);
                    } else {
                        Utilitarios.mostrarNotificacao('Pagamento processado com sucesso!', 'sucesso');
                        setTimeout(() => {
                            window.location.href = resultado.redirect || '../cliente/pagamentos.php';
                        }, 2000);
                    }
                } else {
                    Utilitarios.mostrarNotificacao(resultado?.mensagem || 'Erro ao processar pagamento', 'erro');
                }
            });
        }
        
        function mostrarModalCodigoMBWAY(referencia) {
            modal.abrir(`
                <div style="text-align: center;">
                    <h3> MB WAY - Código de Confirmação</h3>
                    <p>Utilize o código abaixo na sua app MB WAY:</p>
                    <div style="font-size: 2rem; font-weight: bold; color: var(--laranja); margin: 1rem 0;">
                        ${referencia}
                    </div>
                    <p>Após confirmar na app, o pagamento será processado automaticamente.</p>
                    <button class="btn btn-success" onclick="verificarPagamentoMBWAY('${referencia}')">
                        Já confirmei na app
                    </button>
                </div>
            `, 'Pagamento MB WAY');
        }
        
        async function verificarPagamentoMBWAY(referencia) {
            const resultado = await API.post('../pagamentos/confirmar_pagamento.php', {
                referencia: referencia,
                metodo: 'mbway'
            });
            
            if(resultado && resultado.sucesso) {
                modal.fechar();
                Utilitarios.mostrarNotificacao('Pagamento confirmado!', 'sucesso');
                setTimeout(() => {
                    window.location.href = resultado.redirect;
                }, 1500);
            }
        }
    </script>
    <?php
}
?>