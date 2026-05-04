// ============================================
// FUNCIONÁRIO - JAVASCRIPT ESPECÍFICO
// ============================================

// Gestão de Reservas (Funcionário)
class FuncionarioReservas {
    static async carregarReservasPendentes() {
        const resultado = await API.get('../reservas.php?acao=pendentes');
        if (resultado && resultado.sucesso) {
            this.renderizarReservas(resultado.dados);
        }
    }
    
    static renderizarReservas(reservas) {
        const container = document.getElementById('reservasPendentes');
        if (!container) return;
        
        if (reservas.length === 0) {
            container.innerHTML = '<div class="texto-centro" style="padding: 2rem;">Não há reservas pendentes.</div>';
            return;
        }
        
        container.innerHTML = `
            <div class="container-tabela">
                <table class="tabela">
                    <thead>
                        <tr><th>Cliente</th><th>Viatura</th><th>Datas</th><th>Valor</th><th>Ações</th></tr>
                    </thead>
                    <tbody>
                        ${reservas.map(r => `
                            <tr>
                                <td>${r.cliente_nome}<br><small>${r.cliente_email}</small></td>
                                <td>${r.marca} ${r.modelo}</td>
                                <td>${Utilitarios.formatarData(r.data_inicio)} - ${Utilitarios.formatarData(r.data_fim)}<br><small>${r.total_dias} dias</small></td>
                                <td>€ ${parseFloat(r.preco_total).toFixed(2)}</td>
                                <td class="tabela-acoes">
                                    <button class="btn btn-sucesso btn-sm" onclick="FuncionarioReservas.confirmar(${r.id})">✅ Confirmar</button>
                                    <button class="btn btn-perigo btn-sm" onclick="FuncionarioReservas.rejeitar(${r.id})">❌ Rejeitar</button>
                                </td>
                            </td>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }
    
    static async confirmar(reservaId) {
        const resultado = await API.post('../reservas.php?acao=confirmar', { reserva_id: reservaId });
        if (resultado && resultado.sucesso) {
            Utilitarios.mostrarNotificacao('Reserva confirmada com sucesso!', 'sucesso');
            this.carregarReservasPendentes();
        }
    }
    
    static async rejeitar(reservaId) {
        modal.confirmar('Tem certeza que deseja rejeitar esta reserva?', async () => {
            const resultado = await API.post('../reservas.php?acao=rejeitar', { reserva_id: reservaId });
            if (resultado && resultado.sucesso) {
                Utilitarios.mostrarNotificacao('Reserva rejeitada!', 'aviso');
                this.carregarReservasPendentes();
            }
        });
    }
}

// Gestão de Aluguéis
class FuncionarioAlugueis {
    static async buscarCliente(termo) {
        const resultado = await API.get(`../clientes.php?acao=buscar&termo=${encodeURIComponent(termo)}`);
        if (resultado && resultado.sucesso) {
            this.mostrarResultadosBusca(resultado.dados);
        }
    }
    
    static mostrarResultadosBusca(clientes) {
        const container = document.getElementById('resultadosBusca');
        if (!container) return;
        
        if (clientes.length === 0) {
            container.innerHTML = '<div class="resultado-item">Nenhum cliente encontrado</div>';
            container.classList.add('ativo');
            return;
        }
        
        container.innerHTML = clientes.map(c => `
            <div class="resultado-item" onclick="FuncionarioAlugueis.selecionarCliente(${c.id}, '${c.nome}', '${c.email}')">
                <strong>${c.nome}</strong><br>
                <small>${c.email} | ${c.telefone || 'Sem telefone'}</small>
            </div>
        `).join('');
        container.classList.add('ativo');
    }
    
    static selecionarCliente(id, nome, email) {
        document.getElementById('cliente_id').value = id;
        document.getElementById('cliente_nome').value = nome;
        document.getElementById('cliente_email').value = email;
        
        const container = document.getElementById('resultadosBusca');
        container.classList.remove('ativo');
        
        // Carregar reservas do cliente
        this.carregarReservasCliente(id);
    }
    
    static async carregarReservasCliente(clienteId) {
        const resultado = await API.get(`../reservas.php?acao=por_cliente&cliente_id=${clienteId}`);
        if (resultado && resultado.sucesso) {
            this.renderizarReservasCliente(resultado.dados);
        }
    }
    
    static renderizarReservasCliente(reservas) {
        const select = document.getElementById('reserva_id');
        if (!select) return;
        
        select.innerHTML = '<option value="">Selecionar reserva (opcional)</option>' +
            reservas.filter(r => r.status === 'confirmada').map(r => `
                <option value="${r.id}">${r.marca} ${r.modelo} - ${Utilitarios.formatarData(r.data_inicio)} a ${Utilitarios.formatarData(r.data_fim)}</option>
            `).join('');
    }
    
    static async carregarViaturasDisponiveis() {
        const resultado = await API.get('../viaturas.php?acao=disponiveis');
        if (resultado && resultado.sucesso) {
            const select = document.getElementById('viatura_id');
            if (select) {
                select.innerHTML = '<option value="">Selecionar viatura</option>' +
                    resultado.dados.map(v => `
                        <option value="${v.id}" data-preco="${v.preco_dia}">${v.marca} ${v.modelo} - MZN ${v.preco_dia}/dia</option>
                    `).join('');
            }
        }
    }
    
    static calcularPreco() {
        const viaturaSelect = document.getElementById('viatura_id');
        const dataInicio = document.getElementById('data_inicio').value;
        const dataFim = document.getElementById('data_fim').value;
        
        if (!viaturaSelect.value || !dataInicio || !dataFim) return;
        
        const precoDia = parseFloat(viaturaSelect.options[viaturaSelect.selectedIndex].dataset.preco);
        const dias = Utilitarios.calcularDias(dataInicio, dataFim);
        const total = precoDia * dias;
        
        document.getElementById('total_dias').value = dias;
        document.getElementById('preco_total').value = total.toFixed(2);
        document.getElementById('total_exibir').innerHTML = `MZN ${total.toFixed(2)}`;
    }
    
    static async registrarAluguer() {
        if (!FormularioDinamico.validarFormulario('formAluguer')) {
            Utilitarios.mostrarNotificacao('Preencha todos os campos obrigatórios', 'aviso');
            return;
        }
        
        const formData = new FormData(document.getElementById('formAluguer'));
        const dados = Object.fromEntries(formData);
        
        const resultado = await API.post('../alugueis.php?acao=registrar', dados);
        
        if (resultado && resultado.sucesso) {
            Utilitarios.mostrarNotificacao('Aluguer registado com sucesso!', 'sucesso');
            setTimeout(() => {
                window.location.href = '../funcionario/dashboard.php';
            }, 2000);
        } else {
            Utilitarios.mostrarNotificacao(resultado?.mensagem || 'Erro ao registar aluguer', 'erro');
        }
    }
    
    static async processarDevolucao() {
        const aluguerId = document.getElementById('aluguer_id').value;
        if (!aluguerId) {
            Utilitarios.mostrarNotificacao('Selecione um aluguer ativo', 'aviso');
            return;
        }
        
        const resultado = await API.post('../alugueis.php?acao=devolver', { aluguer_id: aluguerId });
        
        if (resultado && resultado.sucesso) {
            Utilitarios.mostrarNotificacao('Devolução processada com sucesso!', 'sucesso');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            Utilitarios.mostrarNotificacao(resultado?.mensagem || 'Erro ao processar devolução', 'erro');
        }
    }
    
    static async carregarAlugueisAtivos() {
        const resultado = await API.get('../alugueis.php?acao=ativos');
        if (resultado && resultado.sucesso) {
            const select = document.getElementById('aluguer_id');
            if (select) {
                select.innerHTML = '<option value="">Selecionar aluguer</option>' +
                    resultado.dados.map(a => `
                        <option value="${a.id}" data-fim="${a.data_fim}">${a.cliente_nome} - ${a.marca} ${a.modelo} - até ${Utilitarios.formatarData(a.data_fim)}</option>
                    `).join('');
                
                select.addEventListener('change', () => {
                    const dataFim = select.options[select.selectedIndex]?.dataset.fim;
                    if (dataFim) {
                        const hoje = new Date();
                        const fim = new Date(dataFim);
                        const diasAtraso = Math.max(0, Math.ceil((hoje - fim) / (1000 * 60 * 60 * 24)));
                        
                        if (diasAtraso > 0) {
                            const multa = diasAtraso * 25;
                            document.getElementById('multa_info').style.display = 'block';
                            document.getElementById('multa_valor').innerHTML = `€ ${multa.toFixed(2)} (${diasAtraso} dias de atraso)`;
                        } else {
                            document.getElementById('multa_info').style.display = 'none';
                        }
                    }
                });
            }
        }
    }
}

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('reservasPendentes')) {
        FuncionarioReservas.carregarReservasPendentes();
    }
    
    if (document.getElementById('formAluguer')) {
        FuncionarioAlugueis.carregarViaturasDisponiveis();
        
        const buscaCliente = document.getElementById('busca_cliente');
        if (buscaCliente) {
            let timeout;
            buscaCliente.addEventListener('input', (e) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    if (e.target.value.length >= 3) {
                        FuncionarioAlugueis.buscarCliente(e.target.value);
                    }
                }, 500);
            });
            
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.busca-cliente')) {
                    document.getElementById('resultadosBusca')?.classList.remove('ativo');
                }
            });
        }
        
        document.getElementById('data_inicio')?.addEventListener('change', FuncionarioAlugueis.calcularPreco);
        document.getElementById('data_fim')?.addEventListener('change', FuncionarioAlugueis.calcularPreco);
        document.getElementById('viatura_id')?.addEventListener('change', FuncionarioAlugueis.calcularPreco);
    }
    
    if (document.getElementById('aluguer_id')) {
        FuncionarioAlugueis.carregarAlugueisAtivos();
    }
});

window.FuncionarioReservas = FuncionarioReservas;
window.FuncionarioAlugueis = FuncionarioAlugueis;