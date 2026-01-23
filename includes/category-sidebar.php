<?php
/**
 * Category Sidebar Component
 * Displays category tabs and integration list for quick navigation
 */

if (!defined('ABSPATH'))
    exit;

require_once plugin_dir_path(__FILE__) . 'integrations-config.php';

// Get all integrations
$all_integrations = serc_get_integrations_config();

// Get current integration ID if on query page
$current_integration_id = $_GET['integration'] ?? '';
$current_category = '';

// Determine current category based on current integration
if ($current_integration_id) {
    foreach ($all_integrations as $cat_key => $integrations) {
        foreach ($integrations as $integration) {
            if ($integration['id'] === $current_integration_id) {
                $current_category = $cat_key;
                break 2;
            }
        }
    }
}

// Default to 'cpf' if no category found
if (!$current_category) {
    $current_category = 'cpf';
}

$category_labels = [
    'cpf' => 'CPF',
    'cnpj' => 'CNPJ',
    'veicular' => 'Veicular',
    'juridico' => 'Jurídico'
];
?>

<style>
    .category-sidebar {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
        border: 1px solid #eee;
        position: sticky;
        top: 20px;
        max-height: calc(100vh - 100px);
        overflow-y: auto;
    }

    .category-sidebar h3 {
        margin: 0 0 20px 0;
        font-size: 18px;
        font-weight: 600;
        color: #1a1a1a;
    }

    .sidebar-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .sidebar-tab {
        padding: 8px 16px;
        background: #f5f5f5;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        color: #666;
        cursor: pointer;
        transition: all 0.2s;
    }

    .sidebar-tab:hover {
        background: #e8f5e9;
        color: var(--primary-green);
        border-color: var(--primary-green);
    }

    .sidebar-tab.active {
        background: var(--primary-green);
        color: #fff;
        border-color: var(--primary-green);
    }

    .sidebar-search {
        position: relative;
        margin-bottom: 20px;
    }

    .sidebar-search input {
        width: 100%;
        padding: 10px 15px 10px 35px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 13px;
        font-family: var(--font-main);
    }

    .sidebar-search input:focus {
        outline: none;
        border-color: var(--primary-green);
    }

    .sidebar-search i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
        font-size: 14px;
    }

    .sidebar-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .sidebar-item {
        padding: 12px;
        border: 1px solid #eee;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .sidebar-item:hover {
        border-color: var(--primary-green);
        background: #f9fffe;
        transform: translateX(2px);
    }

    .sidebar-item.active {
        border-color: var(--primary-green);
        background: #f0fdf4;
    }

    .sidebar-item-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 6px;
    }

    .sidebar-item-icon {
        color: var(--primary-green);
        font-size: 18px;
        flex-shrink: 0;
    }

    .sidebar-item-name {
        font-size: 14px;
        font-weight: 500;
        color: #1a1a1a;
        flex: 1;
    }

    .sidebar-item-desc {
        font-size: 12px;
        color: #666;
        line-height: 1.4;
        margin: 0 0 8px 0;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .sidebar-item-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .sidebar-item-value {
        font-size: 11px;
        color: #777;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .sidebar-item-btn {
        background: var(--primary-green);
        color: #fff;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .sidebar-item-btn:hover {
        background: #007a41;
    }

    .sidebar-item.active .sidebar-item-btn {
        background: #007a41;
    }
</style>

<div class="category-sidebar">
    <h3>Categorias</h3>

    <!-- Category Tabs -->
    <div class="sidebar-tabs">
        <?php foreach ($category_labels as $cat_key => $cat_label): ?>
            <button class="sidebar-tab <?php echo ($cat_key === $current_category) ? 'active' : ''; ?>"
                data-category="<?php echo esc_attr($cat_key); ?>"
                onclick="switchCategory('<?php echo esc_attr($cat_key); ?>')">
                <?php echo esc_html($cat_label); ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Search -->
    <div class="sidebar-search">
        <i class="ph ph-magnifying-glass"></i>
        <input type="text" id="sidebar-search-input" placeholder="Buscar consulta..."
            onkeyup="filterSidebarItems(this.value)">
    </div>

    <!-- Integration Lists (one per category) -->
    <?php foreach ($all_integrations as $cat_key => $integrations): ?>
        <div class="sidebar-list" data-category="<?php echo esc_attr($cat_key); ?>"
            style="<?php echo ($cat_key !== $current_category) ? 'display: none;' : ''; ?>">
            <?php foreach ($integrations as $integration): ?>
                <a href="?p=query&integration=<?php echo esc_attr($integration['id']); ?>"
                    class="sidebar-item <?php echo ($integration['id'] === $current_integration_id) ? 'active' : ''; ?>"
                    data-name="<?php echo esc_attr(strtolower($integration['name'])); ?>"
                    data-desc="<?php echo esc_attr(strtolower($integration['description'])); ?>">

                    <div class="sidebar-item-header">
                        <i class="sidebar-item-icon <?php echo esc_attr($integration['icon'] ?? 'ph-file-text'); ?>"></i>
                        <span class="sidebar-item-name">
                            <?php echo esc_html($integration['name']); ?>
                        </span>
                    </div>

                    <p class="sidebar-item-desc">
                        <?php echo esc_html($integration['description']); ?>
                    </p>

                    <div class="sidebar-item-footer">
                        <span class="sidebar-item-value">
                            <img src="img/credit.svg" alt="Ícone Créditos"
                                style="width: 14px; height: 14px; vertical-align: middle;">
                            <?php echo esc_html($integration['value']); ?> créditos
                        </span>
                        <button class="sidebar-item-btn"
                            onclick="event.preventDefault(); window.location.href=this.parentElement.parentElement.href;">
                            Consultar
                        </button>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>

<script>
    function switchCategory(category) {
        // Update tab active state
        document.querySelectorAll('.sidebar-tab').forEach(tab => {
            tab.classList.toggle('active', tab.dataset.category === category);
        });

        // Show/hide integration lists
        document.querySelectorAll('.sidebar-list').forEach(list => {
            list.style.display = list.dataset.category === category ? 'flex' : 'none';
        });

        // Clear search
        document.getElementById('sidebar-search-input').value = '';
    }

    function filterSidebarItems(searchTerm) {
        searchTerm = searchTerm.toLowerCase().trim();

        // Get visible list
        const visibleList = document.querySelector('.sidebar-list[style*="flex"]') ||
            document.querySelector('.sidebar-list:not([style*="none"])');

        if (!visibleList) return;

        const items = visibleList.querySelectorAll('.sidebar-item');

        items.forEach(item => {
            const name = item.dataset.name || '';
            const desc = item.dataset.desc || '';
            const matches = name.includes(searchTerm) || desc.includes(searchTerm);
            item.style.display = matches ? 'block' : 'none';
        });
    }
</script>