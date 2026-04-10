<?php
/**
 * View: History
 * Supports both full page load and AJAX partial loading
 */
if (!defined('ABSPATH'))
    exit;

// Check if this is an AJAX request
global $serc_ajax_request;
$is_ajax = !empty($serc_ajax_request);

// Only include header/sidebar for full page loads
if (!$is_ajax) {
    include plugin_dir_path(__FILE__) . 'includes/header.php';
    include plugin_dir_path(__FILE__) . 'includes/sidebar.php';
    echo '<div class="area-content">';
}

// Pre-calculate user stats
$user_id = get_current_user_id();
$credits = serc_get_user_credits();
$today_queries = serc_get_today_query_count($user_id);
$favorites = get_user_meta($user_id, 'serc_favorite_integrations', true);
$fav_count = is_array($favorites) ? count($favorites) : 0;

// Filter parameters
$paged = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;
$de = isset($_GET['de']) ? sanitize_text_field($_GET['de']) : '';
$ate = isset($_GET['ate']) ? sanitize_text_field($_GET['ate']) : '';
$type = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';

$args = array(
    'post_type' => 'serc_consulta',
    'post_status' => 'private',
    'author' => $user_id,
    'posts_per_page' => 10,
    'paged' => $paged,
    'orderby' => 'date',
    'order' => 'DESC'
);

if ($type) {
    $args['meta_query'] = array(array('key' => 'type', 'value' => $type, 'compare' => '='));
}

if ($de || $ate) {
    $args['date_query'] = array(array('after' => $de ?: null, 'before' => $ate ?: null, 'inclusive' => true));
}

$query = new WP_Query($args);
$consultas = [];
if ($query->have_posts()) {
    while ($query->have_posts()) {
        $query->the_post();
        $pid = get_the_ID();
        $consultas[] = [
            'ID' => $pid,
            'date' => get_the_date('Y-m-d H:i:s'),
            'type' => get_post_meta($pid, 'type', true),
            'status' => get_post_meta($pid, 'upload_status', true) ? 'Concluído' : 'Processando',
            'filename' => get_post_meta($pid, 'filename', true),
        ];
    }
    wp_reset_postdata();
}
?>

<div class="dashboard-redesign">
    <!-- 1. Header Area -->
    <div class="dash-header-row">
        <div class="dash-header-texts">
            <h1 class="dash-title">Histórico de Consultas</h1>
            <p class="dash-subtitle">Visualize e baixe os resultados de todas as suas pesquisas anteriores.</p>
        </div>
    </div>

    <!-- 4. History Table Area -->
    <div class="dash-pane pane-full-width">
        <!-- Modern Filters -->
        <form method="get" class="history-filters-inline">
            <input type="hidden" name="p" value="history">
            <div class="filter-field">
                <label class="filter-label">Início</label>
                <input type="date" name="de" value="<?php echo esc_attr($de); ?>" class="filter-date-input">
            </div>
            <div class="filter-field">
                <label class="filter-label">Fim</label>
                <input type="date" name="ate" value="<?php echo esc_attr($ate); ?>" class="filter-date-input">
            </div>
            <div class="filter-field">
                <label class="filter-label">Tipo</label>
                <select name="tipo" class="filter-select-input">
                    <option value="">Todos</option>
                    <option value="cpf" <?php selected($type, 'cpf'); ?>>CPF</option>
                    <option value="cnpj" <?php selected($type, 'cnpj'); ?>>CNPJ</option>
                    <option value="veicular" <?php selected($type, 'veicular'); ?>>Veicular</option>
                </select>
            </div>
            <button type="submit" class="btn-consultar-small filter-submit-btn">
                <i data-lucide="filter"></i> Filtrar
            </button>
        </form>

        <div class="integration-table">
            <div class="integration-header" style="grid-template-columns: 2fr 1fr 1fr 1fr;">
                <span>Data / Tipo</span>
                <span>ID do Registro</span>
                <span>Status</span>
                <span style="text-align: right;">Ações</span>
            </div>

            <?php if (empty($consultas)): ?>
                <div class="alert-info-minimal" style="margin-top: 20px;">Nenhuma consulta registrada nos filtros selecionados.</div>
            <?php else: ?>
                <?php foreach ($consultas as $consulta): ?>
                    <div class="history-item-row">
                        <div class="history-item-info">
                            <span class="history-item-date"><?php echo date('d/m/Y H:i', strtotime($consulta['date'])); ?></span>
                            <span class="history-item-type"><?php echo strtoupper(esc_html($consulta['type'])); ?></span>
                        </div>
                        <div class="history-item-id">
                            <span class="badge-value-tag">#<?php echo esc_html($consulta['ID']); ?></span>
                        </div>
                        <div class="history-item-status">
                            <?php
                            $isDone = (stripos($consulta['status'], 'concluído') !== false);
                            $class = $isDone ? 'status-success' : 'status-pending';
                            ?>
                            <span class="status-badge <?php echo $class; ?>" style="font-size: 11px;">
                                <i data-lucide="<?php echo $isDone ? 'check-circle' : 'hourglass'; ?>" style="width:12px;height:12px;margin-right:4px;"></i>
                                <?php echo esc_html($consulta['status']); ?>
                            </span>
                        </div>
                        <div class="history-item-action">
                            <?php if (!empty($consulta['filename'])): ?>
                                <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=serc_download&hash=' . serc_consulta_ensure_hash($consulta['ID']))); ?>" class="btn-consultar-small" title="Baixar PDF">
                                    <i data-lucide="download"></i>
                                </a>
                            <?php else: ?>
                                <span class="type-pill">Processando</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($query->max_num_pages > 1): ?>
            <div class="shop-pagination" style="margin-top: 30px; display: flex; gap: 8px; justify-content: center;">
                <?php for ($i = 1; $i <= $query->max_num_pages; $i++): ?>
                    <a href="?p=history&pg=<?php echo $i; ?>" class="shop-page-link <?php echo ($i == $paged) ? 'current' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!$is_ajax): ?>
    </div> <!-- .area-content -->
    </div> <!-- .dashboard-wrapper -->
<?php endif; ?>