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

    <?php wp_head(); ?>
    <style>
        :root {
            --primary-green: #008f4c;
            --bg-color: #f5f6fa;
            --text-color: #333;
            --text-muted: #7B7B7B;
            --border-color: #e0e0e0;
            --font-main: 'Poppins', sans-serif;
            --sidebar-width: 240px;
            --header-height: 70px;
        }

        body {
            font-family: var(--font-main);
            background: var(--bg-color);
            margin: 0;
            padding: 0;
            color: var(--text-color);
            overflow-x: hidden;
        }

        /* Layout Grid */
        .dashboard-wrapper {
            display: grid;
            grid-template-columns: var(--sidebar-width) 1fr;
            grid-template-rows: var(--header-height) 1fr;
            grid-template-areas:
                "logo header"
                "sidebar content";
            min-height: 100vh;
        }

        /* LOGO & SIDEBAR */
        .area-logo {
            grid-area: logo;
            background: #fff;
            display: flex;
            align-items: center;
            padding-left: 24px;
            border-bottom: 1px solid var(--border-color);
            border-right: 1px solid var(--border-color);
        }

        .area-sidebar {
            grid-area: sidebar;
            background: #fff;
            border-right: 1px solid var(--border-color);
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-item {
            margin-bottom: 8px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 24px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 15px;
            font-weight: 300;
            transition: all 0.2s;
            gap: 12px;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-green);
            background: #f0fdf4;
            font-weight: 500;
            border-right: 3px solid var(--primary-green);
        }

        .nav-link i {
            font-size: 20px;
        }

        .help-banner {
            margin: 20px;
            background: #001f3f;
            /* Dark background as in visual */
            border-radius: 12px;
            padding: 20px;
            color: #fff;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        /* Background abstract lines simulation */
        .help-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 45%, rgba(255, 255, 255, 0.1) 50%, transparent 55%);
            pointer-events: none;
        }

        /* HEADER */
        .area-header {
            grid-area: header;
            background: #fff;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .dashboard-crumb {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .global-search {
            position: relative;
            width: 400px;
        }

        .global-search input {
            width: 100%;
            background: #f5f5f5;
            border: none;
            padding: 10px 15px 10px 40px;
            border-radius: 6px;
            font-family: var(--font-main);
            font-size: 14px;
        }

        .global-search i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .credits-capsule {
            background: #f0fdf4;
            color: var(--primary-green);
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* MAIN CONTENT AREA SHARED STYLES */
        .area-content {
            grid-area: content;
            padding: 30px;
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 30px;
            align-items: start;
        }
    </style>
</head>

<body <?php body_class(); ?>>

    <div class="dashboard-wrapper">
        <!-- LOGO AREA -->
        <div class="area-logo">
            <a href="?p=dashboard" style="display:flex;align-items:center;text-decoration:none;">
                <img src="img/LOGO.svg" alt="Selo Brasil" style="height: 40px;">
            </a>
        </div>

        <!-- HEADER -->
        <div class="area-header">
            <div class="header-left">
                <a href="?p=dashboard" class="dashboard-crumb">
                    <i class="ph-fill ph-squares-four"></i> Dashboard
                </a>
                <div class="global-search">
                    <i class="ph ph-magnifying-glass"></i>
                    <input type="text" placeholder="Buscar consultas">
                </div>
            </div>
            <div class="header-right">
                <div class="credits-capsule">
                    <img src="img/credit.svg" alt="Ícone Créditos"
                        style="width: 18px; height: 18px; vertical-align: middle;"> Créditos:
                    <?php echo number_format(serc_get_user_credits(), 2, ',', ('.')); ?>
                </div>
                <img src="https://ui-avatars.com/api/?name=User&background=random" alt="User" class="user-avatar">
            </div>
        </div>