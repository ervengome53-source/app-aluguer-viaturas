<?php
// components/filtros.php
// Componente de filtros reutilizável

function exibirFiltros($filtrosAtuais = [], $action = '', $method = 'GET') {
    $tipos = ['carro', 'moto', 'van', 'luxo', 'economico', 'suv'];
    $combustiveis = ['gasolina', 'diesel', 'eletrico', 'hibrido'];
    $transmissoes = ['manual', 'automatico'];
    ?>
    
    <div class="barra-filtros">
        <form action="<?= $action ?>" method="<?= $method ?>" class="form-filtros" style="display: contents;">
            <div class="grupo-filtro">
                <label class="rotulo-formulario"> Pesquisar</label>
                <input type="text" 
                       name="busca" 
                       class="controlo-formulario" 
                       placeholder="Modelo, marca..." 
                       value="<?= htmlspecialchars($filtrosAtuais['busca'] ?? '') ?>">
            </div>
            
            <div class="grupo-filtro">
                <label class="rotulo-formulario"> Tipo</label>
                <select name="tipo" class="controlo-formulario">
                    <option value="">Todos</option>
                    <?php foreach($tipos as $tipo): ?>
                        <option value="<?= $tipo ?>" <?= (($filtrosAtuais['tipo'] ?? '') == $tipo) ? 'selected' : '' ?>>
                            <?= ucfirst($tipo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="grupo-filtro">
                <label class="rotulo-formulario"> Combustível</label>
                <select name="combustivel" class="controlo-formulario">
                    <option value="">Todos</option>
                    <?php foreach($combustiveis as $combustivel): ?>
                        <option value="<?= $combustivel ?>" <?= (($filtrosAtuais['combustivel'] ?? '') == $combustivel) ? 'selected' : '' ?>>
                            <?= ucfirst($combustivel) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="grupo-filtro">
                <label class="rotulo-formulario"> Transmissão</label>
                <select name="transmissao" class="controlo-formulario">
                    <option value="">Todas</option>
                    <?php foreach($transmissoes as $transmissao): ?>
                        <option value="<?= $transmissao ?>" <?= (($filtrosAtuais['transmissao'] ?? '') == $transmissao) ? 'selected' : '' ?>>
                            <?= ucfirst($transmissao) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="grupo-filtro">
                <label class="rotulo-formulario"> Preço Mínimo</label>
                <input type="number" 
                       name="preco_min" 
                       class="controlo-formulario" 
                       placeholder="MZN" 
                       step="10"
                       value="<?= htmlspecialchars($filtrosAtuais['preco_min'] ?? '') ?>">
            </div>
            
            <div class="grupo-filtro">
                <label class="rotulo-formulario"> Preço Máximo</label>
                <input type="number" 
                       name="preco_max" 
                       class="controlo-formulario" 
                       placeholder="MZN" 
                       step="10"
                       value="<?= htmlspecialchars($filtrosAtuais['preco_max'] ?? '') ?>">
            </div>
            
            <div class="grupo-filtro">
                <label class="rotulo-formulario">&nbsp;</label>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primario">
                         Filtrar
                    </button>
                    <button type="button" class="btn btn-secundario" onclick="limparFiltros()">
                        ️ Limpar
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <script>
        function limparFiltros() {
            const form = document.querySelector('.form-filtros');
            if (form) {
                form.querySelectorAll('input, select').forEach(input => {
                    if (input.type !== 'submit' && input.type !== 'button') {
                        input.value = '';
                    }
                });
                form.submit();
            }
        }
    </script>
    
    <style>
        .form-filtros {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            width: 100%;
        }
        
        @media (max-width: 768px) {
            .form-filtros {
                flex-direction: column;
            }
            
            .grupo-filtro {
                width: 100%;
            }
        }
    </style>
    <?php
}
?>