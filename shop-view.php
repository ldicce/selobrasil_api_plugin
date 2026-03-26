<?php
/**
 * View: Shop (Loja)
 * Lists WooCommerce products for credit purchase
 * Supports both full page load and AJAX partial loading
 */
if (!defined('ABSPATH'))
    exit;

// Require user to be logged in
if (!is_user_logged_in()) {
    wp_redirect('/login');
    exit;
}

// Check if this is an AJAX request
global $serc_ajax_request;
$is_ajax = !empty($serc_ajax_request);

// Only include header/sidebar for full page loads
if (!$is_ajax) {
    include plugin_dir_path(__FILE__) . 'includes/header.php';
    include plugin_dir_path(__FILE__) . 'includes/sidebar.php';
    echo '<div class="area-content" style="grid-template-columns: 1fr;">';
}
?>

<div class="shop-container">
    <div class="shop-header">
        <h2>
            <i class="ph-fill ph-storefront"></i>
            Loja de Créditos
        </h2>
        <p class="shop-description">Adquira créditos para realizar suas consultas. Escolha o pacote ideal para você.</p>
    </div>

    <div class="shop-grid">
        <?php
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            echo '<div class="shop-empty"><i class="ph-duotone ph-storefront" style="font-size:48px; margin-bottom:15px; display:block; color:#ccc;"></i>WooCommerce não está ativo. Ative o plugin WooCommerce para exibir os produtos.</div>';
        } else {
            // Query WooCommerce products
            $args = array(
                'status' => 'publish',
                'limit' => -1,
                'orderby' => 'menu_order',
                'order' => 'ASC',
            );
            $products = wc_get_products($args);

            if (empty($products)) {
                echo '<div class="shop-empty"><i class="ph-duotone ph-package" style="font-size:48px; margin-bottom:15px; display:block; color:#ccc;"></i>Nenhum produto disponível no momento.</div>';
            } else {
                foreach ($products as $product) :
                    $product_id = $product->get_id();
                    $image_url = wp_get_attachment_url($product->get_image_id());
                    $placeholder = plugin_dir_url(__FILE__) . 'assets/img/credit.svg';
                    $price_html = $product->get_price_html();
                    $permalink = $product->get_permalink();
                    $short_desc = $product->get_short_description();
                    $regular_price = $product->get_regular_price();
                    $sale_price = $product->get_sale_price();
                    $is_on_sale = $product->is_on_sale();
                    ?>
                    <div class="shop-card">
                        <?php if ($is_on_sale): ?>
                            <span class="shop-card__badge">Oferta</span>
                        <?php endif; ?>

                        <div class="shop-card__image">
                            <?php if ($image_url): ?>
                                <img src="<?php echo esc_url($image_url); ?>"
                                    alt="<?php echo esc_attr($product->get_name()); ?>">
                            <?php else: ?>
                                <div class="shop-card__placeholder">
                                    <i class="ph-duotone ph-package" style="font-size: 48px; color: #ccc;"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="shop-card__info">
                            <h3 class="shop-card__title"><?php echo esc_html($product->get_name()); ?></h3>

                            <?php if ($short_desc): ?>
                                <p class="shop-card__desc"><?php echo wp_strip_all_tags($short_desc); ?></p>
                            <?php endif; ?>

                            <div class="shop-card__price">
                                <?php echo $price_html; ?>
                            </div>

                            <a href="<?php echo esc_url($permalink); ?>" class="shop-card__btn" target="_blank">
                                <i class="ph-bold ph-shopping-cart"></i> Comprar
                            </a>
                        </div>
                    </div>
                    <?php
                endforeach;
            }
        }
        ?>
    </div>
</div>

<?php if (!$is_ajax): ?>
    </div>
    </div>
<?php endif; ?>
