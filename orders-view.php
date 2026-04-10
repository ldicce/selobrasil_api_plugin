<?php
/**
 * View: Orders (WooCommerce Orders List)
 * Displays user's WooCommerce orders within the dashboard
 */
if (!defined('ABSPATH'))
    exit;

global $serc_ajax_request;
$is_ajax = !empty($serc_ajax_request);

if (!$is_ajax) {
    include plugin_dir_path(__FILE__) . 'includes/header.php';
    include plugin_dir_path(__FILE__) . 'includes/sidebar.php';
}

if (!is_user_logged_in()) {
    if (!$is_ajax) {
        wp_redirect(home_url());
        exit;
    }
    echo '<div class="error-message">Você precisa estar logado.</div>';
    return;
}

$user_id = get_current_user_id();

if (!$is_ajax) {
    echo '<div class="area-content">';
}
?>

<div class="account-container orders-container">
    <div class="account-header">
        <h2><i data-lucide="package"></i> Meus Pedidos</h2>
        <p class="account-description">Acompanhe seus pedidos e histórico de compras</p>
    </div>

    <div class="orders-table-wrapper">
        <?php
        // Get user's orders
        $customer_orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'limit' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
            'status' => array('wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed', 'wc-cancelled', 'wc-refunded', 'wc-failed'),
        ));

        if (!empty($customer_orders)): ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Data</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customer_orders as $order):
                        $order_id = $order->get_id();
                        $order_date = $order->get_date_created();
                        $order_status = $order->get_status();
                        $order_total = $order->get_total();

                        // Status label mapping
                        $status_labels = array(
                            'pending' => 'Pendente',
                            'processing' => 'Processando',
                            'on-hold' => 'Aguardando',
                            'completed' => 'Concluído',
                            'cancelled' => 'Cancelado',
                            'refunded' => 'Reembolsado',
                            'failed' => 'Falhou',
                        );
                        $status_label = isset($status_labels[$order_status]) ? $status_labels[$order_status] : ucfirst($order_status);

                        // Status badge class
                        $status_class = 'status-' . $order_status;
                        ?>
                        <tr>
                            <td><strong>#<?php echo $order_id; ?></strong></td>
                            <td><?php echo $order_date ? $order_date->date('d/m/Y') : '—'; ?></td>
                            <td><span class="order-status-badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span></td>
                            <td>R$ <?php echo number_format(floatval($order_total), 2, ',', '.'); ?></td>
                            <td>
                                <a href="<?php echo esc_url(serc_get_dashboard_url(['view' => 'order-detail', 'order_id' => $order_id])); ?>" class="btn-order-view serc-nav-link" data-view="order-detail" data-order-id="<?php echo $order_id; ?>">
                                    <i data-lucide="eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="orders-empty">
                <i data-lucide="package" class="orders-empty-icon"></i>
                <p>Você ainda não possui pedidos.</p>
                <a href="<?php echo serc_get_dashboard_url(['view' => 'shop']); ?>" class="btn-shop-now">
                    <i data-lucide="store"></i> Ir para a Loja
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!$is_ajax): ?>
</div>
</div>
<?php endif; ?>
