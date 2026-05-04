// ============================================
// SISTEMA DE ALUGUER DE VIATURAS - JAVASCRIPT GLOBAL
// ============================================

// Utilitários Globais
const Utilitarios = {
    formatarMoeda: (valor) => {
        return new Intl.NumberFormat('pt-PT', { 
            style: 'currency', 
            currency: 'MZN' 
        }).format(valor);
    },
    
    formatarData: (data) => {
        if (!data) return '';
        return new Date(data).toLocaleDateString('pt-PT');
    },
    
    formatarDataHora: (data) => {
        if (!data) return '';
        return new Date(data).toLocaleDateString('pt-PT', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    mostrarNotificacao: (mensagem, tipo = 'sucesso') => {
        const notificacao = document.createElement('div');
        notificacao.className = `notificacao ${tipo}`;
        notificacao.innerHTML = `
            <span>${tipo === 'sucesso' ? '..' : tipo === 'erro' ? '..' : tipo === 'aviso' ? ' ..' : ' ..'}</span>
            <span>${mensagem}</span>
        `;
        document.body.appendChild(notificacao);
        
        setTimeout(() => {
            notificacao.style.animation = 'notificacaoEntrar 0.3s ease reverse';
            setTimeout(() => notificacao.remove(), 300);
        }, 4000);
    },
    
    validarEmail: (email) => {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },
    
    validarTelefone: (telefone) => {
        const re = /^[0-9]{9}$/;
        return re.test(telefone);
    },
    
    calcularDias: (dataInicio, dataFim) => {
        const inicio = new Date(dataInicio);
        const fim = new Date(dataFim);
        const diffTime = Math.abs(fim - inicio);
        return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
    },
    
    obterParametroUrl: (param) => {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(param);
    },
    
    redirecionar: (url) => {
        window.location.href = url;
    },
    
    recarregar: () => {
        window.location.reload();
    }
};

// API Calls
const API = {
    baseUrl: '/api',
    
    async request(endpoint, metodo = 'GET', dados = null) {
        const options = {
            method: metodo,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        if (dados && (metodo === 'POST' || metodo === 'PUT')) {
            options.body = JSON.stringify(dados);
        }
        
        try {
            const resposta = await fetch(`${this.baseUrl}${endpoint}`, options);
            const resultado = await resposta.json();
            
            if (!resposta.ok) {
                throw new Error(resultado.mensagem || 'Erro na requisição');
            }
            
            return resultado;
        } catch (erro) {
            console.error('API Error:', erro);
            Utilitarios.mostrarNotificacao(erro.message, 'erro');
            return null;
        }
    },
    
    get: (endpoint) => this.request(endpoint, 'GET'),
    post: (endpoint, dados) => this.request(endpoint, 'POST', dados),
    put: (endpoint, dados) => this.request(endpoint, 'PUT', dados),
    delete: (endpoint) => this.request(endpoint, 'DELETE')
};

// Modal Handler
class ModalManager {
    constructor() {
        this.modal = null;
        this.init();
    }
    
    init() {
        this.modal = document.createElement('div');
        this.modal.className = 'modal';
        document.body.appendChild(this.modal);
    }
    
    abrir(conteudo, titulo = 'Informação') {
        this.modal.innerHTML = `
            <div class="conteudo-modal">
                <div class="cabecalho-modal">
                    <h3>${titulo}</h3>
                    <button class="fechar-modal" onclick="modal.fechar()">&times;</button>
                </div>
                <div class="corpo-modal">
                    ${conteudo}
                </div>
                <div class="rodape-modal">
                    <button class="btn btn-secundario" onclick="modal.fechar()">Fechar</button>
                </div>
            </div>
        `;
        this.modal.classList.add('ativo');
    }
    
    confirmar(mensagem, onConfirmar) {
        this.modal.innerHTML = `
            <div class="conteudo-modal">
                <div class="cabecalho-modal">
                    <h3>Confirmar Ação</h3>
                    <button class="fechar-modal" onclick="modal.fechar()">&times;</button>
                </div>
                <div class="corpo-modal">
                    <p>${mensagem}</p>
                </div>
                <div class="rodape-modal">
                    <button class="btn btn-secundario" onclick="modal.fechar()">Cancelar</button>
                    <button class="btn btn-perigo" id="confirmarBtn">Confirmar</button>
                </div>
            </div>
        `;
        this.modal.classList.add('ativo');
        
        document.getElementById('confirmarBtn').addEventListener('click', () => {
            if (onConfirmar) onConfirmar();
            this.fechar();
        });
    }
    
    fechar() {
        this.modal.classList.remove('ativo');
    }
}

// Inicializar modal global
const modal = new ModalManager();

// Formulários Dinâmicos
class FormularioDinamico {
    static mascaraTelefone(input) {
        input.addEventListener('input', (e) => {
            let valor = e.target.value.replace(/\D/g, '');
            if (valor.length > 9) valor = valor.slice(0, 9);
            e.target.value = valor;
        });
    }
    
    static mascaraNumeroCartao(input) {
        input.addEventListener('input', (e) => {
            let valor = e.target.value.replace(/\D/g, '');
            valor = valor.replace(/(\d{4})/g, '$1 ').trim();
            if (valor.length > 19) valor = valor.slice(0, 19);
            e.target.value = valor;
        });
    }
    
    static mascaraValidade(input) {
        input.addEventListener('input', (e) => {
            let valor = e.target.value.replace(/\D/g, '');
            if (valor.length >= 2) {
                valor = valor.slice(0, 2) + '/' + valor.slice(2, 4);
            }
            if (valor.length > 5) valor = valor.slice(0, 5);
            e.target.value = valor;
        });
    }
    
    static validarFormulario(formId) {
        const form = document.getElementById(formId);
        if (!form) return true;
        
        let valido = true;
        const inputs = form.querySelectorAll('[required]');
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                input.classList.add('erro');
                valido = false;
            } else {
                input.classList.remove('erro');
            }
        });
        
        return valido;
    }
}

// Datas
class GerenciadorDatas {
    static configurarMinimoData(input) {
        const hoje = new Date().toISOString().split('T')[0];
        input.min = hoje;
    }
    
    static configurarDataFim(dataInicio, dataFim) {
        if (dataInicio.value) {
            dataFim.min = dataInicio.value;
        }
        
        dataInicio.addEventListener('change', () => {
            dataFim.min = dataInicio.value;
            if (dataFim.value && dataFim.value < dataInicio.value) {
                dataFim.value = dataInicio.value;
            }
        });
    }
}

// Inicialização Global
document.addEventListener('DOMContentLoaded', () => {
    // Configurar inputs de data
    const inputsData = document.querySelectorAll('input[type="date"]');
    inputsData.forEach(input => {
        GerenciadorDatas.configurarMinimoData(input);
    });
    
    // Configurar máscaras de telefone
    const inputsTelefone = document.querySelectorAll('input[type="tel"]');
    inputsTelefone.forEach(input => {
        FormularioDinamico.mascaraTelefone(input);
    });
    
    // Configurar máscaras de cartão
    const inputsCartao = document.querySelectorAll('.cartao-numero');
    inputsCartao.forEach(input => {
        FormularioDinamico.mascaraNumeroCartao(input);
    });
    
    // Configurar máscaras de validade
    const inputsValidade = document.querySelectorAll('.cartao-validade');
    inputsValidade.forEach(input => {
        FormularioDinamico.mascaraValidade(input);
    });
});

// Exportar para uso global
window.Utilitarios = Utilitarios;
window.API = API;
window.modal = modal;
window.FormularioDinamico = FormularioDinamico;
window.GerenciadorDatas = GerenciadorDatas;