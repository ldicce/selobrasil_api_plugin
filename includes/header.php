<?php
if (!defined('ABSPATH'))
    exit;
?>
<div class="dashboard-wrapper">
    <script>
        // Immediatelly apply dark mode and prevent transition flash
        (function() {
            try {
                document.body.classList.add('preload');
                var theme = localStorage.getItem('serc_theme');
                if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.body.classList.add('dark-mode');
                } else {
                    document.body.classList.remove('dark-mode');
                }
                window.addEventListener('load', function() {
                    setTimeout(function() { document.body.classList.remove('preload'); }, 50);
                });
            } catch (e) {}
        })();
    </script>
    <?php
    // CSS Injection (Inline to bypass path issues)
    $style_path = dirname(__DIR__) . '/assets/css/style.css';
    if (file_exists($style_path)) {
        echo '<style>' . file_get_contents($style_path) . '</style>';
    }
    ?>

    <!-- Fonts Injection (Direct) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Lucide Icons (Modern SVG Line Icons) -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Dashboard Navigation Script -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        var serc_ajax = {
            ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('serpro_cnpj_nonce'); ?>'
        };
    </script>
    <script
        src="<?php echo plugins_url('assets/js/serc-frontend.js', dirname(__DIR__) . '/serpro-cnpj-quotas.php'); ?>?v=3.2.1"></script>

    <!-- Initialize Lucide Icons (must run after DOM is ready) -->
    <script>
        function serc_initLucide() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
        document.addEventListener('DOMContentLoaded', serc_initLucide);
    </script>

    <!-- LOGO AREA REPLACED BY SIDEBAR -->

    <!-- HEADER -->
    <div class="area-header">
        <div class="header-left">
            <a href="<?php echo serc_get_dashboard_url(['view' => 'dashboard']); ?>" class="dashboard-crumb">
                <i data-lucide="layout-dashboard"></i> Dashboard
            </a>
            <!-- Mobile Logo (Visible only on mobile) -->
            <a href="<?php echo serc_get_dashboard_url(['view' => 'dashboard']); ?>" class="mobile-logo">
                <img src="<?php echo plugins_url('assets/img/LOGO.svg', dirname(__DIR__) . '/serpro-cnpj-quotas.php'); ?>"
                    alt="Selo Brasil" class="mobile-logo-img">
                <span class="mobile-logo-text">Selo Brasil</span>
            </a>
            <div class="global-search">
                <i data-lucide="search"></i>
                <input type="text" placeholder="Buscar consultas">
                <div class="global-search-results"></div>
            </div>
        </div>
        <div class="header-right">
            <div class="credits-capsule">
                <img src="<?php echo plugin_dir_url(__FILE__); ?>../assets/img/credit.svg" alt="Ícone Créditos"
                    style="width: 18px; height: 18px; vertical-align: middle;"> Créditos:
                <?php echo number_format(serc_get_user_credits(), 2, ',', ('.')); ?>
            </div>
            <a href="#" class="header-help-btn" title="Precisar de ajuda? Consulte nossa página.">
                <i data-lucide="help-circle"></i>
            </a>
            <button id="serc-theme-toggle" class="theme-toggle-btn" title="Alternar tema" aria-label="Alternar modo escuro/claro">
                <i data-lucide="moon" id="serc-theme-icon"></i>
            </button>
            <!-- User Avatar Dropdown -->
            <div class="user-avatar-wrap" id="serc-user-menu-wrap">
                <button type="button" class="user-avatar-link" id="serc-user-menu-btn" title="Menu do usuário" aria-haspopup="true" aria-expanded="false">
                    <img src="https://ui-avatars.com/api/?name=User&background=random" alt="User" class="user-avatar">
                    <i data-lucide="chevron-down" style="width: 16px; height: 16px; margin-left: 6px; color: var(--text-muted); transition: transform 0.2s;"></i>
                </button>
                <div class="user-dropdown" id="serc-user-dropdown" role="menu">
                    <a href="<?php echo serc_get_dashboard_url(['view' => 'settings']); ?>" class="user-dropdown-item" role="menuitem">
                        <i data-lucide="settings"></i>
                        <span>Configurações da conta</span>
                    </a>
                    <div class="user-dropdown-divider"></div>
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="user-dropdown-item user-dropdown-item--danger" role="menuitem">
                        <i data-lucide="log-out"></i>
                        <span>Sair</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Bottom Navigation (Persistent) -->
    <div class="mobile-bottom-nav">
        <a href="<?php echo serc_get_dashboard_url(['view' => 'dashboard']); ?>"
            class="mobile-nav-item <?php echo (!isset($_GET['view']) || $_GET['view'] === 'dashboard') ? 'active' : ''; ?>">
            <i data-lucide="house"></i>
            <span>Início</span>
        </a>
        <a href="<?php echo serc_get_dashboard_url(['view' => 'history']); ?>"
            class="mobile-nav-item <?php echo (isset($_GET['view']) && $_GET['view'] === 'history') ? 'active' : ''; ?>">
            <i data-lucide="history"></i>
            <span>Histórico</span>
        </a>
        <a href="<?php echo serc_get_dashboard_url(['view' => 'category']); ?>"
            class="mobile-nav-item nav-highlight <?php echo (isset($_GET['view']) && ($_GET['view'] === 'category' || $_GET['view'] === 'query')) ? 'active' : ''; ?>">
            <i data-lucide="search"></i>
            <span>Consultas</span>
        </a>
        <a href="https://wa.me/5511999999999" target="_blank" class="mobile-nav-item">
            <i data-lucide="message-circle"></i>
            <span>Suporte</span>
        </a>
        <a href="<?php echo serc_get_dashboard_url(['view' => 'settings']); ?>"
            class="mobile-nav-item <?php echo (isset($_GET['view']) && $_GET['view'] === 'settings') ? 'active' : ''; ?>">
            <i data-lucide="user"></i>
            <span>Conta</span>
        </a>
    </div>