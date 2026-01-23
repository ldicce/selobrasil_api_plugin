<?php
/**
 * View: Dashboard (Home)
 */
if (!defined('ABSPATH'))
    exit;

include plugin_dir_path(__FILE__) . 'includes/header.php';
include plugin_dir_path(__FILE__) . 'includes/sidebar.php';
?>

<!-- MAIN CONTENT -->
<div class="area-content">
    <style>
        /* Styles specific to Dashboard Home */
        .welcome-title {
            margin-top: 0;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .action-card {
            background: #e6f7ef;
            padding: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: transform 0.2s;
        }

        .action-card:hover {
            transform: translateY(-3px);
        }

        .action-card i {
            font-size: 24px;
            color: var(--primary-green);
        }

        .action-card.wide {
            grid-column: span 3;
        }

        /* Example if needed, current design shows 3 cols */

        .stats-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            border: 1px solid #eee;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.01);
        }

        .stat-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stat-value i {
            font-size: 24px;
            color: gold;
        }

        /* Coin icon color */

        .btn-buy-credits {
            background: var(--primary-green);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 15px;
            font-size: 14px;
        }

        .favorites-section h3 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #333;
        }

        .fav-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .fav-card {
            background: #fafafa;
            padding: 15px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #eee;
        }

        .fav-card i {
            color: gold;
        }

        /* Right Sidebar specifics */
        .activity-timeline {
            margin-top: 20px;
        }

        .activity-item {
            position: relative;
            padding-left: 20px;
            margin-bottom: 20px;
            border-left: 2px solid #eee;
        }

        .activity-dot {
            position: absolute;
            left: -6px;
            top: 0;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #ddd;
        }

        .activity-dot.green {
            background: var(--primary-green);
        }

        .activity-dot.yellow {
            background: #FFC107;
        }

        .activity-dot.blue {
            background: #007bff;
        }

        .activity-text {
            font-size: 13px;
            color: #555;
            margin-bottom: 4px;
        }

        .activity-meta {
            font-size: 11px;
            color: #888;
            background: #f5f5f5;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
        }

        .btn-see-all {
            background: #f9f9f9;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: #333;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .promo-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #eee;
            margin-top: 30px;
        }

        .promo-title {
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .promo-text {
            font-size: 12px;
            color: #666;
            margin: 8px 0;
        }

        .promo-link {
            font-size: 12px;
            color: #666;
            text-decoration: underline;
        }
    </style>

    <div class="dashboard-main">
        <div class="main-card"
            style="margin-bottom: 30px; background: transparent; border: none; box-shadow: none; padding: 0;">
            <h2 class="welcome-title">O que você quer fazer agora?</h2>

            <div class="action-grid">
                <a href="?p=consulta&type=cpf" class="action-card">
                    <i class="ph ph-identification-card"></i> Consultar CPF
                </a>
                <a href="?p=consulta&type=cnpj" class="action-card">
                    <i class="ph ph-buildings"></i> Consultar CNPJ
                </a>
                <a href="?p=consulta&type=veicular" class="action-card">
                    <i class="ph ph-car"></i> Veicular
                </a>
                <a href="?p=consulta&type=juridico" class="action-card" style="background: #f0f7f4">
                    <i class="ph ph-scales"></i> Jurídico
                </a>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-title">Créditos</div>
                    <div class="stat-value">
                        <img src="img/credit.svg" alt="Ícone Créditos"
                            style="width: 18px; height: 18px; vertical-align: middle;">
                        <?php echo number_format(serc_get_user_credits(), 2, ',', ('.')); ?>
                    </div>
                    <button class="btn-buy-credits">
                        <i class="ph-bold ph-shopping-bag"></i> Compra de créditos
                    </button>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Uso hoje</div>
                    <div class="stat-value" style="font-size: 24px;">
                        12 <span
                            style="font-size: 14px; font-weight: 400; color: #666; margin-left: 4px;">Consultas</span>
                    </div>
                </div>
            </div>

            <div class="favorites-section">
                <h3>APIs favoritas</h3>
                <div class="fav-grid">
                    <div class="fav-card"><i class="ph-fill ph-star"></i> CPF Completo <i class="ph-caret-right"
                            style="margin-left:auto; font-size:10px; color:#ccc;"></i></div>
                    <div class="fav-card"><i class="ph-fill ph-star"></i> CNPJ Plus <i class="ph-caret-right"
                            style="margin-left:auto; font-size:10px; color:#ccc;"></i></div>
                    <div class="fav-card"><i class="ph-fill ph-star"></i> Veicular Gold <i class="ph-caret-right"
                            style="margin-left:auto; font-size:10px; color:#ccc;"></i></div>
                    <div class="fav-card"><i class="ph-fill ph-star"></i> Jurídico Gold <i class="ph-caret-right"
                            style="margin-left:auto; font-size:10px; color:#ccc;"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT SIDEBAR (ACTIVITY) -->
    <div class="right-sidebar">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin:0; font-size: 16px;">Últimas atividades</h3>
            <i class="ph-caret-right"></i>
        </div>

        <div class="activity-timeline">
            <div class="activity-item">
                <div class="activity-dot green"></div>
                <div class="activity-text">Acesso ao sistema</div>
            </div>
            <div class="activity-item">
                <div class="activity-dot yellow"></div>
                <div class="activity-text">Consulta CPF</div>
            </div>
            <div class="activity-item">
                <div class="activity-dot blue"></div>
                <div class="activity-text">Download do relatório</div>
            </div>
            <div class="activity-item">
                <div class="activity-dot green"></div>
                <div class="activity-text">Requisição realizada na API: Nacional de Delitos Trabalhistas</div>
                <span class="activity-meta">Consultar requisição realizada</span>
            </div>
        </div>

        <button class="btn-see-all">
            Ver todos <i class="ph-caret-right"></i>
        </button>

        <div class="promo-card">
            <div class="promo-title">Novo plano Enterprise <i class="ph-fill ph-rocket"
                    style="color:var(--primary-green)"></i></div>
            <p class="promo-text">Mais limites de APIs.</p>
            <a href="#" class="promo-link">Saiba mais ↗</a>
        </div>
    </div>

</div>
</div>
<?php wp_footer(); ?>
</body>

</html>