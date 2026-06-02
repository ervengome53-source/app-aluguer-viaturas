<?php
// components/barra_lateral.php
$cargo_utilizador = $_SESSION['utilizador_cargo'] ?? '';
$pagina_atual = basename($_SERVER['PHP_SELF']);
$nome_utilizador = $_SESSION['utilizador_nome'] ?? 'Utilizador';
$inicial_utilizador = strtoupper(substr($nome_utilizador, 0, 1));
?>
<!--------------------- BARRA LATERAL PADRÃO --------------------->
<div class="barra-lateral" style="width: 270px; background: #1E3A5F; height: 100vh; position: fixed; left: 0; top: 0; display: flex; flex-direction: column; justify-content: space-between; overflow-y: auto; z-index: 1000; box-shadow: 2px 0 10px rgba(0,0,0,0.1);">
    
    <!-- CABEÇALHO -->
    <div class="barra-lateral-cabecalho" style="padding: 1.5rem; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2);">
        <h3 style="color: #FF8C00; margin: 0; font-size: 1.3rem;">SIGAV</h3>
        <small style="color: rgba(255,255,255,0.7); font-size: 0.7rem;">Sistema de Gestão</small>
    </div>
    
    <!-- MENU PRINCIPAL -->
    <nav class="barra-lateral-nav" style="flex: 1; padding: 0.5rem 0;">
        <?php if($cargo_utilizador == 'admin'): ?>
            <a href="/SISTEMA_ALUGUER_ViATURAS/admin/dashboard.php" class="barra-lateral-item" style="display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #ecf0f1; text-decoration: none; border-radius: 8px; margin: 4px 12px; transition: all 0.3s ease;">
                <i class="fas fa-tachometer-alt" style="width: 22px; font-size: 1.1rem;"></i> Dashboard
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/admin/viaturas.php" class="barra-lateral-item" style="display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #ecf0f1; text-decoration: none; border-radius: 8px; margin: 4px 12px; transition: all 0.3s ease;">
                <i class="fas fa-car" style="width: 22px; font-size: 1.1rem;"></i> Viaturas
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/admin/usuarios.php" class="barra-lateral-item" style="display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #ecf0f1; text-decoration: none; border-radius: 8px; margin: 4px 12px; transition: all 0.3s ease;">
                <i class="fas fa-users" style="width: 22px; font-size: 1.1rem;"></i> Utilizadores
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/admin/relatorios.php" class="barra-lateral-item" style="display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #ecf0f1; text-decoration: none; border-radius: 8px; margin: 4px 12px; transition: all 0.3s ease;">
                <i class="fas fa-chart-line" style="width: 22px; font-size: 1.1rem;"></i> Relatórios
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/admin/relatorio_pagamentos.php" class="barra-lateral-item" style="display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #ecf0f1; text-decoration: none; border-radius: 8px; margin: 4px 12px; transition: all 0.3s ease;">
                <i class="fas fa-money-bill-wave" style="width: 22px; font-size: 1.1rem;"></i> Pagamentos
            </a>
        
        <?php elseif($cargo_utilizador == 'funcionario'): ?>
            <a href="/SISTEMA_ALUGUER_ViATURAS/funcionario/dashboard.php" class="barra-lateral-item" style="display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #ecf0f1; text-decoration: none; border-radius: 8px; margin: 4px 12px; transition: all 0.3s ease;">
                <i class="fas fa-tachometer-alt" style="width: 22px; font-size: 1.1rem;"></i> Dashboard
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/funcionario/reservas.php" class="barra-lateral-item" style="display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #ecf0f1; text-decoration: none; border-radius: 8px; margin: 4px 12px; transition: all 0.3s ease;">
                <i class="fas fa-calendar-check" style="width: 22px; font-size: 1.1rem;"></i> Reservas
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/funcionario/aluguer.php" class="barra-lateral-item" style="display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #ecf0f1; text-decoration: none; border-radius: 8px; margin: 4px 12px; transition: all 0.3s ease;">
                <i class="fas fa-key" style="width: 22px; font-size: 1.1rem;"></i> Aluguer
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/funcionario/devolucao.php" class="barra-lateral-item" style="display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #ecf0f1; text-decoration: none; border-radius: 8px; margin: 4px 12px; transition: all 0.3s ease;">
                <i class="fas fa-undo-alt" style="width: 22px; font-size: 1.1rem;"></i> Devolução
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/funcionario/clientes.php" class="barra-lateral-item" style="display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #ecf0f1; text-decoration: none; border-radius: 8px; margin: 4px 12px; transition: all 0.3s ease;">
                <i class="fas fa-user-friends" style="width: 22px; font-size: 1.1rem;"></i> Clientes
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/funcionario/pagamentos.php" class="barra-lateral-item" style="display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #ecf0f1; text-decoration: none; border-radius: 8px; margin: 4px 12px; transition: all 0.3s ease;">
                <i class="fas fa-credit-card" style="width: 22px; font-size: 1.1rem;"></i> Pagamentos
            </a>
        
        <?php elseif($cargo_utilizador == 'cliente'): ?>
            <a href="/SISTEMA_ALUGUER_ViATURAS/cliente/dashboard.php" class="barra-lateral-item" style="display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #ecf0f1; text-decoration: none; border-radius: 8px; margin: 4px 12px; transition: all 0.3s ease;">
                <i class="fas fa-tachometer-alt" style="width: 22px; font-size: 1.1rem;"></i> Painel
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/cliente/viaturas.php" class="barra-lateral-item" style="display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #ecf0f1; text-decoration: none; border-radius: 8px; margin: 4px 12px; transition: all 0.3s ease;">
                <i class="fas fa-car" style="width: 22px; font-size: 1.1rem;"></i> Catálogo
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/cliente/reservas.php" class="barra-lateral-item" style="display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #ecf0f1; text-decoration: none; border-radius: 8px; margin: 4px 12px; transition: all 0.3s ease;">
                <i class="fas fa-bookmark" style="width: 22px; font-size: 1.1rem;"></i> Minhas Reservas
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/cliente/historico.php" class="barra-lateral-item" style="display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #ecf0f1; text-decoration: none; border-radius: 8px; margin: 4px 12px; transition: all 0.3s ease;">
                <i class="fas fa-history" style="width: 22px; font-size: 1.1rem;"></i> Histórico
            </a>
            <a href="/SISTEMA_ALUGUER_ViATURAS/cliente/pagamentos.php" class="barra-lateral-item" style="display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #ecf0f1; text-decoration: none; border-radius: 8px; margin: 4px 12px; transition: all 0.3s ease;">
                <i class="fas fa-receipt" style="width: 22px; font-size: 1.1rem;"></i> Pagamentos
            </a>
        <?php endif; ?>
    </nav>
    
    <!-- CONFIGURAÇÕES (Admin e Funcionário apenas) -->
    <?php if($cargo_utilizador == 'admin' ): ?>
    <div class="barra-lateral-config" style="border-top: 1px solid rgba(255,255,255,0.1); border-bottom: 1px solid rgba(255,255,255,0.1); margin: 0.5rem 0; padding: 0.5rem 0;">
        <a href="/SISTEMA_ALUGUER_ViATURAS/admin/configuracoes.php" class="barra-lateral-item" style="display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #ecf0f1; text-decoration: none; border-radius: 8px; margin: 4px 12px; transition: all 0.3s ease;">
            <i class="fas fa-cog" style="width: 22px; font-size: 1.1rem;"></i> Configurações
        </a>
    </div>
    <?php endif; ?>
    
    <!-- ============================================ -->
    <!-- PERFIL (PARTE INFERIOR) - CLICÁVEL -->
    <!-- ============================================ -->
    <div class="barra-lateral-footer" style="padding: 1rem; border-top: 1px solid rgba(255,255,255,0.1); margin-top: auto;">
        <!-- Perfil do Utilizador (clicável) -->
        <a href="/SISTEMA_ALUGUER_ViATURAS/cliente/perfil.php" class="perfil-link" style="text-decoration: none; display: block; margin-bottom: 0.8rem;">
            <div style="display: flex; align-items: center; gap: 0.8rem; padding: 0.5rem; background: rgba(255,255,255,0.08); border-radius: 10px; transition: all 0.3s ease;">
                <div style="width: 40px; height: 40px; background: #FF8C00; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; color: white;"><?= $inicial_utilizador ?></div>
                <div style="flex: 1;">
                    <strong style="color: white; font-size: 0.9rem; display: block;"><?= htmlspecialchars($nome_utilizador) ?></strong>
                    <small style="color: rgba(255,255,255,0.7); font-size: 0.7rem;"><?= ucfirst($cargo_utilizador) ?></small>
                </div>
                <i class="fas fa-chevron-right" style="color: rgba(255,255,255,0.5); font-size: 0.8rem;"></i>
            </div>
        </a>
        
        <!-- Botão Sair -->
        <a href="/SISTEMA_ALUGUER_ViATURAS/public/logout.php" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; background: #dc3545; color: white; padding: 0.6rem; border-radius: 8px; text-decoration: none; font-size: 0.85rem; font-weight: 500; transition: all 0.3s ease; width: 100%;">
            <i class="fas fa-sign-out-alt" style="font-size: 1rem;"></i> Sair
        </a>
    </div>
</div>

<style>
/* ESTILOS GARANTIDOS PARA BARRA LATERAL */
.barra-lateral-item {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    padding: 12px 20px !important;
    color: #ecf0f1 !important;
    text-decoration: none !important;
    border-radius: 8px !important;
    margin: 4px 12px !important;
    transition: all 0.3s ease !important;
}

.barra-lateral-item i {
    width: 22px !important;
    font-size: 1.1rem !important;
    color: #ecf0f1 !important;
}

.barra-lateral-item:hover {
    background: rgba(255, 140, 0, 0.15) !important;
    color: #FF8C00 !important;
    transform: translateX(5px) !important;
}

.barra-lateral-item:hover i {
    color: #FF8C00 !important;
}

/* Item ativo - página atual */
.barra-lateral-item.ativo {
    background: linear-gradient(135deg, #FF8C00, #ff6a00) !important;
    color: white !important;
    box-shadow: 0 4px 15px rgba(255, 140, 0, 0.3) !important;
}

.barra-lateral-item.ativo i {
    color: white !important;
}

/* Efeito hover no perfil */
.perfil-link:hover div {
    background: rgba(255, 140, 0, 0.2) !important;
    transform: translateX(3px);
}

/* Scrollbar */
.barra-lateral::-webkit-scrollbar {
    width: 5px;
}

.barra-lateral::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.1);
}

.barra-lateral::-webkit-scrollbar-thumb {
    background: #FF8C00;
    border-radius: 5px;
}

/* Responsivo */
@media (max-width: 768px) {
    .barra-lateral {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    .barra-lateral.ativo {
        transform: translateX(0);
    }
}
</style>

<script>
// Detectar página atual e marcar item como ativo
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    const items = document.querySelectorAll('.barra-lateral-item');
    
    items.forEach(item => {
        const href = item.getAttribute('href');
        if(href && href.includes(currentPage)) {
            item.classList.add('ativo');
        }
    });
});
</script>