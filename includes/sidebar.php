<?php
if (!defined('ABSPATH'))
    exit;

// Helper to check active state (simple version)
$current_page = $_GET['view'] ?? 'dashboard';

// Get current user details for footer
$current_user = wp_get_current_user();
$user_name = $current_user->display_name ?: 'Usuário';
$user_role = !empty($current_user->roles) ? translate_user_role($current_user->roles[0]) : 'Cliente';

// User Initials for Avatar
$words = explode(' ', $user_name);
$initials = '';
if (count($words) >= 2) {
    $initials = mb_substr($words[0], 0, 1) . mb_substr($words[count($words)-1], 0, 1);
} else {
    $initials = mb_substr($user_name, 0, 2);
}
$initials = strtoupper($initials);
?>
<!-- SIDEBAR -->
<aside class="area-sidebar is-collapsed">
    
    <!-- HEADER LOGO -->
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <a href="<?php echo serc_get_dashboard_url(['view' => 'dashboard']); ?>" class="logo-link" style="display:flex; align-items:center;">
                <img src="<?php echo plugin_dir_url(__FILE__); ?>../assets/img/LOGO_branca.svg" alt="Selo Brasil" class="logo-full" style="max-height: 28px; width: auto;" />
                <img src="<?php echo plugin_dir_url(__FILE__); ?>../assets/img/selobrasil_icon.svg" alt="Selo Brasil Icon" class="logo-collapsed" style="max-height: 28px; width: auto; display: none;" />
            </a>
        </div>
        <button type="button" class="sidebar-close-btn" aria-label="Colapsar Menu">
            <i data-lucide="x"></i>
        </button>
    </div>

    <!-- NAVIGATION -->
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="<?php echo serc_get_dashboard_url(['view' => 'dashboard']); ?>"
                class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                <div class="nav-icon"><i data-lucide="layout-dashboard"></i></div>
                <div class="nav-texts">
                    <span class="nav-text">Dashboard</span>
                    <span class="nav-desc">Visão geral do sistema</span>
                </div>
                <div class="nav-indicator"></div>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo serc_get_dashboard_url(['view' => 'category']); ?>"
                class="nav-link <?php echo $current_page === 'category' || $current_page === 'query' || $current_page === 'consulta' ? 'active' : ''; ?>">
                <div class="nav-icon"><i data-lucide="search"></i></div>
                <div class="nav-texts">
                    <span class="nav-text">Consultas</span>
                    <span class="nav-desc">Pesquisas detalhadas</span>
                </div>
                <div class="nav-indicator"></div>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo serc_get_dashboard_url(['view' => 'history']); ?>"
                class="nav-link <?php echo $current_page === 'history' ? 'active' : ''; ?>">
                <div class="nav-icon"><i data-lucide="history"></i></div>
                <div class="nav-texts">
                    <span class="nav-text">Histórico</span>
                    <span class="nav-desc">Consultas passadas</span>
                </div>
                <div class="nav-indicator"></div>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo serc_get_dashboard_url(['view' => 'reports']); ?>"
                class="nav-link <?php echo $current_page === 'reports' ? 'active' : ''; ?>">
                <div class="nav-icon"><i data-lucide="bar-chart-2"></i></div>
                <div class="nav-texts">
                    <span class="nav-text">Relatórios</span>
                    <span class="nav-desc">Consumo de créditos</span>
                </div>
                <div class="nav-indicator"></div>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo serc_get_dashboard_url(['view' => 'shop']); ?>"
                class="nav-link <?php echo $current_page === 'shop' ? 'active' : ''; ?>">
                <div class="nav-icon"><i data-lucide="shopping-bag"></i></div>
                <div class="nav-texts">
                    <span class="nav-text">Loja</span>
                    <span class="nav-desc">Adquirir saldos</span>
                </div>
                <div class="nav-indicator"></div>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo serc_get_dashboard_url(['view' => 'orders']); ?>"
                class="nav-link <?php echo $current_page === 'orders' ? 'active' : ''; ?>">
                <div class="nav-icon"><i data-lucide="package"></i></div>
                <div class="nav-texts">
                    <span class="nav-text">Pedidos</span>
                    <span class="nav-desc">Faturas e pedidos</span>
                </div>
                <div class="nav-indicator"></div>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo serc_get_dashboard_url(['view' => 'settings']); ?>"
                class="nav-link <?php echo $current_page === 'settings' ? 'active' : ''; ?>">
                <div class="nav-icon"><i data-lucide="settings"></i></div>
                <div class="nav-texts">
                    <span class="nav-text">Configuração</span>
                    <span class="nav-desc">Ajustes e perfil</span>
                </div>
                <div class="nav-indicator"></div>
            </a>
        </li>
    </ul>

    <!-- FOOTER (empty — user actions moved to header avatar dropdown) -->
    <div class="sidebar-footer"></div>
</aside>