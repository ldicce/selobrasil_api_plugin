<?php
/**
 * View: Query Form (Dynamic query execution page)
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

// Get integration from URL parameter
$integration_id = $_GET['integration'] ?? '';
$integration = serc_get_integration_by_id($integration_id);

// Handle if integration not found
if (!$integration) {
    if (!$is_ajax) {
        header('Location: ?p=dashboard');
        exit;
    }
    echo '<div class="error-message">Integração não encontrada.</div>';
    return;
}

if (!$is_ajax) {
    echo '<div class="area-content">';
}
?>

    <div class="query-container">
        <div class="query-header">
            <div class="query-breadcrumb">
                <a href="<?php echo serc_get_dashboard_url(['view' => 'dashboard']); ?>">Dashboard</a> /
                <a href="<?php echo serc_get_dashboard_url(['view' => 'category', 'type' => $integration_id]); ?>">Consultas</a> /
                <?php echo esc_html($integration['name']); ?>
            </div>

            <h1 class="query-title">
                <i class="<?php echo esc_attr($integration['icon'] ?? 'ph-file-text'); ?>"></i>
                <?php echo esc_html($integration['name']); ?>
            </h1>

            <p class="query-description">
                <?php echo esc_html($integration['description']); ?>
            </p>

            <div class="query-meta">
                <div class="query-meta-item">
                    <img src="<?php echo plugins_url('assets/img/credit.svg', __FILE__); ?>" alt="Ícone Créditos" style="width: 18px; height: 18px; vertical-align: middle;">
                    Valor: <strong>
                        <?php echo esc_html($integration['value']); ?> créditos
                    </strong>
                </div>
                <div class="query-meta-item">
                    <i class="ph ph-tag"></i>
                    Tipo: <strong>
                        <?php echo esc_html($integration['type']); ?>
                    </strong>
                </div>
            </div>
        </div>

        <div class="query-form-section">
            <h3>Preencha os dados para consulta</h3>
            <div class="form-wrapper">
                <form class="serc-form" data-type="<?php echo esc_attr($integration['id']); ?>">
                    <?php
                    $fields = $integration['fields'] ?? [];
                    if (empty($fields)): ?>
                            <p style="color: #999;">Formulário em desenvolvimento. Em breve você poderá realizar consultas.</p>
                    <?php else: ?>
                            <?php foreach ($fields as $field): ?>
                                    <label for="<?php echo esc_attr($field['name']); ?>">
                                        <?php echo esc_html($field['label']); ?>:
                                        <?php if (!empty($field['required'])): ?>
                                                <span style="color: red;">*</span>
                                        <?php endif; ?>
                                    </label>
                                    <input
                                        type="<?php echo esc_attr($field['type'] ?? 'text'); ?>"
                                        id="<?php echo esc_attr($field['name']); ?>"
                                        name="<?php echo esc_attr($field['name']); ?>"
                                        placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
                                        <?php if (!empty($field['class'])): ?>
                                                class="<?php echo esc_attr($field['class']); ?>"
                                        <?php endif; ?>
                                        <?php if (!empty($field['required'])): ?>
                                                required
                                        <?php endif; ?>
                                    />
                            <?php endforeach; ?>
                            <button type="submit">
                                <i class="ph-bold ph-magnifying-glass"></i> Consultar
                            </button>
                            <div class="serc-result" style="margin-top:20px;"></div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="result-section">
            <!-- Results will appear here after form submission -->
        </div>
    </div>

    <!-- Category Sidebar for Quick Navigation -->
    <?php include plugin_dir_path(__FILE__) . 'includes/category-sidebar.php'; ?>
<?php if (!$is_ajax): ?>
</div>
</div>
<?php endif; ?>