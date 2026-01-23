<?php
if (!defined('ABSPATH'))
    exit;
?>
<div class="dashboard-wrapper">
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

    <!-- Dashboard Navigation Script -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script
        src="<?php echo plugins_url('assets/js/serc-frontend.js', dirname(__DIR__) . '/serpro-cnpj-quotas.php'); ?>"></script>

    <!-- LOGO AREA -->
    <div class="area-logo">
        <a href="<?php echo serc_get_dashboard_url(['view' => 'dashboard']); ?>"
            style="display:flex;align-items:center;text-decoration:none;">
            <img src="<?php echo plugins_url('assets/img/LOGO.svg', dirname(__DIR__) . '/serpro-cnpj-quotas.php'); ?>"
                alt="Selo Brasil" style="height: 40px;">
        </a>
    </div>

    <!-- HEADER -->
    <div class="area-header">
        <div class="header-left">
            <a href="<?php echo serc_get_dashboard_url(['view' => 'dashboard']); ?>" class="dashboard-crumb">
                <i class="ph-fill ph-squares-four"></i> Dashboard
            </a>
            <div class="global-search">
                <i class="ph ph-magnifying-glass"></i>
                <input type="text" placeholder="Buscar consultas">
            </div>
        </div>
        <div class="header-right">
            <div class="credits-capsule">
                <img src="<?php echo plugin_dir_url(__FILE__); ?>../assets/img/credit.svg" alt="Ícone Créditos"
                    style="width: 18px; height: 18px; vertical-align: middle;"> Créditos:
                <?php echo number_format(serc_get_user_credits(), 2, ',', ('.')); ?>
            </div>
            <img src="https://ui-avatars.com/api/?name=User&background=random" alt="User" class="user-avatar">
        </div>
    </div>