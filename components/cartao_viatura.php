<?php
// components/cartao_viatura.php
// Componente reutilizável para exibir cartão de viatura

function exibirCartaoViatura($viatura, $mostrarBotao = true, $linkDetalhes = true) {
    ?>
    <div class="cartao-veiculo">
        <?php if($viatura['status'] !== 'disponivel'): ?>
            <div class="veiculo-badge status-<?= $viatura['status'] ?>">
                <?= ucfirst($viatura['status']) ?>
            </div>
        <?php endif; ?>
        
        <img src="../uploads/veiculos/<?= $viatura['imagem'] ?: 'placeholder.jpg' ?>" 
             alt="<?= htmlspecialchars($viatura['marca'] . ' ' . $viatura['modelo']) ?>" 
             class="imagem-veiculo"
             onerror="this.src='../assets/imagens/placeholder.jpg'">
        
        <div class="info-veiculo">
            <?php if($linkDetalhes): ?>
                <a href="../cliente/detalhe_viatura.php?id=<?= $viatura['id'] ?>" class="titulo-veiculo">
                    <?= htmlspecialchars($viatura['marca'] . ' ' . $viatura['modelo']) ?>
                </a>
            <?php else: ?>
                <h3 class="titulo-veiculo">
                    <?= htmlspecialchars($viatura['marca'] . ' ' . $viatura['modelo']) ?>
                </h3>
            <?php endif; ?>
            
            <div class="veiculo-caracteristicas">
                <span> <?= $viatura['ano'] ?></span>
                <span> <?= ucfirst($viatura['tipo']) ?></span>
                <span> <?= $viatura['lugares'] ?> lugares</span>
                <span> <?= ucfirst($viatura['transmissao']) ?></span>
                <span> <?= ucfirst($viatura['combustivel']) ?></span>
            </div>
            
            <div class="preco-veiculo">
                € <?= number_format($viatura['preco_dia'], 2) ?>
                <small>/dia</small>
            </div>
            
            <?php if($mostrarBotao && $viatura['status'] === 'disponivel'): ?>
                <button class="btn btn-primario btn-reservar" 
                        onclick="window.location.href='../cliente/detalhe_viatura.php?id=<?= $viatura['id'] ?>'"
                        style="width: 100%; margin-top: 0.75rem;">
                   Reservar Agora
                </button>
            <?php elseif($mostrarBotao && $viatura['status'] !== 'disponivel'): ?>
                <button class="btn btn-secundario" disabled style="width: 100%; margin-top: 0.75rem;">
                     Indisponível
                </button>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
        .veiculo-caracteristicas {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 0.5rem 0;
            font-size: 0.8rem;
            color: var(--cinza-escuro);
        }
        
        .veiculo-caracteristicas span {
            background: var(--cinza-claro);
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
        }
        
        .veiculo-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: bold;
            z-index: 1;
        }
        
        .status-alugado {
            background: var(--perigo);
            color: white;
        }
        
        .status-manutencao {
            background: var(--aviso);
            color: #333;
        }
        
        .cartao-veiculo {
            position: relative;
        }
    </style>
    <?php
}
?>