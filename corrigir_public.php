<?php
$arquivo = __DIR__ . '/public/index.php';
if(file_exists($arquivo)) {
    $conteudo = file_get_contents($arquivo);
    
    // Adicionar o mapeamento de imagens
    $busca = '<div class="grade-veiculos" id="gradeVeiculos">';
    $replacement = '<div class="grade-veiculos" id="gradeVeiculos">
    <?php 
    $imagens_map = [
        \'500\' => \'fiat500.jpg\',
        \'Focus\' => \'focus.jpg\',
        \'Golf\' => \'golf.jpg\',
        \'Corolla\' => \'corolla.jpg\',
        \'Civic\' => \'civic.jpg\',
        \'X5\' => \'bmw_x5.jpg\',
        \'Tucson\' => \'tucson.jpg\',
        \'Classe C\' => \'mercedes.jpg\',
        \'Model 3\' => \'Pink Tesla.jpg\',
        \'Sprinter\' => \'sprinter.jpg\'
    ];
    ?>';
    
    $conteudo = str_replace($busca, $replacement, $conteudo);
    
    // Corrigir caminho da imagem
    $conteudo = str_replace(
        'src="/uploads/viaturas/<?= $viatura[\'imagem\'] ?: \'padrao.jpg\' ?>',
        'src="../uploads/veiculos/<?= $imagens_map[$viatura[\'modelo\']] ?? $viatura[\'imagem\'] ?? \'placeholder.jpg\' ?>"',
        $conteudo
    );
    
    file_put_contents($arquivo, $conteudo);
    echo " Arquivo public/index.php corrigido!";
} else {
    echo " Arquivo não encontrado!";
}
?>