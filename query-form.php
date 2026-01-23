<?php
/**
 * View: Query Form (Dynamic query execution page)
 */
if (!defined('ABSPATH'))
    exit;

include plugin_dir_path(__FILE__) . 'includes/header.php';
include plugin_dir_path(__FILE__) . 'includes/sidebar.php';
require_once plugin_dir_path(__FILE__) . 'includes/integrations-config.php';

// Get integration from URL parameter
$integration_id = $_GET['integration'] ?? '';
$integration = serc_get_integration_by_id($integration_id);

// Redirect if integration not found
if (!$integration) {
    header('Location: ?p=dashboard');
    exit;
}
?>

<!-- MAIN CONTENT -->
<div class="area-content">
    <style>
        /* Query Form Specific Styles */
        .query-container {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            border: 1px solid #eee;
        }

        .query-header {
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }

        .query-breadcrumb {
            font-size: 13px;
            color: #777;
            margin-bottom: 12px;
        }

        .query-breadcrumb a {
            color: var(--primary-green);
            text-decoration: none;
        }

        .query-breadcrumb a:hover {
            text-decoration: underline;
        }

        .query-title {
            font-size: 28px;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .query-title i {
            color: var(--primary-green);
            font-size: 32px;
        }

        .query-description {
            color: #666;
            font-size: 15px;
        }

        .query-meta {
            display: flex;
            gap: 30px;
            margin-top: 15px;
        }

        .query-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #555;
        }

        .query-meta-item i {
            color: var(--primary-green);
        }

        .query-meta-item strong {
            color: var(--primary-green);
            font-weight: 600;
        }

        .query-form-section {
            margin-top: 30px;
        }

        .query-form-section h3 {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 20px 0;
        }

        .form-wrapper {
            background: #f9f9f9;
            padding: 25px;
            border-radius: 8px;
            border: 1px solid #eee;
        }

        /* Enhance form styles from shortcodes */
        .form-wrapper form {
            max-width: 600px;
        }

        .form-wrapper label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }

        .form-wrapper input,
        .form-wrapper select,
        .form-wrapper textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: var(--font-main);
            font-size: 14px;
            margin-bottom: 16px;
            background: #fff;
        }

        .form-wrapper input:focus,
        .form-wrapper select:focus,
        .form-wrapper textarea:focus {
            outline: none;
            border-color: var(--primary-green);
        }

        .form-wrapper button[type="submit"] {
            background: var(--primary-green) !important;
            color: #fff !important;
            border: none !important;
            padding: 12px 30px !important;
            border-radius: 6px !important;
            font-weight: 500 !important;
            cursor: pointer !important;
            font-size: 15px !important;
            margin-top: 10px !important;
        }

        .form-wrapper button[type="submit"]:hover {
            background: #007a41 !important;
        }

        .result-section {
            margin-top: 30px;
        }

        .result-section h3 {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 15px 0;
        }
    </style>

    <!-- Load jQuery and scripts for AJAX functionality -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?php echo plugin_dir_url(__FILE__); ?>jQuery-Mask-Plugin-master/dist/jquery.mask.min.js"></script>
    <script>
        // Inject AJAX configuration for preview environment
        var serc_ajax = {
            ajax_url: '/preview.php?ajax=1',
            nonce: 'preview_nonce'
        };
    </script>
    <script src="<?php echo plugin_dir_url(__FILE__); ?>serc-frontend.js"></script>

    <div class="query-container">
        <div class="query-header">
            <div class="query-breadcrumb">
                <a href="?p=dashboard">Dashboard</a> /
                <a href="?p=consulta&type=<?php echo esc_attr($integration_id); ?>">Consultas</a> /
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
                    <img src="img/credit.svg" alt="Ícone Créditos" style="width: 18px; height: 18px; vertical-align: middle;">
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
</div>
</div>


<?php wp_footer(); ?>
</body>

</html>