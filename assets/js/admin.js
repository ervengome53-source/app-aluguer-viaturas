// ============================================
// ADMIN - JAVASCRIPT ESPECÍFICO
// ============================================

// Gráficos
class GraficosAdmin {
    static initGraficoReceita(ctx, dados) {
        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
                datasets: [{
                    label: 'Receita (€)',
                    data: dados || [12500, 15000, 18200, 21000, 23500, 28900, 31200, 34500, 37800, 40200, 43500, 46800],
                    borderColor: '#FF8C00',
                    backgroundColor: 'rgba(255, 140, 0, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { callbacks: { label: (ctx) => `€ ${ctx.raw.toLocaleString()}` } }
                }
            }
        });
    }
    
    static initGraficoViaturas(ctx, dados) {
        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dados?.labels || ['Honda Civic', 'BMW X5', 'Toyota Corolla', 'Ford Focus', 'Hyundai Tucson'],
                datasets: [{
                    label: 'Nº de Aluguer',
                    data: dados?.valores || [45, 38, 42, 35, 30],
                    backgroundColor: '#1E3A5F',
                    borderColor: '#FF8C00',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, title: { display: true, text: 'Quantidade' } } }
            }
        });
    }
    
    static initGraficoPagamentos(ctx, dados) {
        return new Chart(ctx, {
            type: 'pie',
            data: {
                labels: dados?.labels || ['Dinheiro', 'Cartão', 'MB WAY', 'Transferência', 'PayPal'],
                datasets: [{
                    data: dados?.valores || [30, 25, 20, 15, 10],
                    backgroundColor: ['#FF8C00', '#1E3A5F', '#28a745', '#17a2b8', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
}

// CRUD de Viaturas
class GestaoViaturas {
    static async carregarViaturas() {
        const resultado = await API.get('../viaturas.php');
        if (resultado && resultado.sucesso) {
            this.renderizarTabela(resultado.dados);
        }
    }
    
    static renderizarTabela(viaturas) {
        const tbody = document.getElementById('tabelaViaturas');
        if (!tbody) return;
        
        tbody.innerHTML = viaturas.map(v => `
            <tr>
                <td>${v.id}</td>
                <td>${v.marca} ${v.modelo}</td>
                <td>${v.matricula}</td>
                <td>€ ${parseFloat(v.preco_dia).toFixed(2)}</td>
                <td><span class="etiqueta etiqueta-${v.status === 'disponivel' ? 'sucesso' : 'aviso'}">${v.status}</span></td>
                <td class="tabela-acoes">
                    <button class="btn btn-info btn-sm" onclick="GestaoViaturas.editar(${v.id})"></button>
                    <button class="btn btn-perigo btn-sm" onclick="GestaoViaturas.excluir(${v.id})">️</button>
                </td>
            </tr>
        `).join('');
    }
    
    static async editar(id) {
        const viatura = await API.get(`../viaturas.php?id=${id}`);
        if (viatura && viatura.sucesso) {
            this.abrirModalEdicao(viatura.dados);
        }
    }
    
    static abrirModalEdicao(viatura) {
        modal.abrir(`
            <form id="formEditarViatura">
                <input type="hidden" name="id" value="${viatura.id}">
                <div class="form-row">
                    <div class="grupo-formulario">
                        <label class="rotulo-formulario">Marca</label>
                        <input type="text" name="marca" class="controlo-formulario" value="${viatura.marca}" required>
                    </div>
                    <div class="grupo-formulario">
                        <label class="rotulo-formulario">Modelo</label>
                        <input type="text" name="modelo" class="controlo-formulario" value="${viatura.modelo}" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="grupo-formulario">
                        <label class="rotulo-formulario">Ano</label>
                        <input type="number" name="ano" class="controlo-formulario" value="${viatura.ano}" required>
                    </div>
                    <div class="grupo-formulario">
                        <label class="rotulo-formulario">Matrícula</label>
                        <input type="text" name="matricula" class="controlo-formulario" value="${viatura.matricula}" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="grupo-formulario">
                        <label class="rotulo-formulario">Preço por dia (€)</label>
                        <input type="number" step="0.01" name="preco_dia" class="controlo-formulario" value="${viatura.preco_dia}" required>
                    </div>
                    <div class="grupo-formulario">
                        <label class="rotulo-formulario">Status</label>
                        <select name="status" class="controlo-formulario">
                            <option value="disponivel" ${viatura.status === 'disponivel' ? 'selected' : ''}>Disponível</option>
                            <option value="alugado" ${viatura.status === 'alugado' ? 'selected' : ''}>Alugado</option>
                            <option value="manutencao" ${viatura.status === 'manutencao' ? 'selected' : ''}>Manutenção</option>
                        </select>
                    </div>
                </div>
                <div class="grupo-formulario">
                    <label class="rotulo-formulario">Descrição</label>
                    <textarea name="descricao" class="controlo-formulario" rows="3">${viatura.descricao || ''}</textarea>
                </div>
                <button type="submit" class="btn btn-primario w-100">Guardar Alterações</button>
            </form>
        `, 'Editar Viatura');
        
        document.getElementById('formEditarViatura').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const dados = Object.fromEntries(formData);
            
            const resultado = await API.post('../viaturas.php?acao=editar', dados);
            if (resultado && resultado.sucesso) {
                Utilitarios.mostrarNotificacao('Viatura atualizada com sucesso!', 'sucesso');
                modal.fechar();
                this.carregarViaturas();
            }
        });
    }
    
    static async excluir(id) {
        modal.confirmar('Tem certeza que deseja excluir esta viatura?', async () => {
            const resultado = await API.post(`../viaturas.php?acao=excluir&id=${id}`);
            if (resultado && resultado.sucesso) {
                Utilitarios.mostrarNotificacao('Viatura excluída com sucesso!', 'sucesso');
                this.carregarViaturas();
            }
        });
    }
    
    static async salvarNova() {
        const form = document.getElementById('formNovaViatura');
        if (!FormularioDinamico.validarFormulario('formNovaViatura')) {
            Utilitarios.mostrarNotificacao('Preencha todos os campos obrigatórios', 'aviso');
            return;
        }
        
        const formData = new FormData(form);
        const dados = Object.fromEntries(formData);
        
        const resultado = await API.post('../viaturas.php?acao=criar', dados);
        if (resultado && resultado.sucesso) {
            Utilitarios.mostrarNotificacao('Viatura adicionada com sucesso!', 'sucesso');
            form.reset();
            this.carregarViaturas();
        }
    }
}

// Gestão de Utilizadores
class GestaoUtilizadores {
    static async carregarUtilizadores() {
        const resultado = await API.get('../usuarios.php');
        if (resultado && resultado.sucesso) {
            this.renderizarTabela(resultado.dados);
        }
    }
    
    static renderizarTabela(utilizadores) {
        const tbody = document.getElementById('tabelaUtilizadores');
        if (!tbody) return;
        
        tbody.innerHTML = utilizadores.map(u => `
            <tr>
                <td>${u.id}</td>
                <td>${u.nome}</td>
                <td>${u.email}</td>
                <td><span class="etiqueta etiqueta-${u.cargo === 'admin' ? 'perigo' : u.cargo === 'funcionario' ? 'info' : 'sucesso'}">${u.cargo}</span></td>
                <td><span class="etiqueta etiqueta-${u.status === 'ativo' ? 'sucesso' : 'perigo'}">${u.status}</span></td>
                <td class="tabela-acoes">
                    <button class="btn btn-info btn-sm" onclick="GestaoUtilizadores.editar(${u.id})"></button>
                    <button class="btn btn-${u.status === 'ativo' ? 'perigo' : 'sucesso'} btn-sm" onclick="GestaoUtilizadores.alterarStatus(${u.id}, '${u.status}')">
                        ${u.status === 'ativo' ? 'Bloquear' : 'Ativar'}
                    </button>
                </td>
            </tr>
        `).join('');
    }
    
    static async alterarStatus(id, statusAtual) {
        const novaStatus = statusAtual === 'ativo' ? 'inativo' : 'ativo';
        const acao = novaStatus === 'ativo' ? 'ativar' : 'bloquear';
        
        modal.confirmar(`Tem certeza que deseja ${acao} este utilizador?`, async () => {
            const resultado = await API.post(`../usuarios.php?acao=alterar_status`, { id, status: novaStatus });
            if (resultado && resultado.sucesso) {
                Utilitarios.mostrarNotificacao(`Utilizador ${acao}do com sucesso!`, 'sucesso');
                this.carregarUtilizadores();
            }
        });
    }
}

// Exportar
window.GraficosAdmin = GraficosAdmin;
window.GestaoViaturas = GestaoViaturas;
window.GestaoUtilizadores = GestaoUtilizadores;