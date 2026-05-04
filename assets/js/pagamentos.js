// ============================================
// PAGAMENTOS - JAVASCRIPT ESPECÍFICO
// Sistema de Gestão de Pagamentos - MB WAY, Cartões, Transferências
// ============================================

// Classe Principal de Pagamentos
class GestaoPagamentos {
    static metodoSelecionado = null;
    static pagamentoId = null;
    
    // Inicializar formulário de pagamento
    static initFormularioPagamento(valorTotal, reservaId = null, aluguerId = null) {
        this.valorTotal = valorTotal;
        this.reservaId = reservaId;
        this.aluguerId = aluguerId;
        
        this.calcularValoresComTaxas(valorTotal);
        this.configurarEventos();
    }
    
    // Calcular valores com taxas (IVA 23% + Taxa Serviço 5%)
    static calcularValoresComTaxas(valorBase) {
        const iva = valorBase * 0.23;
        const taxaServico = valorBase * 0.05;
        const valorTotal = valorBase + iva + taxaServico;
        
        this.valorBase = valorBase;
        this.iva = iva;
        this.taxaServico = taxaServico;
        this.valorTotal = valorTotal;
        
        // Atualizar UI se existir
        if (document.getElementById('subtotal')) {
            document.getElementById('subtotal').innerHTML = `MZN ${valorBase.toFixed(2)}`;
            document.getElementById('iva_valor').innerHTML = `MZN ${iva.toFixed(2)} (23%)`;
            document.getElementById('taxa_servico').innerHTML = `MZN ${taxaServico.toFixed(2)} (5%)`;
            document.getElementById('total_valor').innerHTML = `MZN ${valorTotal.toFixed(2)}`;
        }
        
        return { valorBase, iva, taxaServico, valorTotal };
    }
    
    // Configurar eventos do formulário
    static configurarEventos() {
        // Eventos de seleção de método de pagamento
        const metodos = document.querySelectorAll('.metodo-pagamento');
        metodos.forEach(metodo => {
            metodo.addEventListener('click', (e) => {
                e.stopPropagation();
                const valor = metodo.dataset.metodo;
                this.selecionarMetodo(valor, metodo);
            });
        });
        
        // Máscaras para inputs de cartão
        const numeroCartao = document.getElementById('numero_cartao');
        if (numeroCartao) {
            numeroCartao.addEventListener('input', (e) => this.mascaraNumeroCartao(e));
        }
        
        const validade = document.getElementById('validade');
        if (validade) {
            validade.addEventListener('input', (e) => this.mascaraValidade(e));
        }
        
        const cvv = document.getElementById('cvv');
        if (cvv) {
            cvv.addEventListener('input', (e) => this.mascaraCVV(e));
        }
        
        // Telefone MB WAY
        const telefoneMbway = document.getElementById('telefone_mbway');
        if (telefoneMbway) {
            telefoneMbway.addEventListener('input', (e) => this.mascaraTelefone(e));
        }
        
        // Upload de comprovativo
        const comprovativo = document.getElementById('comprovativo');
        if (comprovativo) {
            comprovativo.addEventListener('change', (e) => this.validarComprovativo(e));
        }
    }
    
    // Selecionar método de pagamento
    static selecionarMetodo(metodo, elemento) {
        this.metodoSelecionado = metodo;
        
        // Remover seleção anterior
        document.querySelectorAll('.metodo-pagamento').forEach(el => {
            el.classList.remove('selecionado');
        });
        elemento.classList.add('selecionado');
        
        // Esconder todos os formulários
        document.querySelectorAll('.dados-pagamento').forEach(el => {
            el.style.display = 'none';
        });
        
        // Mostrar formulário específico
        switch(metodo) {
            case 'cartao':
                document.getElementById('dados_cartao').style.display = 'block';
                break;
            case 'mbway':
                document.getElementById('dados_mbway').style.display = 'block';
                break;
            case 'transferencia':
                document.getElementById('dados_transferencia').style.display = 'block';
                this.gerarReferenciaTransferencia();
                break;
            case 'dinheiro':
                // Não precisa de dados adicionais
                break;
            case 'paypal':
                document.getElementById('dados_paypal').style.display = 'block';
                break;
        }
        
        // Marcar radio button
        const radio = document.querySelector(`input[name="metodo_pagamento"][value="${metodo}"]`);
        if (radio) radio.checked = true;
    }
    
    // Processar pagamento principal
    static async processarPagamento() {
        if (!this.metodoSelecionado) {
            Utilitarios.mostrarNotificacao('Selecione um método de pagamento', 'aviso');
            return false;
        }
        
        // Validar dados conforme método
        if (!this.validarDadosMetodo()) {
            return false;
        }
        
        // Mostrar loading
        this.mostrarLoading(true);
        
        // Preparar dados para envio
        const dadosPagamento = {
            metodo: this.metodoSelecionado,
            reserva_id: this.reservaId,
            aluguer_id: this.aluguerId,
            valor_base: this.valorBase,
            valor_total: this.valorTotal,
            dados_especificos: this.obterDadosEspecificos()
        };
        
        try {
            const resultado = await API.post('../pagamentos/processar_pagamento.php', dadosPagamento);
            
            if (resultado && resultado.sucesso) {
                this.pagamentoId = resultado.pagamento_id;
                
                // Processar conforme método
                switch(this.metodoSelecionado) {
                    case 'mbway':
                        await this.processarMBWAY(resultado.referencia);
                        break;
                    case 'cartao':
                        await this.processarCartao();
                        break;
                    case 'transferencia':
                        await this.processarTransferencia();
                        break;
                    default:
                        this.finalizarPagamento(resultado);
                }
            } else {
                Utilitarios.mostrarNotificacao(resultado?.mensagem || 'Erro ao processar pagamento', 'erro');
                this.mostrarLoading(false);
            }
        } catch (erro) {
            console.error('Erro no pagamento:', erro);
            Utilitarios.mostrarNotificacao('Erro na comunicação com o servidor', 'erro');
            this.mostrarLoading(false);
        }
        
        return false;
    }
    
    // Validação específica por método
    static validarDadosMetodo() {
        switch(this.metodoSelecionado) {
            case 'cartao':
                const numeroCartao = document.getElementById('numero_cartao')?.value.replace(/\s/g, '');
                const validade = document.getElementById('validade')?.value;
                const cvv = document.getElementById('cvv')?.value;
                const nomeTitular = document.getElementById('nome_titular')?.value;
                
                if (!numeroCartao || numeroCartao.length < 16) {
                    Utilitarios.mostrarNotificacao('Número de cartão inválido', 'erro');
                    return false;
                }
                if (!validade || !this.validarValidade(validade)) {
                    Utilitarios.mostrarNotificacao('Data de validade inválida', 'erro');
                    return false;
                }
                if (!cvv || cvv.length < 3) {
                    Utilitarios.mostrarNotificacao('CVV inválido', 'erro');
                    return false;
                }
                if (!nomeTitular) {
                    Utilitarios.mostrarNotificacao('Nome do titular é obrigatório', 'erro');
                    return false;
                }
                break;
                
            case 'mbway':
                const telefone = document.getElementById('telefone_mbway')?.value;
                if (!telefone || telefone.length !== 9) {
                    Utilitarios.mostrarNotificacao('Número de telefone inválido (9 dígitos)', 'erro');
                    return false;
                }
                break;
                
            case 'transferencia':
                const comprovativo = document.getElementById('comprovativo')?.files[0];
                if (!comprovativo) {
                    Utilitarios.mostrarNotificacao('Por favor, anexe o comprovativo de transferência', 'aviso');
                    return false;
                }
                break;
        }
        
        return true;
    }
    
    // Obter dados específicos do método
    static obterDadosEspecificos() {
        const dados = {};
        
        switch(this.metodoSelecionado) {
            case 'cartao':
                dados.numero_cartao = document.getElementById('numero_cartao')?.value.replace(/\s/g, '');
                dados.validade = document.getElementById('validade')?.value;
                dados.cvv = document.getElementById('cvv')?.value;
                dados.nome_titular = document.getElementById('nome_titular')?.value;
                break;
                
            case 'mbway':
                dados.telefone = document.getElementById('telefone_mbway')?.value;
                break;
                
            case 'transferencia':
                dados.referencia = document.getElementById('ref_transferencia')?.innerText;
                dados.comprovativo = document.getElementById('comprovativo')?.files[0];
                break;
                
            case 'paypal':
                dados.email = document.getElementById('email_paypal')?.value;
                break;
        }
        
        return dados;
    }
    
    // Processar MB WAY
    static async processarMBWAY(referencia) {
        this.mostrarLoading(false);
        
        modal.abrir(`
            <div style="text-align: center; padding: 1rem;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📱</div>
                <h3>Pagamento MB WAY</h3>
                <p>Utilize o código abaixo na sua app MB WAY:</p>
                <div style="font-size: 2.5rem; font-weight: bold; color: var(--laranja); margin: 1rem 0; letter-spacing: 5px;">
                    ${referencia}
                </div>
                <p>Após confirmar na app, clique no botão abaixo.</p>
                <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem;">
                    <button class="btn btn-secundario" onclick="modal.fechar(); GestaoPagamentos.cancelarPagamento();">
                        Cancelar
                    </button>
                    <button class="btn btn-sucesso" onclick="GestaoPagamentos.verificarPagamentoMBWAY('${referencia}')">
                        Já confirmei na app
                    </button>
                </div>
            </div>
        `, 'MB WAY - Confirmação');
    }
    
    // Verificar pagamento MB WAY
    static async verificarPagamentoMBWAY(referencia) {
        this.mostrarLoading(true);
        
        const resultado = await API.post('../pagamentos/confirmar_pagamento.php', {
            referencia: referencia,
            metodo: 'mbway',
            pagamento_id: this.pagamentoId
        });
        
        this.mostrarLoading(false);
        
        if (resultado && resultado.sucesso) {
            modal.fechar();
            this.finalizarPagamento(resultado);
        } else {
            Utilitarios.mostrarNotificacao('Pagamento ainda não confirmado. Tente novamente.', 'aviso');
        }
    }
    
    // Processar pagamento com cartão
    static async processarCartao() {
        this.mostrarLoading(true);
        
        // Simular processamento de cartão (integração com gateway real)
        await this.sleep(2000);
        
        // Validar cartão (simulação)
        const numeroCartao = document.getElementById('numero_cartao')?.value.replace(/\s/g, '');
        const ultimosDigitos = numeroCartao.slice(-4);
        
        const resultado = {
            sucesso: true,
            mensagem: `Pagamento aprovado! Cartão terminado em ${ultimosDigitos}`,
            redirect: '../cliente/pagamentos.php'
        };
        
        this.mostrarLoading(false);
        this.finalizarPagamento(resultado);
    }
    
    // Processar transferência
    static async processarTransferencia() {
        const comprovativo = document.getElementById('comprovativo')?.files[0];
        
        // Upload do comprovativo
        const formData = new FormData();
        formData.append('comprovativo', comprovativo);
        formData.append('pagamento_id', this.pagamentoId);
        
        const resultado = await API.post('../pagamentos/upload_comprovativo.php', formData);
        
        if (resultado && resultado.sucesso) {
            Utilitarios.mostrarNotificacao('Comprovativo enviado! Pagamento será confirmado em breve.', 'sucesso');
            setTimeout(() => {
                window.location.href = '../cliente/pagamentos.php';
            }, 2000);
        } else {
            Utilitarios.mostrarNotificacao('Erro ao enviar comprovativo', 'erro');
            this.mostrarLoading(false);
        }
    }
    
    // Finalizar pagamento com sucesso
    static finalizarPagamento(resultado) {
        Utilitarios.mostrarNotificacao(resultado.mensagem || 'Pagamento realizado com sucesso!', 'sucesso');
        
        // Limpar formulário
        this.limparFormulario();
        
        // Redirecionar se necessário
        if (resultado.redirect) {
            setTimeout(() => {
                window.location.href = resultado.redirect;
            }, 2000);
        } else {
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        }
    }
    
    // Cancelar pagamento
    static async cancelarPagamento() {
        if (this.pagamentoId) {
            await API.post('../pagamentos/cancelar_pagamento.php', { pagamento_id: this.pagamentoId });
        }
        Utilitarios.mostrarNotificacao('Pagamento cancelado', 'info');
        this.limparFormulario();
    }
    
    // Limpar formulário
    static limparFormulario() {
        this.metodoSelecionado = null;
        this.pagamentoId = null;
        
        // Limpar campos
        document.querySelectorAll('.controlo-formulario').forEach(input => {
            if (input.type !== 'radio') input.value = '';
        });
        
        // Remover seleção de métodos
        document.querySelectorAll('.metodo-pagamento').forEach(el => {
            el.classList.remove('selecionado');
        });
        
        // Esconder formulários
        document.querySelectorAll('.dados-pagamento').forEach(el => {
            el.style.display = 'none';
        });
    }
    
    // Máscaras
    static mascaraNumeroCartao(e) {
        let valor = e.target.value.replace(/\D/g, '');
        valor = valor.replace(/(\d{4})/g, '$1 ').trim();
        if (valor.length > 19) valor = valor.slice(0, 19);
        e.target.value = valor;
    }
    
    static mascaraValidade(e) {
        let valor = e.target.value.replace(/\D/g, '');
        if (valor.length >= 2) {
            valor = valor.slice(0, 2) + '/' + valor.slice(2, 4);
        }
        if (valor.length > 5) valor = valor.slice(0, 5);
        e.target.value = valor;
    }
    
    static mascaraCVV(e) {
        let valor = e.target.value.replace(/\D/g, '');
        if (valor.length > 4) valor = valor.slice(0, 4);
        e.target.value = valor;
    }
    
    static mascaraTelefone(e) {
        let valor = e.target.value.replace(/\D/g, '');
        if (valor.length > 9) valor = valor.slice(0, 9);
        e.target.value = valor;
    }
    
    static validarValidade(validade) {
        const [mes, ano] = validade.split('/');
        if (!mes || !ano) return false;
        
        const mesNum = parseInt(mes);
        const anoNum = parseInt(ano);
        const dataAtual = new Date();
        const anoAtual = dataAtual.getFullYear() % 100;
        const mesAtual = dataAtual.getMonth() + 1;
        
        if (mesNum < 1 || mesNum > 12) return false;
        if (anoNum < anoAtual) return false;
        if (anoNum === anoAtual && mesNum < mesAtual) return false;
        
        return true;
    }
    
    static validarComprovativo(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const tiposPermitidos = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!tiposPermitidos.includes(file.type)) {
            Utilitarios.mostrarNotificacao('Formato inválido. Use JPEG, PNG ou PDF', 'erro');
            e.target.value = '';
            return;
        }
        
        if (file.size > 5 * 1024 * 1024) {
            Utilitarios.mostrarNotificacao('Ficheiro muito grande. Máximo 5MB', 'erro');
            e.target.value = '';
            return;
        }
    }
    
    static gerarReferenciaTransferencia() {
        const ref = 'RENT-' + Date.now() + '-' + Math.random().toString(36).substring(2, 8).toUpperCase();
        const span = document.getElementById('ref_transferencia');
        if (span) span.innerText = ref;
        return ref;
    }
    
    static mostrarLoading(show) {
        const loader = document.getElementById('loadingPagamento');
        if (loader) {
            loader.style.display = show ? 'flex' : 'none';
        }
        
        const btn = document.querySelector('#btnConfirmarPagamento');
        if (btn) {
            btn.disabled = show;
            btn.innerHTML = show ? ' Processando...' : ' Confirmar Pagamento';
        }
    }
    
    static sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

// Histórico de Pagamentos do Cliente
class HistoricoPagamentos {
    static async carregarHistorico() {
        const resultado = await API.get('../pagamentos/historico_pagamentos.php');
        if (resultado && resultado.sucesso) {
            this.renderizarHistorico(resultado.dados);
            this.atualizarEstatisticas(resultado.estatisticas);
        }
    }
    
    static renderizarHistorico(pagamentos) {
        const container = document.getElementById('historicoPagamentos');
        if (!container) return;
        
        if (pagamentos.length === 0) {
            container.innerHTML = '<div class="texto-centro" style="padding: 2rem;">Nenhum pagamento encontrado.</div>';
            return;
        }
        
        container.innerHTML = pagamentos.map(p => `
            <div class="cartao-pagamento" data-estado="${p.estado}">
                <div class="cabecalho-pagamento">
                    <div>
                        <strong>${p.referencia_pagamento}</strong>
                        <br>
                        <small>${Utilitarios.formatarDataHora(p.data_criacao)}</small>
                    </div>
                    <div class="valor-pagamento">€ ${parseFloat(p.valor).toFixed(2)}</div>
                </div>
                <div class="detalhes-pagamento">
                    <div>
                        <span class="metodo-icon">${this.getIconeMetodo(p.metodo_pagamento)}</span>
                        <strong>Método:</strong> ${this.getNomeMetodo(p.metodo_pagamento)}
                    </div>
                    <div>
                        <strong>Descrição:</strong> ${p.descricao || 'Pagamento de aluguer'}
                    </div>
                    <div>
                        <strong>Status:</strong>
                        <span class="etiqueta etiqueta-${p.estado === 'confirmado' ? 'sucesso' : p.estado === 'pendente' ? 'aviso' : 'perigo'}">
                            ${p.estado === 'confirmado' ? ' Confirmado' : p.estado === 'pendente' ? ' Pendente' : ' Falhou'}
                        </span>
                    </div>
                </div>
                ${p.estado === 'confirmado' ? `
                    <div style="margin-top: 0.75rem; text-align: right;">
                        <button class="btn btn-info btn-sm" onclick="HistoricoPagamentos.emitirRecibo(${p.id})">
                             Baixar Recibo
                        </button>
                    </div>
                ` : ''}
            </div>
        `).join('');
    }
    
    static atualizarEstatisticas(estatisticas) {
        if (!estatisticas) return;
        
        const totalPago = document.getElementById('total_pago');
        if (totalPago) totalPago.innerHTML = `MZN ${parseFloat(estatisticas.total_pago || 0).toFixed(2)}`;
        
        const totalPendente = document.getElementById('total_pendente');
        if (totalPendente) totalPendente.innerHTML = `MZM ${parseFloat(estatisticas.total_pendente || 0).toFixed(2)}`;
        
        const totalPagamentos = document.getElementById('total_pagamentos');
        if (totalPagamentos) totalPagamentos.innerHTML = estatisticas.total_pagamentos || 0;
    }
    
    static async emitirRecibo(pagamentoId) {
        window.open(`../pagamentos/recibo.php?id=${pagamentoId}`, '_blank');
    }
    
    static getIconeMetodo(metodo) {
        const icones = {
            'dinheiro': '',
            'cartao_credito': '',
            'cartao_debito': '',
            'mbway': '',
            'transferencia': '',
            'paypal': ' '
        };
        return icones[metodo] || '';
    }
    
    static getNomeMetodo(metodo) {
        const nomes = {
            'dinheiro': 'Dinheiro',
            'cartao_credito': 'Cartão Crédito',
            'cartao_debito': 'Cartão Débito',
            'mbway': 'MB WAY',
            'transferencia': 'Transferência Bancária',
            'paypal': 'PayPal'
        };
        return nomes[metodo] || metodo;
    }
    
    static filtrarPorEstado(estado) {
        const cards = document.querySelectorAll('.cartao-pagamento');
        cards.forEach(card => {
            if (estado === 'todos') {
                card.style.display = 'block';
            } else {
                card.style.display = card.dataset.estado === estado ? 'block' : 'none';
            }
        });
        
        // Atualizar botões ativos
        document.querySelectorAll('.filtro-btn').forEach(btn => {
            btn.classList.remove('ativo');
        });
        event.target.classList.add('ativo');
    }
}

// Estatísticas de Pagamentos para Admin
class EstatisticasPagamentos {
    static async carregarDashboard() {
        const resultado = await API.get('../pagamentos/api_pagamentos.php?acao=dashboard');
        if (resultado && resultado.sucesso) {
            this.atualizarKPIs(resultado.kpis);
            this.inicializarGraficos(resultado.graficos);
            this.renderizarTabela(resultado.pagamentos_recentes);
        }
    }
    
    static atualizarKPIs(kpis) {
        if (!kpis) return;
        
        document.getElementById('total_receita')?.innerHTML = `MZN ${parseFloat(kpis.total_receita || 0).toLocaleString()}`;
        document.getElementById('total_transacoes')?.innerHTML = kpis.total_transacoes || 0;
        document.getElementById('total_pendentes')?.innerHTML = kpis.total_pendentes || 0;
        document.getElementById('valor_medio')?.innerHTML = `MZN ${parseFloat(kpis.valor_medio || 0).toFixed(2)}`;
    }
    
    static inicializarGraficos(dados) {
        // Gráfico de Receita Mensal
        const ctxReceita = document.getElementById('graficoReceita')?.getContext('2d');
        if (ctxReceita && GraficosAdmin) {
            GraficosAdmin.initGraficoReceita(ctxReceita, dados?.receita_mensal);
        }
        
        // Gráfico de Métodos de Pagamento
        const ctxMetodos = document.getElementById('graficoMetodos')?.getContext('2d');
        if (ctxMetodos && GraficosAdmin) {
            GraficosAdmin.initGraficoPagamentos(ctxMetodos, dados?.metodos);
        }
    }
    
    static renderizarTabela(pagamentos) {
        const tbody = document.getElementById('tabelaPagamentos');
        if (!tbody) return;
        
        if (!pagamentos || pagamentos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="texto-centro">Nenhum pagamento encontrado</td></tr>';
            return;
        }
        
        tbody.innerHTML = pagamentos.map(p => `
            <tr>
                <td>${p.referencia_pagamento}</td>
                <td>${p.cliente_nome}</td>
                <td>€ ${parseFloat(p.valor).toFixed(2)}</td>
                <td>${HistoricoPagamentos.getNomeMetodo(p.metodo_pagamento)}</td>
                <td>${Utilitarios.formatarData(p.data_criacao)}</td>
                <td><span class="etiqueta etiqueta-${p.estado === 'confirmado' ? 'sucesso' : p.estado === 'pendente' ? 'aviso' : 'perigo'}">${p.estado}</span></td>
                <td class="tabela-acoes">
                    <button class="btn btn-info btn-sm" onclick="HistoricoPagamentos.emitirRecibo(${p.id})"></button>
                </td>
            </tr>
        `).join('');
    }
    
    static async exportarExcel() {
        window.location.href = '../pagamentos/api_pagamentos.php?acao=exportar_excel';
    }
    
    static async exportarPDF() {
        window.location.href = '../pagamentos/api_pagamentos.php?acao=exportar_pdf';
    }
}

// Inicialização baseada na página atual
document.addEventListener('DOMContentLoaded', () => {
    // Página de pagamento do cliente
    if (document.getElementById('formularioPagamento')) {
        const valorTotal = parseFloat(document.getElementById('valor_total')?.value || 0);
        const reservaId = document.getElementById('reserva_id')?.value;
        const aluguerId = document.getElementById('aluguer_id')?.value;
        
        GestaoPagamentos.initFormularioPagamento(valorTotal, reservaId, aluguerId);
        
        const btnConfirmar = document.getElementById('btnConfirmarPagamento');
        if (btnConfirmar) {
            btnConfirmar.addEventListener('click', () => GestaoPagamentos.processarPagamento());
        }
    }
    
    // Página de histórico do cliente
    if (document.getElementById('historicoPagamentos')) {
        HistoricoPagamentos.carregarHistorico();
        
        // Configurar filtros
        document.querySelectorAll('.filtro-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                HistoricoPagamentos.filtrarPorEstado(e.target.dataset.filtro);
            });
        });
    }
    
    // Dashboard de pagamentos do admin
    if (document.getElementById('dashboardPagamentos')) {
        EstatisticasPagamentos.carregarDashboard();
    }
});

// Exportar para uso global
window.GestaoPagamentos = GestaoPagamentos;
window.HistoricoPagamentos = HistoricoPagamentos;
window.EstatisticasPagamentos = EstatisticasPagamentos;