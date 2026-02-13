<?php
/**
 * View: Dashboard (Home)
 * Supports both full page load and AJAX partial loading
 */
if (!defined('ABSPATH'))
    exit;

// Require user to be logged in — redirect to WP login/register page if not
if (!is_user_logged_in()) {
    wp_redirect('/login');
    exit;
}

// Check if this is an AJAX request (set by serc_load_dashboard_view)
global $serc_ajax_request;
$is_ajax = !empty($serc_ajax_request);

// Get current user ID for use throughout the dashboard
$user_id = get_current_user_id();

// Only include header/sidebar for full page loads
if (!$is_ajax) {
    include plugin_dir_path(__FILE__) . 'includes/header.php';
    include plugin_dir_path(__FILE__) . 'includes/sidebar.php';
    echo '<div class="area-content">';
}
?>

<div class="dashboard-main">
    <div class="main-card"
        style="margin-bottom: 30px; background: transparent; border: none; box-shadow: none; padding: 0;">
        <h2 class="welcome-title">O que você quer fazer agora?</h2>

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
                    <?php echo serc_get_today_query_count($user_id); ?> <span
                        style="font-size: 14px; font-weight: 400; color: #666; margin-left: 4px;">Consultas</span>
                </div>
            </div>
        </div>

        <div class="favorites-section">
            <h3>Consultas favoritas</h3>
            <div class="fav-grid" id="favorites-grid">
                <?php
                $favorites = get_user_meta($user_id, 'serc_favorite_integrations', true);
                if (!is_array($favorites)) {
                    $favorites = [];
                }


                // Get integration details
                require_once plugin_dir_path(__FILE__) . 'includes/integrations-config.php';
                $all_integrations = serc_get_integrations_config();
                $fav_details = [];

                foreach ($favorites as $fav_id) {
                    foreach ($all_integrations as $category => $integrations) {
                        foreach ($integrations as $integration) {
                            if ($integration['id'] === $fav_id) {
                                $fav_details[] = $integration;
                                break 2;
                            }
                        }
                    }
                }

                // Render 6 slots - filled or empty
                for ($i = 0; $i < 6; $i++):
                    if (isset($fav_details[$i])):
                        $fav = $fav_details[$i];
                        $url = serc_get_dashboard_url(['view' => 'query', 'integration' => $fav['id']]);
                        ?>
                        <a href="<?php echo $url; ?>" class="fav-card fav-card--filled"
                            data-integration-id="<?php echo esc_attr($fav['id']); ?>" data-slot-index="<?php echo $i; ?>">
                            <i class="<?php echo esc_attr($fav['icon'] ?? 'ph-puzzle-piece'); ?>"></i>
                            <span class="fav-card__name"><?php echo esc_html($fav['name']); ?></span>
                            <button class="fav-card__edit"
                                onclick="event.preventDefault(); event.stopPropagation(); openFavoriteSelector(this, <?php echo $i; ?>)"
                                title="Editar favorito">
                                <img src="<?php echo plugin_dir_url(__FILE__); ?>assets/img/Edit_Pencil_Line_01.svg"
                                    alt="Editar" class="fav-card__edit-icon">
                            </button>
                            <button class="fav-card__remove"
                                onclick="event.preventDefault(); event.stopPropagation(); removeFavorite('<?php echo esc_attr($fav['id']); ?>')"
                                title="Remover favorito">
                                <i class="ph-x"></i>
                            </button>
                        </a>
                    <?php else: ?>
                        <div class="fav-card fav-card--empty" onclick="openFavoriteSelector(this)" title="Adicionar favorito">
                            <img src="<?php echo plugin_dir_url(__FILE__); ?>assets/img/Add_Plus_Square.svg" alt="Adicionar"
                                class="fav-card__add-icon">
                        </div>
                    <?php endif; endfor; ?>
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
        <?php
        $activities = serc_get_user_activities($user_id, 10);
        if (empty($activities)):
            ?>
            <div class="activity-item">
                <div class="activity-dot gray"></div>
                <div class="activity-text">Nenhuma atividade recente</div>
            </div>
            <?php
        else:
            foreach ($activities as $activity):
                // Determine dot color based on activity type
                $dot_color = 'gray';
                if ($activity['type'] === 'login') {
                    $dot_color = 'green';
                } elseif ($activity['type'] === 'query') {
                    $dot_color = 'yellow';
                } elseif ($activity['type'] === 'download') {
                    $dot_color = 'blue';
                }

                // Format timestamp
                $time_ago = human_time_diff($activity['timestamp'], current_time('timestamp'));
                ?>
                <div class="activity-item">
                    <div class="activity-dot <?php echo $dot_color; ?>"></div>
                    <div class="activity-text"><?php echo esc_html($activity['description']); ?></div>
                    <span class="activity-meta"><?php echo $time_ago; ?> atrás</span>
                </div>
                <?php
            endforeach;
        endif;
        ?>
    </div>

    <button class="btn-see-all">
        Ver todos <i class="ph-caret-right"></i>
    </button>


</div>



<?php if (!$is_ajax): ?>
    </div>
    </div>
<?php endif; ?>