<?php
/**
 * View: Category List (Lists all queries for a specific category)
 */
if (!defined('ABSPATH'))
    exit;

include plugin_dir_path(__FILE__) . 'includes/header.php';
include plugin_dir_path(__FILE__) . 'includes/sidebar.php';
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
?>

<!-- MAIN CONTENT -->
<div class="area-content" style="grid-template-columns: 1fr;">
    <style>
        /* Category View Specific Styles */
        .category-header {
            margin-bottom: 30px;
        }

        .category-title {
            font-size: 24px;
            font-weight: 600;
            margin: 0 0 20px 0;
            color: #1a1a1a;
        }

        .category-toolbar {
            display: flex;
            gap: 20px;
            align-items: center;
            margin-bottom: 30px;
        }

        .category-search {
            flex: 1;
            position: relative;
            max-width: 400px;
        }

        .category-search input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: var(--font-main);
            font-size: 14px;
            background: #fff;
        }

        .category-search i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .btn-consultar-green {
            background: var(--primary-green);
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .category-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 0;
        }

        .category-tab {
            padding: 12px 20px;
            background: none;
            border: none;
            font-size: 14px;
            font-weight: 500;
            color: #666;
            cursor: pointer;
            position: relative;
            border-bottom: 2px solid transparent;
        }

        .category-tab:hover {
            color: var(--primary-green);
        }

        .category-tab.active {
            color: var(--primary-green);
            border-bottom-color: var(--primary-green);
        }

        /* Integration List Table */
        .integration-table {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #eee;
        }

        .integration-header {
            display: grid;
            grid-template-columns: 3fr 1fr 1fr 1fr;
            padding: 16px 24px;
            background: #f9f9f9;
            font-size: 13px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .integration-row {
            display: grid;
            grid-template-columns: 3fr 1fr 1fr 1fr;
            padding: 20px 24px;
            border-top: 1px solid #f0f0f0;
            align-items: center;
            transition: background 0.2s;
        }

        .integration-row:hover {
            background: #fafafa;
        }

        .integration-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .integration-icon {
            width: 48px;
            height: 48px;
            background: #e6f7ef;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .integration-icon i {
            font-size: 24px;
            color: var(--primary-green);
        }

        .integration-details {
            flex: 1;
        }

        .integration-name {
            font-size: 15px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 4px;
        }

        .integration-description {
            font-size: 13px;
            color: #777;
        }

        .integration-value {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 15px;
            font-weight: 600;
            color: var(--primary-green);
        }

        .integration-value i {
            font-size: 18px;
        }

        .integration-type {
            font-size: 13px;
            color: #666;
        }

        .integration-date {
            font-size: 13px;
            color: #999;
        }
    </style>

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
            <a href="?p=query&integration=<?php echo esc_attr($integration['id']); ?>"
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
                        <img src="img/credit.svg" alt="Ícone Créditos"
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
</div>
</div>

<?php wp_footer(); ?>
</body>

</html>