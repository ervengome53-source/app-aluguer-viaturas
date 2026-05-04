<?php
// components/cabecalho.php
$nome_utilizador = $_SESSION['utilizador_nome'] ?? 'Utilizador';
$cargo_utilizador = $_SESSION['utilizador_cargo'] ?? '';
$inicial_utilizador = strtoupper(substr($nome_utilizador, 0, 1));
$pasta_projeto = '/SISTEMA_ALUGUER_ViATURAS';
?>

<div class="barra-superior">
    <button class="menu-toggle" id="menuToggle">☰</button>
    
    <div class="info-utilizador">
        <span>Bem-vindo, <?= htmlspecialchars($nome_utilizador) ?></span>
        <div class="avatar-utilizador"><?= $inicial_utilizador ?></div>
        <a href="<?= $pasta_projeto ?>../public/logout.php" class="btn btn-perigo">Sair</a>
    </div>
</div>

<script>
document.getElementById('menuToggle')?.addEventListener('click', function() {
    document.querySelector('.barra-lateral').classList.toggle('ativo');
});
</script>