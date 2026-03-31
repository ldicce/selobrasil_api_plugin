<?php
/**
 * View: Category List (Lists all queries for a specific category)
 * Supports both full page load and AJAX partial loading
 */
if (!defined('ABSPATH'))
    exit;

// Check if this is an AJAX request (set by serc_load_dashboard_view)
global $serc_ajax_request;
$is_ajax = !empty($serc_ajax_request);

// Only include header/sidebar for full page loads
if (!$is_ajax) {
    include plugin_dir_path(__FILE__) . 'includes/header.php';
    include plugin_dir_path(__FILE__) . 'includes/sidebar.php';
    echo '<div class="area-content">';
}
require_once plugin_dir_path(__FILE__) . 'includes/integrations-config.php';

// Get category from URL parameter
$category = $_GET['type'] ?? '';

// Pre-calculate user stats for the action bar
$user_id = get_current_user_id();
$credits = serc_get_user_credits();
$today_queries = serc_get_today_query_count($user_id);
$favorites = get_user_meta($user_id, 'serc_favorite_integrations', true);
$fav_count = is_array($favorites) ? count($favorites) : 0;

// Category names mapping
$category_names = [
    'cpf' => 'CPF',
    'cnpj' => 'CNPJ',
    'veicular' => 'Veicular',
    'credito' => 'Dívidas e Crédito',
    'juridico' => 'Jurídico'
];
$category_display = $category_names[$category] ?? 'Geral';
?>

<div class="dashboard-redesign">
    <!-- 1. Header Area -->
    <div class="dash-header-row">
        <div class="dash-header-texts">
            <h1 class="dash-title">Consultas: <?php echo esc_html($category_display); ?></h1>
            <p class="dash-subtitle">Escolha o serviço desejado para iniciar sua consulta.</p>
        </div>
    </div>

    <!-- 2. Navigation Carousel (The 4 Green Cards) -->
    <div class="dash-metrics-grid">
        <!-- Card 1: CPF -->
        <a href="<?php echo serc_get_dashboard_url(['view' => 'category', 'type' => 'cpf']); ?>" 
           class="dash-metric-card <?php echo $category === 'cpf' ? 'active' : ''; ?>">
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
        <a href="<?php echo serc_get_dashboard_url(['view' => 'category', 'type' => 'cnpj']); ?>" 
           class="dash-metric-card <?php echo $category === 'cnpj' ? 'active' : ''; ?>">
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
        <a href="<?php echo serc_get_dashboard_url(['view' => 'category', 'type' => 'veicular']); ?>" 
           class="dash-metric-card <?php echo $category === 'veicular' ? 'active' : ''; ?>">
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
        <a href="<?php echo serc_get_dashboard_url(['view' => 'category', 'type' => 'credito']); ?>" 
           class="dash-metric-card <?php echo $category === 'credito' ? 'active' : ''; ?>">
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
        <a href="<?php echo serc_get_dashboard_url(['view' => 'category', 'type' => 'juridico']); ?>" 
           class="dash-metric-card <?php echo $category === 'juridico' ? 'active' : ''; ?>">
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

    <!-- 4. Content Area (Integration List) -->
    <div class="dash-pane pane-full-width">
        <div class="pane-header">
            <h3><?php echo empty($category) ? 'Todos os Serviços' : 'Serviços em ' . esc_html($category_display); ?></h3>
            <p>Selecione um serviço específico para processar sua consulta agora.</p>
        </div>
        
        <div class="pane-body">
            <?php
            if (empty($category)) {
                $all_cats = serc_get_integrations_config();
                $current_integrations = [];
                foreach ($all_cats as $cat_integrations) {
                    $current_integrations = array_merge($current_integrations, $cat_integrations);
                }
            } else {
                $current_integrations = serc_get_category_integrations($category);
            }

            if (empty($current_integrations)) {
                echo '<div class="alert-info-minimal">Nenhum serviço disponível.</div>';
            } else {
                    ?>
                    <div class="integration-table">
                        <div class="integration-header">
                            <div>SERVIÇO / CONSULTA</div>
                            <div style="text-align: center;">CUSTO</div>
                            <div style="text-align: center;">TIPO</div>
                            <div style="text-align: right;">AÇÃO</div>
                        </div>

                        <?php foreach ($current_integrations as $integration): ?>
                            <a href="<?php echo serc_get_dashboard_url(['view' => 'query', 'integration' => $integration['id']]); ?>" 
                               class="integration-row">
                                <div class="integration-info">
                                    <div class="integration-icon">
                                        <i data-lucide="<?php echo esc_attr(!empty($integration['icon']) ? $integration['icon'] : 'search'); ?>"></i>
                                    </div>
                                    <div class="integration-details">
                                        <div class="integration-name"><?php echo esc_html($integration['name']); ?></div>
                                        <div class="integration-description"><?php echo esc_html($integration['description']); ?></div>
                                    </div>
                                </div>
                                <div class="integration-value" style="text-align: center;">
                                    <span class="badge-value-tag">
                                        <img src="<?php echo plugin_dir_url(__FILE__) . 'assets/img/credit.svg'; ?>" alt="" style="width:14px;height:14px;vertical-align:middle;">
                                        <?php echo esc_html($integration['value']); ?>
                                    </span>
                                </div>
                                <div class="integration-type" style="text-align: center;">
                                    <span class="type-pill"><?php echo esc_html($integration['type'] ?: 'Normal'); ?></span>
                                </div>
                                <div class="integration-action" style="text-align: right;">
                                    <span class="btn-consultar-small">Consultar <i data-lucide="chevron-right"></i></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php } ?>
        </div>
    </div>
</div>

<?php if (!$is_ajax): ?>
    </div> <!-- .area-content -->
    </div> <!-- .dashboard-wrapper -->
<?php endif; ?>