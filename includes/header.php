<?php
if (!defined('ABSPATH'))
    exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Selo Brasil - Dashboard</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Icons (Phosphor Icons) -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

</head>

<body <?php body_class(); ?>>

    <div class="dashboard-wrapper">
        <!-- LOGO AREA -->
        <div class="area-logo">
            <a href="<?php echo admin_url('admin.php?page=serc-dashboard'); ?>"
                style="display:flex;align-items:center;text-decoration:none;">
                <img src="<?php echo plugin_dir_url(__FILE__); ?>../assets/img/LOGO.svg" alt="Selo Brasil"
                    style="height: 40px;">
            </a>
        </div>

        <!-- HEADER -->
        <div class="area-header">
            <div class="header-left">
                <a href="<?php echo admin_url('admin.php?page=serc-dashboard'); ?>" class="dashboard-crumb">
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