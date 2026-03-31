<?php
/**
 * View: Shop (Loja)
 * Lists WooCommerce products for credit purchase
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
$current_category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$limit = 12;
?>

<div class="dashboard-redesign">
    <!-- 1. Header Area -->
    <div class="dash-header-row">
        <div class="dash-header-texts">
            <h1 class="dash-title">Loja de Créditos</h1>
            <p class="dash-subtitle">Adquira pacotes de créditos para realizar suas consultas com agilidade.</p>
        </div>
    </div>

    <!-- 4. Main Shop Area -->
    <div class="dash-pane pane-full-width">
        <div class="shop-layout-modern" style="display: grid; grid-template-columns: 240px 1fr; gap: 40px;">
            <!-- Filters Sidebar -->
            <aside class="shop-sidebar-refined">
                <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 20px; color: var(--text-color);">Categorias</h3>
                <ul class="shop-categories-list" style="list-style: none; padding:0; margin:0; display: flex; flex-direction: column; gap: 8px;">
                    <li>
                        <a href="?view=shop" class="shop-cat-btn <?php echo empty($current_category) ? 'active' : ''; ?>" data-category="">
                            Todos os Pacotes
                        </a>
                    </li>
                    <?php
                    if (class_exists('WooCommerce')) {
                        $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true]);
                        if (!empty($terms) && !is_wp_error($terms)) {
                            foreach ($terms as $term) {
                                $is_active = ($current_category === $term->slug) ? 'active' : '';
                                echo '<li><a href="?view=shop&category=' . esc_attr($term->slug) . '" class="shop-cat-btn ' . $is_active . '" data-category="' . esc_attr($term->slug) . '">';
                                echo esc_html($term->name) . ' <span class="cat-count">' . esc_html($term->count) . '</span>';
                                echo '</a></li>';
                            }
                        }
                    }
                    ?>
                </ul>
            </aside>

            <!-- Product Grid -->
            <div class="shop-products-main">
                <div class="shop-grid">
                    <?php
                    if (!class_exists('WooCommerce')) {
                        echo '<div class="alert-info-minimal">WooCommerce não está ativo.</div>';
                    } else {
                        $args = array('status' => 'publish', 'limit' => $limit, 'page' => $paged, 'paginate' => true, 'orderby' => 'menu_order', 'order' => 'ASC');
                        if (!empty($current_category)) $args['category'] = array($current_category);
                        $results = wc_get_products($args);
                        $products = $results->products;
                        $total_pages = $results->max_num_pages;

                        if (empty($products)) {
                            echo '<div class="alert-info-minimal">Nenhum produto encontrado.</div>';
                        } else {
                            foreach ($products as $product) :
                                $image_url = wp_get_attachment_url($product->get_image_id());
                                ?>
                                <div class="shop-card">
                                    <div class="shop-card__image-wrap">
                                        <?php if ($product->is_on_sale()) echo '<span class="shop-card__badge">Oferta</span>'; ?>
                                        <div class="shop-card__image">
                                            <?php if ($image_url): ?>
                                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($product->get_name()); ?>">
                                            <?php else: ?>
                                                <div class="shop-card__placeholder"><i data-lucide="package"></i></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="shop-card__info">
                                        <h3 class="shop-card__title"><?php echo esc_html($product->get_name()); ?></h3>
                                        <div class="shop-card__price"><?php echo $product->get_price_html(); ?></div>
                                        <a href="<?php echo esc_url($product->get_permalink()); ?>" class="shop-card__btn" target="_blank">
                                            <i data-lucide="shopping-cart"></i> Comprar
                                        </a>
                                    </div>
                                </div>
                                <?php
                            endforeach;
                        }
                    }
                    ?>
                </div>

                <!-- Pagination -->
                <?php if (isset($total_pages) && $total_pages > 1): ?>
                    <div class="shop-pagination" style="margin-top: 40px; display: flex; gap: 8px;">
                        <?php for ($i = 1; $i <= $total_pages; $i++) {
                            $is_current = ($i == $paged) ? 'current' : '';
                            echo '<a href="?view=shop&paged=' . $i . '" class="shop-page-link ' . $is_current . '" data-paged="' . $i . '">' . $i . '</a>';
                        } ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!$is_ajax): ?>
    </div> <!-- .area-content -->
    </div> <!-- .dashboard-wrapper -->
<?php endif; ?>
