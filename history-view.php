<?php
/**
 * View: History
 */
if (!defined('ABSPATH'))
    exit;

include plugin_dir_path(__FILE__) . 'includes/header.php';
include plugin_dir_path(__FILE__) . 'includes/sidebar.php';

// Fetch real history data from WordPress
$uid = get_current_user_id();
if (!$uid) {
    echo '<p>Faça login para visualizar o histórico.</p>';
    exit;
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


?>

<!-- MAIN CONTENT -->
<div class="area-content">
    <style>
        .history-container {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .history-header h2 {
            margin: 0;
            font-size: 24px;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-form {
            display: flex;
            gap: 15px;
            align-items: center;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-size: 12px;
            color: #666;
            font-weight: 500;
        }

        .filter-input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            color: #555;
            outline: none;
        }

        .filter-btn {
            background: var(--primary-green);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            align-self: flex-end;
            transition: all 0.2s;
        }

        .filter-btn:hover {
            background: #007a40;
            transform: translateY(-1px);
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .history-table th {
            text-align: left;
            padding: 15px;
            background: #f8f9fa;
            color: #666;
            font-weight: 600;
            font-size: 14px;
            border-bottom: 2px solid #eee;
        }

        .history-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            color: #444;
            vertical-align: middle;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-success {
            background: #e6f7ef;
            color: var(--primary-green);
        }

        .status-pending {
            background: #fff8e1;
            color: #f59e0b;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: #f0fdf4;
            color: var(--primary-green);
            border: 1px solid #d1f7e4;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: var(--primary-green);
            color: #fff;
            border-color: var(--primary-green);
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>

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
                                    <a href="#" class="action-btn">
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
</div>

<?php wp_footer(); ?>
</body>

</html>