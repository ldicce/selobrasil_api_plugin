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

    <div class="dashboard-main">
        <div class="main-card"
            style="margin-bottom: 30px; background: transparent; border: none; box-shadow: none; padding: 0;">
            <h2 class="welcome-title">O que você quer fazer agora?</h2>

            <div class="action-grid">
                <div class="action-grid">
                    <a href="<?php echo serc_get_dashboard_url(['view' => 'category', 'type' => 'cpf']); ?>"
                        class="action-card">
                        <i class="ph ph-identification-card"></i> Consultar CPF
                    </a>
                    <a href="<?php echo serc_get_dashboard_url(['view' => 'category', 'type' => 'cnpj']); ?>"
                        class="action-card">
                        <i class="ph ph-buildings"></i> Consultar CNPJ
                    </a>
                    <a href="<?php echo serc_get_dashboard_url(['view' => 'category', 'type' => 'veicular']); ?>"
                        class="action-card">
                        <i class="ph ph-car"></i> Veicular
                    </a>
                    <a href="<?php echo serc_get_dashboard_url(['view' => 'category', 'type' => 'juridico']); ?>"
                        class="action-card" style="background: #f0f7f4">
                        <i class="ph ph-scales"></i> Jurídico
                    </a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-title">Créditos</div>
                    <div class="stat-value">
                        <img src="<?php echo plugin_dir_url(__FILE__); ?>assets/img/credit.svg" alt="Ícone Créditos"
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
<?php // No footer needed for admin partial ?>