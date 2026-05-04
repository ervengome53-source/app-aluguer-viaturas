<?php
// components/tabela.php
// Componente de tabela reutilizável com ordenação e paginação

function exibirTabela($colunas, $dados, $id = 'tabelaDinamica', $acoes = true, $paginaAtual = 1, $totalPaginas = 1) {
    ?>
    <div class="tabela-container" id="container-<?= $id ?>">
        <?php if(count($dados) > 0): ?>
            <div class="tabela-header-tools">
                <div class="tabela-info">
                    Mostrando <span id="tabela-inicio">1</span> a <span id="tabela-fim"><?= count($dados) ?></span> de <span id="tabela-total"><?= count($dados) ?></span> registos
                </div>
                <div class="tabela-busca">
                    <input type="text" id="busca-<?= $id ?>" class="controlo-formulario" placeholder=" Buscar..." style="width: 200px;">
                </div>
            </div>
            
            <div class="container-tabela">
                <table class="tabela" id="<?= $id ?>">
                    <thead>
                        <tr>
                            <?php foreach($colunas as $coluna): ?>
                                <th data-coluna="<?= $coluna['campo'] ?>" class="coluna-ordenavel">
                                    <?= $coluna['titulo'] ?>
                                    <span class="ordenacao-icon">↕️</span>
                                </th>
                            <?php endforeach; ?>
                            <?php if($acoes): ?>
                                <th>Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($dados as $index => $linha): ?>
                            <tr data-id="<?= $linha['id'] ?? $index ?>">
                                <?php foreach($colunas as $coluna): ?>
                                    <td data-label="<?= $coluna['titulo'] ?>">
                                        <?= $coluna['formato'] ? 
                                            call_user_func($coluna['formato'], $linha[$coluna['campo']] ?? '') : 
                                            htmlspecialchars($linha[$coluna['campo']] ?? '-') ?>
                                    </td>
                                <?php endforeach; ?>
                                <?php if($acoes): ?>
                                    <td class="tabela-acoes">
                                        <button class="btn btn-info btn-sm btn-editar" data-id="<?= $linha['id'] ?? $index ?>">
                                            
                                        </button>
                                        <button class="btn btn-perigo btn-sm btn-excluir" data-id="<?= $linha['id'] ?? $index ?>">
                                            ️
                                        </button>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if($totalPaginas > 1): ?>
                <div class="tabela-paginacao">
                    <button class="btn-pagina btn-pagina-primeiro" <?= $paginaAtual <= 1 ? 'disabled' : '' ?>>
                        « Primeiro
                    </button>
                    <button class="btn-pagina btn-pagina-anterior" <?= $paginaAtual <= 1 ? 'disabled' : '' ?>>
                        ‹ Anterior
                    </button>
                    <span class="pagina-atual">Página <?= $paginaAtual ?> de <?= $totalPaginas ?></span>
                    <button class="btn-pagina btn-pagina-proximo" <?= $paginaAtual >= $totalPaginas ? 'disabled' : '' ?>>
                        Próximo ›
                    </button>
                    <button class="btn-pagina btn-pagina-ultimo" <?= $paginaAtual >= $totalPaginas ? 'disabled' : '' ?>>
                        Último »
                    </button>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="tabela-vazia">
                <div class="texto-centro" style="padding: 3rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;"></div>
                    <h3>Nenhum registo encontrado</h3>
                    <p>Não existem dados para exibir na tabela.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <style>
        .tabela-container {
            background: var(--branco);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .tabela-header-tools {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: var(--cinza-claro);
            border-bottom: 1px solid var(--cinza);
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .tabela-info {
            font-size: 0.85rem;
            color: var(--cinza-escuro);
        }
        
        .coluna-ordenavel {
            cursor: pointer;
            user-select: none;
        }
        
        .coluna-ordenavel:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .ordenacao-icon {
            opacity: 0.5;
            margin-left: 0.25rem;
        }
        
        .tabela-paginacao {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            background: var(--cinza-claro);
            border-top: 1px solid var(--cinza);
            flex-wrap: wrap;
        }
        
        .btn-pagina {
            padding: 0.4rem 0.8rem;
            border: 1px solid var(--cinza);
            background: var(--branco);
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transicao);
        }
        
        .btn-pagina:hover:not(:disabled) {
            background: var(--laranja);
            color: var(--branco);
            border-color: var(--laranja);
        }
        
        .btn-pagina:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagina-atual {
            padding: 0.4rem 1rem;
            background: var(--azul-escuro);
            color: var(--branco);
            border-radius: 6px;
        }
        
        .tabela-vazia {
            background: var(--branco);
            border-radius: 12px;
        }
        
        @media (max-width: 768px) {
            .tabela thead {
                display: none;
            }
            
            .tabela tr {
                display: block;
                margin-bottom: 1rem;
                border: 1px solid var(--cinza);
                border-radius: 8px;
            }
            
            .tabela td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.75rem;
                border-bottom: 1px solid var(--cinza);
            }
            
            .tabela td:last-child {
                border-bottom: none;
            }
            
            .tabela td::before {
                content: attr(data-label);
                font-weight: bold;
                color: var(--azul-escuro);
            }
            
            .tabela-acoes {
                justify-content: flex-end;
            }
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabela = document.getElementById('<?= $id ?>');
            if (!tabela) return;
            
            // Busca na tabela
            const buscaInput = document.getElementById('busca-<?= $id ?>');
            if (buscaInput) {
                buscaInput.addEventListener('keyup', () => {
                    const termo = buscaInput.value.toLowerCase();
                    const linhas = tabela.querySelectorAll('tbody tr');
                    
                    linhas.forEach(linha => {
                        const texto = linha.innerText.toLowerCase();
                        linha.style.display = texto.includes(termo) ? '' : 'none';
                    });
                });
            }
            
            // Ordenação
            const colunas = tabela.querySelectorAll('.coluna-ordenavel');
            let ordemAtual = { coluna: null, direcao: 'asc' };
            
            colunas.forEach((coluna, index) => {
                coluna.addEventListener('click', () => {
                    const tbody = tabela.querySelector('tbody');
                    const linhas = Array.from(tbody.querySelectorAll('tr'));
                    const campo = coluna.dataset.coluna;
                    
                    if (ordemAtual.coluna === index) {
                        ordemAtual.direcao = ordemAtual.direcao === 'asc' ? 'desc' : 'asc';
                    } else {
                        ordemAtual.coluna = index;
                        ordemAtual.direcao = 'asc';
                    }
                    
                    linhas.sort((a, b) => {
                        const aValor = a.cells[index].innerText;
                        const bValor = b.cells[index].innerText;
                        
                        if (ordemAtual.direcao === 'asc') {
                            return aValor.localeCompare(bValor);
                        } else {
                            return bValor.localeCompare(aValor);
                        }
                    });
                    
                    linhas.forEach(linha => tbody.appendChild(linha));
                    
                    // Atualizar ícones de ordenação
                    colunas.forEach(c => {
                        c.querySelector('.ordenacao-icon').innerHTML = '↕️';
                    });
                    coluna.querySelector('.ordenacao-icon').innerHTML = ordemAtual.direcao === 'asc' ? '↑' : '↓';
                });
            });
        });
    </script>
    <?php
}
?>