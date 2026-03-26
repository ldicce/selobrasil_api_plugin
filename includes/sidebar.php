<?php
if (!defined('ABSPATH'))
    exit;

// Helper to check active state (simple version)
$current_page = $_GET['view'] ?? 'dashboard';
?>
<!-- SIDEBAR -->
<div class="area-sidebar">
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="<?php echo serc_get_dashboard_url(['view' => 'dashboard']); ?>"
                class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                <i class="ph-fill ph-house"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo serc_get_dashboard_url(['view' => 'category']); ?>"
                class="nav-link <?php echo $current_page === 'category' || $current_page === 'query' || $current_page === 'consulta' ? 'active' : ''; ?>">
                <i class="ph-bold ph-magnifying-glass"></i> Consultas
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo serc_get_dashboard_url(['view' => 'history']); ?>"
                class="nav-link <?php echo $current_page === 'history' ? 'active' : ''; ?>">
                <i class="ph-duotone ph-clock-counter-clockwise"></i> Histórico
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo serc_get_dashboard_url(['view' => 'reports']); ?>"
                class="nav-link <?php echo $current_page === 'reports' ? 'active' : ''; ?>">
                <i class="ph-fill ph-chart-bar"></i> Relatórios
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo serc_get_dashboard_url(['view' => 'shop']); ?>"
                class="nav-link <?php echo $current_page === 'shop' ? 'active' : ''; ?>">
                <i class="ph-fill ph-storefront"></i> Loja
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo serc_get_dashboard_url(['view' => 'orders']); ?>"
                class="nav-link <?php echo $current_page === 'orders' ? 'active' : ''; ?>">
                <i class="ph-fill ph-package"></i> Pedidos
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo serc_get_dashboard_url(['view' => 'settings']); ?>"
                class="nav-link <?php echo $current_page === 'settings' ? 'active' : ''; ?>">
                <i class="ph-fill ph-gear"></i> Configuração
            </a>
        </li>
    </ul>

    <div class="sidebar-footer" style="margin-top: auto;">
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="nav-link serc-logout-link" style="color: #e74c3c;">
                    <i class="ph-bold ph-sign-out"></i> Sair
                </a>
            </li>
        </ul>
    </div>

    <div class="help-banner">
        <div style="font-weight:bold;margin-bottom:5px;font-size:14px;">Precisar de ajuda?</div>
        <small style="opacity:0.8;font-size:12px;">Consulte nossa página.</small>
        <br>
        <a href="#"
            style="color:#2ECC40;font-size:12px;text-decoration:none;margin-top:10px;display:inline-block;border-bottom:1px solid #2ECC40;">Saiba
            mais ↗</a>
    </div>
</div>