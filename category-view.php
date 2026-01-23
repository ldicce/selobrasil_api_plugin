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
}
require_once plugin_dir_path(__FILE__) . 'includes/integrations-config.php';

// Get category from URL parameter
$category = $_GET['type'] ?? 'cpf';

// Category names mapping
$category_names = [
    'cpf' => 'CPF',
    'cnpj' => 'CNPJ',
    'veicular' => 'Veicular',
    'juridico' => 'Jurídico'
];

$category_display = $category_names[$category] ?? 'Consultas';

// Load real integrations from config
$current_integrations = serc_get_category_integrations($category);

if (!$is_ajax) {
    echo '<div class="area-content" style="grid-template-columns: 1fr;">';
}
?>

<div class="category-header">
    <h1 class="category-title">Categoria:
        <?php echo esc_html($category_display); ?>
    </h1>

    <div class="category-toolbar">
        <div class="category-search">
            <i class="ph ph-magnifying-glass"></i>
            <input type="text" placeholder="Buscar...">
        </div>
        <button class="btn-consultar-green">
            Consultar
        </button>
    </div>

    <div class="category-tabs">
        <button class="category-tab active">Categorias</button>
        <button class="category-tab">Lista completa</button>
    </div>
</div>

<div class="integration-table">
    <div class="integration-header">
        <div>INTEGRAÇÃO</div>
        <div>VALOR</div>
        <div>TIPO</div>
        <div>ADICIONADO</div>
    </div>

    <?php foreach ($current_integrations as $integration): ?>
        <a href="<?php echo serc_get_dashboard_url(['view' => 'query', 'integration' => $integration['id']]); ?>"
            style="text-decoration: none; color: inherit; display: block;">
            <div class="integration-row" style="cursor: pointer;">
                <div class="integration-info">
                    <div class="integration-icon">
                        <i class="<?php echo esc_attr($integration['icon'] ?? 'ph-file-text'); ?>"></i>
                    </div>
                    <div class="integration-details">
                        <div class="integration-name">
                            <?php echo esc_html($integration['name']); ?>
                        </div>
                        <div class="integration-description">
                            <?php echo esc_html($integration['description']); ?>
                        </div>
                    </div>
                </div>
                <div class="integration-value">
                    <img src="<?php echo plugin_dir_url(__FILE__); ?>assets/img/credit.svg" alt="Ícone Créditos"
                        style="width: 18px; height: 18px; vertical-align: middle;">
                    <?php echo esc_html($integration['value']); ?>
                </div>
                <div class="integration-type">
                    <?php echo esc_html($integration['type']); ?>
                </div>
                <div class="integration-date">
                    <?php echo esc_html(date('d/m/Y')); ?>
                </div>
            </div>
        </a>
    <?php endforeach; ?>
</div>
<?php if (!$is_ajax): ?>
    </div>
    </div>
<?php endif; ?>