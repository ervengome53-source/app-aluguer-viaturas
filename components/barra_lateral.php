<?php
// components/barra_lateral.php
$cargo_utilizador = $_SESSION['utilizador_cargo'] ?? '';
$pagina_atual = basename($_SERVER['PHP_SELF']);
$nome_utilizador = $_SESSION['utilizador_nome'] ?? 'Utilizador';
$inicial_utilizador = strtoupper(substr($nome_utilizador, 0, 1));
?>

<div class="barra-lateral">
    <div class="barra-lateral-cabecalho">
        <div style="text-align: center; margin-bottom: 20px;">
            <h3 style="color: #FF8C00; margin: 0;">SIGAV</h3>
            <small>Sistema de Gestão</small>
        </div>
    </div>
    <nav class="barra-lateral-nav">
        <?php if($cargo_utilizador == 'admin'): ?>
            <a href="/SISTEMA_ALUGUER_ViATURAS/admin/dashboard.php" class="barra-lateral-item <?= $pagina_atual == 'dashboard.php' ? 'ativo' : '' ?>">
               Dashboard
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/admin/viaturas.php" class="barra-lateral-item <?= $pagina_atual == 'viaturas.php' ? 'ativo' : '' ?>">
               Viaturas
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/admin/usuarios.php" class="barra-lateral-item <?= $pagina_atual == 'usuarios.php' ? 'ativo' : '' ?>">
               Utilizadores
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/admin/relatorios.php" class="barra-lateral-item <?= $pagina_atual == 'relatorios.php' ? 'ativo' : '' ?>">
               Relatórios
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/admin/relatorio_pagamentos.php" class="barra-lateral-item <?= $pagina_atual == 'relatorio_pagamentos.php' ? 'ativo' : '' ?>">
               Pagamentos
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/admin/configuracoes.php" class="barra-lateral-item <?= $pagina_atual == 'configuracoes.php' ? 'ativo' : '' ?>">
               Configurações
            </a>
        <?php elseif($cargo_utilizador == 'funcionario'): ?>
            <a href="/SISTEMA_ALUGUER_ViATURAS/funcionario/dashboard.php" class="barra-lateral-item <?= $pagina_atual == 'dashboard.php' ? 'ativo' : '' ?>">
               Dashboard
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/funcionario/reservas.php" class="barra-lateral-item <?= $pagina_atual == 'reservas.php' ? 'ativo' : '' ?>">
               Reservas
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/funcionario/aluguer.php" class="barra-lateral-item <?= $pagina_atual == 'aluguer.php' ? 'ativo' : '' ?>">
               Aluguer
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/funcionario/devolucao.php" class="barra-lateral-item <?= $pagina_atual == 'devolucao.php' ? 'ativo' : '' ?>">
               Devolução
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/funcionario/clientes.php" class="barra-lateral-item <?= $pagina_atual == 'clientes.php' ? 'ativo' : '' ?>">
               Clientes
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/funcionario/pagamentos.php" class="barra-lateral-item <?= $pagina_atual == 'pagamentos.php' ? 'ativo' : '' ?>">
               Pagamentos
            </a>
        <?php elseif($cargo_utilizador == 'cliente'): ?>
            <a href="/SISTEMA_ALUGUER_ViATURAS/cliente/dashboard.php" class="barra-lateral-item <?= $pagina_atual == 'dashboard.php' ? 'ativo' : '' ?>">
               Painel
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/cliente/viaturas.php" class="barra-lateral-item <?= $pagina_atual == 'viaturas.php' ? 'ativo' : '' ?>">
               Catálogo
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/cliente/reservas.php" class="barra-lateral-item <?= $pagina_atual == 'reservas.php' ? 'ativo' : '' ?>">
               Minhas Reservas
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/cliente/historico.php" class="barra-lateral-item <?= $pagina_atual == 'historico.php' ? 'ativo' : '' ?>">
               Histórico
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/cliente/perfil.php" class="barra-lateral-item <?= $pagina_atual == 'perfil.php' ? 'ativo' : '' ?>">
               Perfil
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/cliente/pagamentos.php" class="barra-lateral-item <?= $pagina_atual == 'pagamentos.php' ? 'ativo' : '' ?>">
               Pagamentos
            </a>
        <?php endif; ?>
    </nav>
    
    <!-- Botão Sair no final da barra lateral -->
    <div class="barra-lateral-footer">
        <hr style="margin: 1rem 0; border-color: rgba(255,255,255,0.1);">
        <div class="info-usuario-footer">
            <div class="avatar-footer"><?= $inicial_utilizador ?></div>
            <div class="usuario-info-footer">
                <strong><?= htmlspecialchars($nome_utilizador) ?></strong><br>
                <small><?= ucfirst($cargo_utilizador) ?></small>
            </div>
        </div>
        <a href="/SISTEMA_ALUGUER_ViATURAS/public/logout.php" class="btn-sair-footer">
             Sair
        </a>
    </div>
</div>

<style>
/* Estilos para o footer da barra lateral */
.barra-lateral {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100vh;
}

.barra-lateral-footer {
    padding: 1rem;
    margin-top: auto;
    border-top: 1px solid rgba(255,255,255,0.1);
}

.info-usuario-footer {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    padding: 0.5rem;
    margin-bottom: 0.8rem;
    background: rgba(255,255,255,0.05);
    border-radius: 10px;
}

.avatar-footer {
    width: 40px;
    height: 40px;
    background: #FF8C00;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
    color: white;
}

.usuario-info-footer {
    flex: 1;
}

.usuario-info-footer strong {
    color: white;
    font-size: 0.9rem;
}

.usuario-info-footer small {
    color: rgba(255,255,255,0.7);
    font-size: 0.7rem;
}

.btn-sair-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    background: #dc3545;
    color: white;
    padding: 0.6rem;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.3s ease;
    width: 100%;
}

.btn-sair-footer:hover {
    background: #c82333;
    transform: translateY(-2px);
}
</style>