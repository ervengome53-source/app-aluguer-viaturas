// ============================================
// CLIENTE - JAVASCRIPT ESPECÍFICO
// ============================================

// Gestão de Reservas do Cliente
class ClienteReservas {
    static async criarReserva(veiculoId, dataInicio, dataFim) {
        const dados = {
            veiculo_id: veiculoId,
            data_inicio: dataInicio,
            data_fim: dataFim
        };
        
        const resultado = await API.post('../reservas.php?acao=criar', dados);
        
        if (resultado && resultado.sucesso) {
            Utilitarios.mostrarNotificacao('Reserva realizada com sucesso! Aguarde confirmação.', 'sucesso');
            setTimeout(() => {
                window.location.href = '../cliente/reservas.php';
            }, 2000);
        } else {
            Utilitarios.mostrarNotificacao(resultado?.mensagem || 'Erro ao criar reserva', 'erro');
        }
    }
    
    static async cancelarReserva(reservaId) {
        modal.confirmar('Tem certeza que deseja cancelar esta reserva?', async () => {
            const resultado = await API.post('../reservas.php?acao=cancelar', { reserva_id: reservaId });
            
            if (resultado && resultado.sucesso) {
                Utilitarios.mostrarNotificacao('Reserva cancelada com sucesso!', 'sucesso');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                Utilitarios.mostrarNotificacao(resultado?.mensagem || 'Erro ao cancelar reserva', 'erro');
            }
        });
    }
    
    static async carregarMinhasReservas() {
        const resultado = await API.get('../reservas.php?acao=minhas');
        if (resultado && resultado.sucesso) {
            this.renderizarReservas(resultado.dados);
        }
    }
    
    static renderizarReservas(reservas) {
        const container = document.getElementById('minhasReservas');
        if (!container) return;
        
        if (reservas.length === 0) {
            container.innerHTML = '<div class="texto-centro" style="padding: 2rem;">Nenhuma reserva encontrada.</div>';
            return;
        }
        
        container.innerHTML = reservas.map(r => `
            <div class="reserva-card">
                <div class="reserva-info">
                    <h4>${r.marca} ${r.modelo}</h4>
                    <div class="reserva-datas">
                         ${Utilitarios.formatarData(r.data_inicio)} - ${Utilitarios.formatarData(r.data_fim)}
                    </div>
                    <div class="reserva-preco"> Mts ${parseFloat(r.preco_total).toFixed(2)}</div>
                </div>
                <div>
                    <span class="etiqueta etiqueta-${r.status === 'pendente' ? 'aviso' : r.status === 'confirmada' ? 'sucesso' : 'perigo'}">
                        ${r.status === 'pendente' ? ' Pendente' : r.status === 'confirmada' ? ' Confirmada' : 'Cancelada'}
                    </span>
                    ${r.status === 'pendente' ? `
                        <button class="btn btn-perigo btn-sm" onclick="ClienteReservas.cancelarReserva(${r.id})" style="margin-top: 0.5rem;">
                            Cancelar
                        </button>
                    ` : ''}
                </div>
            </div>
        `).join('');
    }
}

// Gestão de Perfil
class ClientePerfil {
    static async atualizarPerfil() {
        const form = document.getElementById('formPerfil');
        if (!FormularioDinamico.validarFormulario('formPerfil')) {
            Utilitarios.mostrarNotificacao('Preencha todos os campos obrigatórios', 'aviso');
            return;
        }
        
        const formData = new FormData(form);
        const dados = Object.fromEntries(formData);
        
        const resultado = await API.post('../perfil.php?acao=atualizar', dados);
        
        if (resultado && resultado.sucesso) {
            Utilitarios.mostrarNotificacao('Perfil atualizado com sucesso!', 'sucesso');
        } else {
            Utilitarios.mostrarNotificacao(resultado?.mensagem || 'Erro ao atualizar perfil', 'erro');
        }
    }
    
    static async alterarSenha() {
        const senhaAtual = document.getElementById('senha_atual').value;
        const novaSenha = document.getElementById('nova_senha').value;
        const confirmarSenha = document.getElementById('confirmar_senha').value;
        
        if (novaSenha !== confirmarSenha) {
            Utilitarios.mostrarNotificacao('As senhas não coincidem', 'aviso');
            return;
        }
        
        if (novaSenha.length < 6) {
            Utilitarios.mostrarNotificacao('A nova senha deve ter no mínimo 6 caracteres', 'aviso');
            return;
        }
        
        const resultado = await API.post('../perfil.php?acao=alterar_senha', {
            senha_atual: senhaAtual,
            nova_senha: novaSenha
        });
        
        if (resultado && resultado.sucesso) {
            Utilitarios.mostrarNotificacao('Senha alterada com sucesso!', 'sucesso');
            document.getElementById('formSenha').reset();
        } else {
            Utilitarios.mostrarNotificacao(resultado?.mensagem || 'Erro ao alterar senha', 'erro');
        }
    }
}

// Histórico de Aluguéis
class ClienteHistorico {
    static async carregarHistorico() {
        const resultado = await API.get('../historico.php');
        if (resultado && resultado.sucesso) {
            this.renderizarHistorico(resultado.dados);
        }
    }
    
    static renderizarHistorico(historico) {
        const container = document.getElementById('historicoAlugueis');
        if (!container) return;
        
        if (historico.length === 0) {
            container.innerHTML = '<div class="texto-centro" style="padding: 2rem;">Nenhum aluguer encontrado no histórico.</div>';
            return;
        }
        
        container.innerHTML = `
            <div class="container-tabela">
                <table class="tabela">
                    <thead>
                        <tr><th>Viatura</th><th>Período</th><th>Valor</th><th>Status</th><th>Data Devolução</th></tr>
                    </thead>
                    <tbody>
                        ${historico.map(a => `
                            <tr>
                                <td>${a.marca} ${a.modelo}</td>
                                <td>${Utilitarios.formatarData(a.data_inicio)} - ${Utilitarios.formatarData(a.data_fim)}</td>
                                <td>€ ${parseFloat(a.preco_total).toFixed(2)}</td>
                                <td><span class="etiqueta etiqueta-${a.status === 'finalizado' ? 'sucesso' : 'aviso'}">${a.status}</span></td>
                                <td>${a.data_devolucao ? Utilitarios.formatarData(a.data_devolucao) : '-'}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }
}

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    // Carregar reservas se estiver na página de reservas
    if (document.getElementById('minhasReservas')) {
        ClienteReservas.carregarMinhasReservas();
    }
    
    // Carregar histórico se estiver na página de histórico
    if (document.getElementById('historicoAlugueis')) {
        ClienteHistorico.carregarHistorico();
    }
});

window.ClienteReservas = ClienteReservas;
window.ClientePerfil = ClientePerfil;
window.ClienteHistorico = ClienteHistorico;