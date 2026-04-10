<?php
/**
 * View: Order Detail (Detalhe do Pedido)
 * Displays a single WooCommerce order details within the dashboard
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
$order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
$order = $order_id ? wc_get_order($order_id) : null;

// Security: only allow the owner to view the order
if (!$order || $order->get_customer_id() !== $user_id) {
    if (!$is_ajax) {
        echo '<div class="area-content">';
    }
    ?>
    <div class="account-container">
        <div class="account-header">
            <h2><i data-lucide="alert-circle"></i> Pedido não encontrado</h2>
            <p class="account-description">O pedido solicitado não existe ou não pertence à sua conta.</p>
        </div>
        <a href="<?php echo serc_get_dashboard_url(['view' => 'orders']); ?>" class="btn-order-back">
            <i data-lucide="arrow-left"></i> Voltar para Meus Pedidos
        </a>
    </div>
    <?php
    if (!$is_ajax) {
        echo '</div></div>';
    }
    return;
}

if (!$is_ajax) {
    echo '<div class="area-content">';
}

// Helper: status labels
$status_labels = [
    'pending' => 'Pendente',
    'processing' => 'Processando',
    'on-hold' => 'Aguardando',
    'completed' => 'Concluído',
    'cancelled' => 'Cancelado',
    'refunded' => 'Reembolsado',
    'failed' => 'Falhou',
];

$order_status = $order->get_status();
$status_label = $status_labels[$order_status] ?? ucfirst($order_status);
$status_class = 'status-' . $order_status;
$order_date = $order->get_date_created();
$order_items = $order->get_items();
$subtotal = $order->get_subtotal();
$shipping_total = $order->get_shipping_total();
$discount_total = $order->get_discount_total();
$order_total = $order->get_total();
$payment_method = $order->get_payment_method_title();
$order_notes = wc_get_order_notes(['order_id' => $order_id, 'type' => 'customer']);
?>

<div class="account-container order-detail-container">

    <!-- Header -->
    <div class="account-header">
        <div class="order-detail-header-row">
            <div>
                <h2><i data-lucide="receipt"></i> Pedido #<?php echo $order_id; ?></h2>
                <p class="account-description">
                    Realizado em <?php echo $order_date ? $order_date->date('d/m/Y \à\s H:i') : '—'; ?>
                </p>
            </div>
            <div class="order-detail-header-actions">
                <span class="order-status-badge <?php echo esc_attr($status_class); ?> order-status-badge--lg">
                    <?php echo esc_html($status_label); ?>
                </span>
                <a href="<?php echo serc_get_dashboard_url(['view' => 'orders']); ?>" class="btn-order-back">
                    <i data-lucide="arrow-left"></i> Voltar
                </a>
            </div>
        </div>
    </div>

    <!-- Order Items Table -->
    <div class="order-detail-section">
        <h3 class="order-detail-section-title"><i data-lucide="package"></i> Itens do Pedido</h3>
        <table class="orders-table order-items-table">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Qtd.</th>
                    <th>Preço Unit.</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $item_id => $item):
                    $product = $item->get_product();
                    $product_name = $item->get_name();
                    $qty = $item->get_quantity();
                    $line_total = $item->get_total();
                    $unit_price = $qty > 0 ? $line_total / $qty : 0;
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($product_name); ?></strong>
                            <?php if ($product && $product->get_sku()): ?>
                                <br><small class="order-sku">SKU: <?php echo esc_html($product->get_sku()); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($qty); ?></td>
                        <td>R$ <?php echo number_format($unit_price, 2, ',', '.'); ?></td>
                        <td><strong>R$ <?php echo number_format($line_total, 2, ',', '.'); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Order Summary + Details grid -->
    <div class="order-detail-grid">

        <!-- Left: Totals -->
        <div class="order-detail-section">
            <h3 class="order-detail-section-title"><i data-lucide="calculator"></i> Resumo do Pedido</h3>
            <div class="order-totals-box">
                <div class="order-total-row">
                    <span>Subtotal</span>
                    <span>R$ <?php echo number_format($subtotal, 2, ',', '.'); ?></span>
                </div>
                <?php if ($shipping_total > 0): ?>
                    <div class="order-total-row">
                        <span>Frete</span>
                        <span>R$ <?php echo number_format($shipping_total, 2, ',', '.'); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($discount_total > 0): ?>
                    <div class="order-total-row order-total-row--discount">
                        <span>Desconto</span>
                        <span>- R$ <?php echo number_format($discount_total, 2, ',', '.'); ?></span>
                    </div>
                <?php endif; ?>
                <div class="order-total-row order-total-row--grand">
                    <span>Total</span>
                    <span>R$ <?php echo number_format($order_total, 2, ',', '.'); ?></span>
                </div>
                <?php if ($payment_method): ?>
                    <div class="order-total-row order-payment-method">
                        <span>Pagamento</span>
                        <span><?php echo esc_html($payment_method); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Billing address -->
        <div class="order-detail-section">
            <h3 class="order-detail-section-title"><i data-lucide="map-pin"></i> Endereço de Cobrança</h3>
            <div class="order-address-box">
                <?php
                $billing_address = $order->get_formatted_billing_address();
                if ($billing_address) {
                    echo wp_kses_post($billing_address);
                } else {
                    echo '<p class="order-no-address">Endereço não informado.</p>';
                }
                ?>
                <?php if ($order->get_billing_phone()): ?>
                    <p class="order-address-phone">
                        <i data-lucide="phone"></i>
                        <?php echo esc_html($order->get_billing_phone()); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- .order-detail-grid -->

    <!-- Order Notes (customer-facing) -->
    <?php if (!empty($order_notes)): ?>
        <div class="order-detail-section">
            <h3 class="order-detail-section-title"><i data-lucide="message-circle"></i> Atualizações do Pedido</h3>
            <ul class="order-notes-list">
                <?php foreach (array_reverse($order_notes) as $note): ?>
                    <li class="order-note-item">
                        <span class="order-note-date">
                            <?php echo date_i18n('d/m/Y H:i', strtotime($note->date_created)); ?>
                        </span>
                        <p class="order-note-text"><?php echo wp_kses_post($note->content); ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

</div><!-- .order-detail-container -->

<?php if (!$is_ajax): ?>
    </div><!-- .area-content -->
    </div><!-- .dashboard-wrapper -->
<?php endif; ?>