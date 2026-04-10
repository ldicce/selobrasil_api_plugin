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
$current_user = wp_get_current_user();
$user_name = $current_user->display_name ?: 'Usuário';

// Only include header/sidebar for full page loads
if (!$is_ajax) {
    include plugin_dir_path(__FILE__) . 'includes/header.php';
    include plugin_dir_path(__FILE__) . 'includes/sidebar.php';
    echo '<div class="area-content">';
}

// Prepare stats
$credits = serc_get_user_credits();
$today_queries = serc_get_today_query_count($user_id);
// Prepare favorites
$favorites = get_user_meta($user_id, 'serc_favorite_integrations', true);
if (!is_array($favorites)) {
    $favorites = [];
}
$fav_count = count($favorites);
?>

<div class="dashboard-redesign">
    <!-- 1. Header Area -->
    <div class="dash-header-row">
        <div class="dash-header-texts">
            <h1 class="dash-title">Olá, <?php echo esc_html($user_name); ?>! 👋</h1>
            <p class="dash-subtitle">O que você procura hoje?</p>
        </div>
    </div>

    <!-- 2. Action Bar -->
    <div class="dash-action-bar">
        <a href="<?php echo serc_get_dashboard_url(['view' => 'shop']); ?>" class="dash-action-btn-primary">
            <i data-lucide="shopping-cart"></i> Adquirir Saldo
        </a>
    </div>

    <!-- 3. Metrics Grid (Dark Green Cards Carousel for Categories) -->
    <div class="dash-metrics-grid">
        <!-- Card 1: CPF -->
        <a href="<?php echo serc_get_dashboard_url(['view' => 'category', 'type' => 'cpf']); ?>" class="dash-metric-card">
            <div class="card-top">
                <span class="card-name"><i data-lucide="id-card"></i></span>
                <span class="card-badge badge-normal">popular</span>
            </div>
            <div class="card-main">
                <span class="card-value">CPF</span>
            </div>
            <div class="card-bottom">
                <span class="card-desc">Pessoa Física</span>
                <span class="card-trend trend-up">Consultar <i data-lucide="arrow-right"></i></span>
            </div>
        </a>

        <!-- Card 2: CNPJ -->
        <a href="<?php echo serc_get_dashboard_url(['view' => 'category', 'type' => 'cnpj']); ?>" class="dash-metric-card">
            <div class="card-top">
                <span class="card-name"><i data-lucide="building-2"></i></span>
                <span class="card-badge badge-optimal">empresas</span>
            </div>
            <div class="card-main">
                <span class="card-value">CNPJ</span>
            </div>
            <div class="card-bottom">
                <span class="card-desc">Pessoa Jurídica</span>
                <span class="card-trend trend-up">Consultar <i data-lucide="arrow-right"></i></span>
            </div>
        </a>

        <!-- Card 3: Veicular -->
        <a href="<?php echo serc_get_dashboard_url(['view' => 'category', 'type' => 'veicular']); ?>" class="dash-metric-card">
            <div class="card-top">
                <span class="card-name"><i data-lucide="car"></i></span>
                <span class="card-badge badge-normal">frota</span>
            </div>
            <div class="card-main">
                <span class="card-value">Auto</span>
            </div>
            <div class="card-bottom">
                <span class="card-desc">Placa e Chassi</span>
                <span class="card-trend trend-up">Consultar <i data-lucide="arrow-right"></i></span>
            </div>
        </a>

        <!-- Card 4: Crédito -->
        <a href="<?php echo serc_get_dashboard_url(['view' => 'category', 'type' => 'credito']); ?>" class="dash-metric-card">
            <div class="card-top">
                <span class="card-name"><i data-lucide="credit-card"></i></span>
                <span class="card-badge badge-high">finanças</span>
            </div>
            <div class="card-main">
                <span class="card-value">Score</span>
            </div>
            <div class="card-bottom">
                <span class="card-desc">Análise de Crédito</span>
                <span class="card-trend trend-up">Consultar <i data-lucide="arrow-right"></i></span>
            </div>
        </a>

        <!-- Card 5: Jurídico -->
        <a href="<?php echo serc_get_dashboard_url(['view' => 'category', 'type' => 'juridico']); ?>" class="dash-metric-card">
            <div class="card-top">
                <span class="card-name"><i data-lucide="scale"></i></span>
                <span class="card-badge badge-normal">legal</span>
            </div>
            <div class="card-main">
                <span class="card-value">Leilão</span>
            </div>
            <div class="card-bottom">
                <span class="card-desc">Jurídico e Leilões</span>
                <span class="card-trend trend-up">Consultar <i data-lucide="arrow-right"></i></span>
            </div>
        </a>
    </div>

    <!-- 4. Main Two-Pane Content -->
    <div class="dash-content-split">
        <!-- Pane 1: Favoritos (Energy Consumption style) -->
        <div class="dash-pane pane-favorites">
            <div class="pane-header">
                <h3>Meus Favoritos</h3>
                <p>Acesso rápido aos seus serviços de consulta mais utilizados</p>
            </div>
            <div class="pane-body flex-center">
                <!-- Our existing favorites grid, adapted to fit the large white area centrally -->
                <div class="fav-grid-centered" id="favorites-grid">
                    <?php
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

                    // Render 6 slots
                    for ($i = 0; $i < 6; $i++):
                        if (isset($fav_details[$i])):
                            $fav = $fav_details[$i];
                            $url = serc_get_dashboard_url(['view' => 'query', 'integration' => $fav['id']]);
                            ?>
                            <a href="<?php echo $url; ?>" class="fav-card fav-card--filled"
                                data-integration-id="<?php echo esc_attr($fav['id']); ?>" data-slot-index="<?php echo $i; ?>">
                                <i data-lucide="<?php echo esc_attr(!empty($fav['icon']) ? $fav['icon'] : 'search'); ?>"></i>
                                <span class="fav-card__name"><?php echo esc_html($fav['name']); ?></span>
                                <button class="fav-card__edit"
                                    onclick="event.preventDefault(); openFavoriteSelector(this, <?php echo $i; ?>)"
                                    title="Editar favorito">
                                    <i data-lucide="pencil"></i>
                                </button>
                                <button class="fav-card__remove"
                                    onclick="event.preventDefault(); removeFavorite('<?php echo esc_attr($fav['id']); ?>')"
                                    title="Remover favorito">
                                    <i data-lucide="x"></i>
                                </button>
                            </a>
                        <?php else: ?>
                            <div class="fav-card fav-card--empty" onclick="openFavoriteSelector(this)" title="Adicionar favorito">
                                <i data-lucide="plus"></i>
                                <span>Adicionar</span>
                            </div>
                        <?php endif; endfor; ?>
                </div>
            </div>
        </div>

        <!-- Pane 2: Resumo de Métricas -->
        <div class="dash-pane pane-metrics-summary">
            <div class="pane-header">
                <h3>Resumo de Uso</h3>
                <p>Visão geral do consumo de créditos e consultas</p>
            </div>
            <div class="pane-body dash-metrics-summary-body">

                <?php
                // Calcula dados do mês atual diretamente do user meta
                $all_activities   = get_user_meta($user_id, 'serc_activities', true);
                if (!is_array($all_activities)) $all_activities = [];

                $month_ts_start = strtotime(date('Y-m-01') . ' 00:00:00');
                $month_ts_end   = strtotime(date('Y-m-t')  . ' 23:59:59');

                $month_queries = 0;
                $month_credits = 0.0;

                foreach ($all_activities as $act) {
                    $ts = isset($act['timestamp']) ? intval($act['timestamp']) : 0;
                    if ($ts < $month_ts_start || $ts > $month_ts_end) continue;
                    if ($act['type'] === 'query') $month_queries++;
                    if ($act['type'] === 'debit' && preg_match('/-([0-9]+\.?[0-9]*)$/', $act['description'], $m)) {
                        $month_credits += floatval($m[1]);
                    }
                }

                // Última atividade (qualquer tipo)
                $last_activities = serc_get_user_activities($user_id, 1);
                $last_act        = !empty($last_activities) ? $last_activities[0] : null;
                
                $action_label = 'Atualização de dados';
                $time_label   = '';

                if ($last_act) {
                    // Type standardization
                    if ($last_act['type'] === 'query') {
                        $action_label = 'Consulta realizada';
                    } elseif ($last_act['type'] === 'download') {
                        $action_label = 'Download de relatório';
                    } elseif ($last_act['type'] === 'access') {
                        $action_label = 'Acesso a funcionalidade';
                    }

                    // Time logic
                    $ts = intval($last_act['timestamp']);
                    $now = current_time('timestamp');
                    $diff = max(0, $now - $ts);
                    
                    $today_midnight = strtotime('today', $now);
                    $yesterday_midnight = strtotime('yesterday', $now);
                    
                    if ($diff < 3600) {
                        $mins = floor($diff / 60);
                        $time_label = $mins <= 1 ? 'agora mesmo' : "há {$mins} minutos";
                    } else if ($ts >= $today_midnight) {
                        $time_label = 'hoje às ' . date('H:i', $ts);
                    } else if ($ts >= $yesterday_midnight) {
                        $time_label = 'ontem às ' . date('H:i', $ts);
                    } else {
                        $time_label = 'em ' . date('d/m/Y', $ts);
                    }
                }
                ?>

                <!-- Linha 1: Créditos e Consultas do mês -->
                <div class="dash-summary-row">
                    <div class="dash-summary-tile dash-summary-tile--green">
                        <div class="dash-summary-tile__icon"><i data-lucide="coins"></i></div>
                        <div class="dash-summary-tile__info">
                            <span class="dash-summary-tile__label">Créditos este mês</span>
                            <span class="dash-summary-tile__value"><?php echo number_format($month_credits, 2, ',', '.'); ?></span>
                            <span class="dash-summary-tile__sub">consumidos</span>
                        </div>
                    </div>
                    <div class="dash-summary-tile dash-summary-tile--blue">
                        <div class="dash-summary-tile__icon"><i data-lucide="search"></i></div>
                        <div class="dash-summary-tile__info">
                            <span class="dash-summary-tile__label">Consultas este mês</span>
                            <span class="dash-summary-tile__value"><?php echo esc_html($month_queries); ?></span>
                            <span class="dash-summary-tile__sub">realizadas</span>
                        </div>
                    </div>
                </div>

                <!-- Linha 2: Saldo + Consultas hoje -->
                <div class="dash-summary-row">
                    <div class="dash-summary-tile dash-summary-tile--amber">
                        <div class="dash-summary-tile__icon"><i data-lucide="activity"></i></div>
                        <div class="dash-summary-tile__info">
                            <span class="dash-summary-tile__label">Consultas hoje</span>
                            <span class="dash-summary-tile__value"><?php echo esc_html($today_queries); ?></span>
                            <span class="dash-summary-tile__sub">realizadas</span>
                        </div>
                    </div>
                    <div class="dash-summary-tile dash-summary-tile--neutral">
                        <div class="dash-summary-tile__icon"><i data-lucide="clock"></i></div>
                        <div class="dash-summary-tile__info">
                            <span class="dash-summary-tile__label">Última atividade</span>
                            <?php if ($last_act): ?>
                                <div style="display: flex; align-items: baseline; gap: 6px; margin-top: 4px; flex-wrap: wrap;">
                                    <span style="color: #FFFFFF; font-weight: 500; font-size: 15px; letter-spacing: -0.2px;"><?php echo esc_html($action_label); ?></span>
                                    <span style="color: #A0A0A0; font-size: 13px;">• <?php echo esc_html($time_label); ?></span>
                                </div>
                            <?php else: ?>
                                <span class="dash-summary-tile__value dash-summary-tile__value--sm" style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">Nenhuma atividade</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Footer: link para relatórios -->
                <div class="dash-summary-footer">
                    <a href="<?php echo serc_get_dashboard_url(['view' => 'reports']); ?>" class="dash-summary-footer__link">
                        <i data-lucide="bar-chart-2"></i>
                        Ver relatório completo
                        <i data-lucide="arrow-right"></i>
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>

<?php if (!$is_ajax): ?>
    </div> <!-- .area-content -->
    </div> <!-- .dashboard-wrapper -->
<?php endif; ?>