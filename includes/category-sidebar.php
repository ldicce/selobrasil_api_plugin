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


<div class="sidebar-wrapper">
    <div class="sidebar-search">
        <div class="search-box">
            <i class="ph-magnifying-glass"></i>
            <input type="text" id="sidebar-search-input" placeholder="Buscar consulta..."
                onkeyup="filterSidebarItems(this.value)">
        </div>
    </div>

    <div class="sidebar-tabs">
        <?php foreach ($category_labels as $key => $label): ?>
            <button class="sidebar-tab <?php echo $key === $current_category ? 'active' : ''; ?>"
                onclick="switchCategory('<?php echo $key; ?>')" data-category="<?php echo $key; ?>">
                <?php echo $label; ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="sidebar-content">
        <?php foreach ($all_integrations as $cat_key => $integrations): ?>
            <div class="sidebar-list" data-category="<?php echo $cat_key; ?>"
                style="display: <?php echo $cat_key === $current_category ? 'flex' : 'none'; ?>;">
                <?php foreach ($integrations as $integration): ?>
                    <a href="<?php echo serc_get_dashboard_url(['view' => 'query', 'integration' => $integration['id']]); ?>"
                        class="sidebar-item <?php echo isset($_GET['integration']) && $_GET['integration'] === $integration['id'] ? 'active' : ''; ?>"
                        data-name="<?php echo strtolower($integration['name']); ?>"
                        data-desc="<?php echo strtolower($integration['description']); ?>">

                        <div class="sidebar-item-header">
                            <div class="sidebar-icon">
                                <i class="<?php echo $integration['icon'] ?? 'ph-puzzle-piece'; ?>"></i>
                            </div>
                            <div class="sidebar-info">
                                <span class="sidebar-item-title"><?php echo esc_html($integration['name']); ?></span>
                            </div>
                        </div>

                        <div class="sidebar-item-footer">
                            <span class="sidebar-item-value">
                                <img src="<?php echo plugins_url('assets/img/credit.svg', dirname(__DIR__) . '/serpro-cnpj-quotas.php'); ?>"
                                    alt="Ícone Créditos" style="width: 14px; height: 14px; vertical-align: middle;">
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