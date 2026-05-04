<?php
// components/barra_lateral.php
$cargo_utilizador = $_SESSION['utilizador_cargo'] ?? '';
$pagina_atual = basename($_SERVER['PHP_SELF']);
$pasta_projeto = '/SISTEMA_ALUGUER_ViATURAS';
?>

<div class="barra-lateral">
    <div class="barra-lateral-cabecalho">
	 <div style="text-align: cente; margin-bottom: 20px;">
            <img src="../assets/imagens/66.jpg" alt="SIGAV Logo" style="max-width: 100px; height: auto; margin-bottom: 10px;">
        </div>
    </div>
    <nav class="barra-lateral-nav">
        <?php if($cargo_utilizador == 'admin'): ?>
            <a href="<?= $pasta_projeto ?>../admin/dashboard.php" class="barra-lateral-item <?= $pagina_atual == 'dashboard.php' ? 'ativo' : '' ?>">
                 Painel
            </a>
            <a href="<?= $pasta_projeto ?>../admin/viaturas.php" class="barra-lateral-item <?= $pagina_atual == 'viaturas.php' ? 'ativo' : '' ?>">
                 Viaturas
            </a>
            <a href="<?= $pasta_projeto ?>../admin/usuarios.php" class="barra-lateral-item <?= $pagina_atual == 'usuarios.php' ? 'ativo' : '' ?>">
                 Utilizadores
            </a>
            <a href="<?= $pasta_projeto ?>../admin/relatorios.php" class="barra-lateral-item <?= $pagina_atual == 'relatorios.php' ? 'ativo' : '' ?>">
                 Relatórios
            </a>
            <a href="<?= $pasta_projeto ?>../admin/configuracoes.php" class="barra-lateral-item <?= $pagina_atual == 'configuracoes.php' ? 'ativo' : '' ?>">
                ️ Configurações
            </a>
        <?php elseif($cargo_utilizador == 'funcionario'): ?>
            <a href="<?= $pasta_projeto ?>../funcionario/dashboard.php" class="barra-lateral-item <?= $pagina_atual == 'dashboard.php' ? 'ativo' : '' ?>">
                 Painel
            </a>
            <a href="<?= $pasta_projeto ?>../funcionario/reservas.php" class="barra-lateral-item <?= $pagina_atual == 'reservas.php' ? 'ativo' : '' ?>">
                 Reservas
            </a>
            <a href="<?= $pasta_projeto ?>../funcionario/aluguer.php" class="barra-lateral-item <?= $pagina_atual == 'aluguer.php' ? 'ativo' : '' ?>">
                 Aluguer
            </a>
            <a href="<?= $pasta_projeto ?>../funcionario/devolucao.php" class="barra-lateral-item <?= $pagina_atual == 'devolucao.php' ? 'ativo' : '' ?>">
                 Devolução
            </a>
            <a href="<?= $pasta_projeto ?>../funcionario/clientes.php" class="barra-lateral-item <?= $pagina_atual == 'clientes.php' ? 'ativo' : '' ?>">
                 Clientes
            </a>
        <?php elseif($cargo_utilizador == 'cliente'): ?>
            <a href="<?= $pasta_projeto ?>../cliente/dashboard.php" class="barra-lateral-item <?= $pagina_atual == 'dashboard.php' ? 'ativo' : '' ?>">
                 Painel
            </a>
            <a href="<?= $pasta_projeto ?>../cliente/viaturas.php" class="barra-lateral-item <?= $pagina_atual == 'viaturas.php' ? 'ativo' : '' ?>">
                 Catálogo
            </a>
            <a href="<?= $pasta_projeto ?>../cliente/reservas.php" class="barra-lateral-item <?= $pagina_atual == 'reservas.php' ? 'ativo' : '' ?>">
                 Minhas Reservas
            </a>
            <a href="<?= $pasta_projeto ?>../cliente/historico.php" class="barra-lateral-item <?= $pagina_atual == 'historico.php' ? 'ativo' : '' ?>">
                 Histórico
            </a>
            <a href="<?= $pasta_projeto ?>../cliente/perfil.php" class="barra-lateral-item <?= $pagina_atual == 'perfil.php' ? 'ativo' : '' ?>">
                 Perfil
            </a>
        <?php endif; ?>
    </nav>
</div>