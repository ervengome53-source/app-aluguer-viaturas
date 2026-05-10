<?php
// components/cabecalho.php
$nome_utilizador = $_SESSION['utilizador_nome'] ?? 'Utilizador';
$cargo_utilizador = $_SESSION['utilizador_cargo'] ?? '';
?>

<div class="barra-superior">
    <button class="menu-toggle" id="menuToggle">☰</button>
    
    <div class="info-utilizador">
<span>Bem-vindo, <strong><?= htmlspecialchars($nome_utilizador) ?></strong></span>        
    </div>
</div>

<script>
document.getElementById('menuToggle')?.addEventListener('click', function() {
    document.querySelector('.barra-lateral').classList.toggle('ativo');
});
</script>