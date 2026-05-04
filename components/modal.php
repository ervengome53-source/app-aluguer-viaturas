<?php
// components/modal.php
// Componente de janela modal reutilizável

function exibirModal($id = 'modalGlobal', $titulo = 'Aviso', $tamanho = 'md') {
    $tamanhos = [
        'sm' => '400px',
        'md' => '500px',
        'lg' => '700px',
        'xl' => '900px'
    ];
    $largura = $tamanhos[$tamanho] ?? '500px';
    ?>
    
    <div id="<?= $id ?>" class="modal" style="display: none;">
        <div class="modal-conteudo" style="max-width: <?= $largura ?>;">
            <div class="modal-cabecalho">
                <h3 class="modal-titulo"><?= htmlspecialchars($titulo) ?></h3>
                <button class="modal-fechar" onclick="fecharModal('<?= $id ?>')">&times;</button>
            </div>
            <div class="modal-corpo" id="modal-corpo-<?= $id ?>">
                <!-- Conteúdo dinâmico -->
            </div>
            <div class="modal-rodape">
                <button class="btn btn-secundario" onclick="fecharModal('<?= $id ?>')">Cancelar</button>
                <button class="btn btn-primario" id="modal-confirmar-<?= $id ?>">Confirmar</button>
            </div>
        </div>
    </div>
    
    <style>
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }
        
        .modal-conteudo {
            background: var(--branco);
            border-radius: 16px;
            width: 90%;
            max-height: 90vh;
            overflow: hidden;
            animation: modalEntrar 0.3s ease;
        }
        
        @keyframes modalEntrar {
            from {
                transform: scale(0.9);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .modal-cabecalho {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem;
            border-bottom: 1px solid var(--cinza);
            background: var(--cinza-claro);
        }
        
        .modal-titulo {
            margin: 0;
            color: var(--azul-escuro);
            font-size: 1.2rem;
        }
        
        .modal-fechar {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--cinza-escuro);
            transition: var(--transicao);
        }
        
        .modal-fechar:hover {
            color: var(--perigo);
        }
        
        .modal-corpo {
            padding: 1.5rem;
            max-height: 60vh;
            overflow-y: auto;
        }
        
        .modal-rodape {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--cinza);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            background: var(--cinza-claro);
        }
    </style>
    
    <script>
        function abrirModal(id, conteudo, onConfirmar = null) {
            const modal = document.getElementById(id);
            const corpo = document.getElementById(`modal-corpo-${id}`);
            const btnConfirmar = document.getElementById(`modal-confirmar-${id}`);
            
            if (!modal || !corpo) return;
            
            corpo.innerHTML = typeof conteudo === 'string' ? conteudo : '';
            modal.style.display = 'flex';
            
            if (onConfirmar) {
                btnConfirmar.onclick = () => {
                    onConfirmar();
                    fecharModal(id);
                };
                btnConfirmar.style.display = 'block';
            } else {
                btnConfirmar.style.display = 'none';
            }
        }
        
        function fecharModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.style.display = 'none';
            }
        }
        
        function confirmarModal(mensagem, onConfirmar) {
            const modalId = 'modalConfirmacao';
            let modal = document.getElementById(modalId);
            
            if (!modal) {
                const modalHtml = `
                    <div id="${modalId}" class="modal" style="display: none;">
                        <div class="modal-conteudo" style="max-width: 400px;">
                            <div class="modal-cabecalho">
                                <h3 class="modal-titulo">Confirmar Ação</h3>
                                <button class="modal-fechar" onclick="fecharModal('${modalId}')">&times;</button>
                            </div>
                            <div class="modal-corpo" id="modal-corpo-${modalId}"></div>
                            <div class="modal-rodape">
                                <button class="btn btn-secundario" onclick="fecharModal('${modalId}')">Cancelar</button>
                                <button class="btn btn-perigo" id="modal-confirmar-${modalId}">Confirmar</button>
                            </div>
                        </div>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                modal = document.getElementById(modalId);
            }
            
            const corpo = document.getElementById(`modal-corpo-${modalId}`);
            const btnConfirmar = document.getElementById(`modal-confirmar-${modalId}`);
            
            corpo.innerHTML = `<p>${mensagem}</p>`;
            modal.style.display = 'flex';
            
            btnConfirmar.onclick = () => {
                if (onConfirmar) onConfirmar();
                fecharModal(modalId);
            };
        }
        
        // Fechar modal ao clicar fora
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        });
        
        // Fechar modal com ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.style.display = 'none';
                });
            }
        });
    </script>
    <?php
}
?>