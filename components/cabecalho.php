<?php
// components/cabecalho.php
$nome_utilizador = $_SESSION['utilizador_nome'] ?? 'Utilizador';
$cargo_utilizador = $_SESSION['utilizador_cargo'] ?? '';
?>

<script>
document.getElementById('menuToggle')?.addEventListener('click', function() {
    document.querySelector('.barra-lateral').classList.toggle('ativo');
});
</script>