<?php
/**
 * View: History
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

// Fetch real history data from WordPress
$uid = get_current_user_id();
if (!$uid) {
    echo '<p>Faça login para visualizar o histórico.</p>';
    if (!$is_ajax)
        exit;
    return;
}

$paged = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;
$de = isset($_GET['de']) ? sanitize_text_field($_GET['de']) : '';
$ate = isset($_GET['ate']) ? sanitize_text_field($_GET['ate']) : '';
$type = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';

$args = array(
    'post_type' => 'serc_consulta',
    'post_status' => 'private',
    'author' => $uid,
    'posts_per_page' => 10,
    'paged' => $paged,
    'orderby' => 'date',
    'order' => 'DESC'
);

// Filter by Type
if ($type) {
    $args['meta_query'] = array(
        array(
            'key' => 'type',
            'value' => $type,
            'compare' => '='
        )
    );
}

// Filter by Date
if ($de || $ate) {
    $args['date_query'] = array(
        array(
            'after' => $de ? $de : null,
            'before' => $ate ? $ate : null,
            'inclusive' => true
        )
    );
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

if (!$is_ajax) {
    echo '<div class="area-content">';
}
?>

<div class="history-container">
    <div class="history-header">
        <h2>
            <i class="ph-duotone ph-clock-counter-clockwise"></i>
            Histórico de Consultas
        </h2>
    </div>

    <form method="get" class="filter-form">
        <input type="hidden" name="p" value="history">

        <div class="filter-group">
            <label>Data Início</label>
            <input type="date" name="de" class="filter-input" value="<?php echo esc_attr($de); ?>">
        </div>

        <div class="filter-group">
            <label>Data Fim</label>
            <input type="date" name="ate" class="filter-input" value="<?php echo esc_attr($ate); ?>">
        </div>

        <div class="filter-group">
            <label>Tipo de Consulta</label>
            <select name="tipo" class="filter-input">
                <option value="">Todos os tipos</option>
                <option value="cpf" <?php echo ($type === 'cpf') ? 'selected' : ''; ?>>Pessoa Física (CPF)</option>
                <option value="cnpj" <?php echo ($type === 'cnpj') ? 'selected' : ''; ?>>Pessoa Jurídica (CNPJ)
                </option>
                <option value="veicular" <?php echo ($type === 'veicular') ? 'selected' : ''; ?>>Veicular</option>
            </select>
        </div>

        <button type="submit" class="filter-btn">
            <i class="ph-bold ph-funnel"></i> Filtrar
        </button>
    </form>

    <table class="history-table">
        <thead>
            <tr>
                <th>Data/Hora</th>
                <th>Tipo de Consulta</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($consultas)): ?>
                <tr>
                    <td colspan="4" class="empty-state">
                        <i class="ph-duotone ph-magnifying-glass"
                            style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                        Nenhuma consulta encontrada no histórico.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($consultas as $consulta): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 500;">
                                <?php echo date('d/m/Y', strtotime($consulta['date'])); ?>
                            </div>
                            <div style="font-size: 12px; color: #888;">
                                <?php echo date('H:i', strtotime($consulta['date'])); ?>
                            </div>
                        </td>
                        <td>
                            <strong>
                                <?php echo esc_html($consulta['type']); ?>
                            </strong>
                            <div style="font-size: 12px; color: #888;">ID: #
                                <?php echo esc_html($consulta['ID']); ?>
                            </div>
                        </td>
                        <td>
                            <?php
                            $statusClass = (stripos($consulta['status'], 'concluído') !== false) ? 'status-success' : 'status-pending';
                            $statusIcon = (stripos($consulta['status'], 'concluído') !== false) ? 'ph-check-circle' : 'ph-hourglass';
                            ?>
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <i class="ph-fill <?php echo $statusIcon; ?>" style="margin-right: 4px;"></i>
                                <?php echo esc_html($consulta['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($consulta['filename'])): ?>
                                <?php
                                $dl_hash = serc_consulta_ensure_hash($consulta['ID']);
                                $dl_url = admin_url('admin-ajax.php?action=serc_download&hash=' . $dl_hash);
                                ?>
                                <a href="<?php echo esc_url($dl_url); ?>" class="action-btn">
                                    <i class="ph-bold ph-download-simple"></i>
                                    Download PDF
                                </a>
                            <?php else: ?>
                                <span style="color: #999; font-size: 13px;">
                                    Processando...
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Mock Pagination (Visual Only) -->
    <?php if (!empty($consultas)): ?>
        <div style="display: flex; justify-content: center; margin-top: 25px; gap: 10px;">
            <button class="filter-btn" style="background: #eee; color: #999; cursor: not-allowed;" disabled>
                <i class="ph-bold ph-caret-left"></i> Anterior
            </button>
            <button class="filter-btn" style="background: var(--primary-green);">
                1
            </button>
            <button class="filter-btn" style="background: #fff; color: #555; border: 1px solid #ddd;">
                2
            </button>
            <button class="filter-btn" style="background: #fff; color: #555; border: 1px solid #ddd;">
                Próximo <i class="ph-bold ph-caret-right"></i>
            </button>
        </div>
    <?php endif; ?>
</div>
<?php if (!$is_ajax): ?>
    </div>
<?php endif; ?>