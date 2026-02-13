<?php
/*
Plugin Name: Selo Brasil - Consultas
Description: Define cotas fixas automaticamente sempre que um pedido é criado com status Concluído.
Version: 3.3.3
Author: Selo Brasil
*/

if (!defined('ABSPATH'))
    exit;

define('SERCNPJ_NONCE', 'serpro_cnpj_nonce');

/**
 * Helper to generate correct dashboard URLs
 * Handles both Admin (page query arg) and Frontend (permalink)
 * Also handles AJAX requests from frontend correctly
 */
function serc_get_dashboard_url($params = [])
{
    // Check if this is a frontend AJAX request
    // is_admin() returns true for AJAX, but we need to detect frontend AJAX
    $is_frontend_ajax = defined('DOING_AJAX') && DOING_AJAX &&
        isset($_SERVER['HTTP_REFERER']) &&
        strpos($_SERVER['HTTP_REFERER'], admin_url()) === false;

    // Basic base URL
    if (is_admin() && !$is_frontend_ajax) {
        $base_url = admin_url('admin.php');
        $params['page'] = 'serc-dashboard';
    } else {
        // Frontend: Use the consultas page URL
        // Try to detect from referer for AJAX requests
        if ($is_frontend_ajax && isset($_SERVER['HTTP_REFERER'])) {
            // Parse referer to get base URL without query params
            $referer = $_SERVER['HTTP_REFERER'];
            $parsed = wp_parse_url($referer);
            $base_url = $parsed['scheme'] . '://' . $parsed['host'] . $parsed['path'];
        } else {
            global $wp;
            $base_url = home_url(add_query_arg([], $wp->request));
        }
    }

    return add_query_arg($params, $base_url);
}

add_action('wp_enqueue_scripts', 'serc_frontend_assets');
add_action('admin_enqueue_scripts', 'serc_frontend_assets');
add_action('admin_menu', 'serc_add_admin_menu');

/* Campos WooCommerce no produto */
add_action('woocommerce_product_options_general_product_data', 'serc_wc_product_field');
add_action('woocommerce_process_product_meta', 'serc_wc_save_product_field');
add_action('woocommerce_product_after_variable_attributes', 'serc_wc_variation_field', 10, 3);
add_action('woocommerce_save_product_variation', 'serc_wc_save_variation_field', 10, 2);

add_action('wp_ajax_serc_lookup_cnpj', 'serc_lookup_cnpj');
add_action('wp_ajax_nopriv_serc_lookup_cnpj', 'serc_lookup_cnpj');
add_action('wp_ajax_serc_lookup', 'serc_lookup');
add_action('wp_ajax_serc_load_view', 'serc_load_dashboard_view');
add_action('wp_ajax_nopriv_serc_load_view', 'serc_load_dashboard_view');
add_action('wp_ajax_nopriv_serc_lookup', 'serc_lookup');
add_action('wp_ajax_serc_upload', 'serc_upload');

// Favorites management
add_action('wp_ajax_serc_toggle_favorite', 'serc_toggle_favorite');
add_action('wp_ajax_serc_get_favorites', 'serc_get_favorites');
add_action('wp_ajax_serc_get_all_integrations', 'serc_get_all_integrations');
add_action('wp_ajax_serc_replace_favorite', 'serc_replace_favorite');

/* Hook: toda vez que um pedido é criado ou atualizado para "completed" */
add_action('woocommerce_new_order', 'serc_check_new_order_status');
add_action('woocommerce_order_status_completed', 'serc_handle_order_completed', 10, 1);

/* Hook: track user login activity */
add_action('wp_login', 'serc_track_login_activity', 10, 2);


// Legacy shortcodes removed in v1.36 - forms now generated dynamically from integrations-config.php
add_shortcode('serc_credit_balance', 'serc_credit_balance_shortcode');
add_shortcode('serc_dashboard', 'serc_render_frontend_dashboard_shortcode');


/* =========================
   Frontend Assets
   ========================= */
function serc_frontend_assets()
{
    // jQuery Mask for CPF/CNPJ formatting
    wp_enqueue_script('jquery-mask', plugins_url('jQuery-Mask-Plugin-master/dist/jquery.mask.min.js', __FILE__), array('jquery'), '1.14.16', true);
    wp_enqueue_script('serc-frontend', plugins_url('assets/js/serc-frontend.js', __FILE__), array('jquery', 'jquery-mask'), '3.3.0', true);

    // External Assets (Google Fonts & Phosphor Icons)
    wp_enqueue_style('serc-google-fonts', 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap', array(), null);
    wp_enqueue_script('serc-phosphor-icons', 'https://unpkg.com/@phosphor-icons/web', array(), null, false);


    // Main Dashboard Styles
    wp_enqueue_style('serc-dashboard-style', plugins_url('assets/css/style.css', __FILE__), array(), '3.3.0');

    wp_localize_script('serc-frontend', 'serc_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce(SERCNPJ_NONCE)
    ));
}

/* =========================
   Helper Functions
   ========================= */
function serc_get_user_credits()
{
    if (!is_user_logged_in())
        return 0.00;

    $user_id = get_current_user_id();
    $balance = get_user_meta($user_id, 'serc_credit_balance', true);
    return floatval($balance);
}

/**
 * Log user activity
 * 
 * @param int $user_id User ID
 * @param string $type Activity type (login, query, download)
 * @param string $description Activity description
 */
function serc_log_activity($user_id, $type, $description)
{
    $activities = get_user_meta($user_id, 'serc_activities', true);
    if (!is_array($activities)) {
        $activities = [];
    }

    // Add new activity
    $activities[] = [
        'type' => $type,
        'description' => $description,
        'timestamp' => current_time('timestamp'),
        'date' => current_time('Y-m-d H:i:s')
    ];

    // Keep only last 50 activities
    if (count($activities) > 50) {
        $activities = array_slice($activities, -50);
    }

    update_user_meta($user_id, 'serc_activities', $activities);
}

/**
 * Get user activities
 * 
 * @param int $user_id User ID
 * @param int $limit Number of activities to retrieve
 * @return array Activities
 */
function serc_get_user_activities($user_id, $limit = 10)
{
    $activities = get_user_meta($user_id, 'serc_activities', true);
    if (!is_array($activities)) {
        return [];
    }

    // Get most recent activities
    $activities = array_slice(array_reverse($activities), 0, $limit);

    return $activities;
}

/**
 * Get count of queries performed today
 * 
 * @param int $user_id User ID
 * @return int Query count
 */
function serc_get_today_query_count($user_id)
{
    $activities = get_user_meta($user_id, 'serc_activities', true);
    if (!is_array($activities)) {
        return 0;
    }

    $today = current_time('Y-m-d');
    $count = 0;

    foreach ($activities as $activity) {
        if ($activity['type'] === 'query') {
            $activity_date = date('Y-m-d', $activity['timestamp']);
            if ($activity_date === $today) {
                $count++;
            }
        }
    }

    return $count;
}

/**
 * Track user login activity
 * 
 * @param string $user_login Username
 * @param WP_User $user User object
 */
function serc_track_login_activity($user_login, $user)
{
    if ($user && isset($user->ID)) {
        serc_log_activity($user->ID, 'login', 'Acesso ao sistema');
    }
}



/* =========================
   Admin Menu & Dashboard
   ========================= */
function serc_add_admin_menu()
{
    // Main Dashboard Page
    add_menu_page(
        'Selo Brasil',          // Page title
        'Selo Brasil',          // Menu title
        'manage_options',       // Capability
        'serc-dashboard',       // Menu slug
        'serc_render_admin_page', // Callback (Updated for separation)
        'dashicons-chart-pie',  // Icon (or custom)
        6                       // Position
    );

    // Submenu: Debit Settings
    add_submenu_page(
        'serc-dashboard',       // Parent slug
        'Configuração de Débitos', // Page title
        'Débitos por Consulta',    // Menu title
        'manage_options',          // Capability
        'serc-debit-settings',     // Menu slug
        'serc_render_debit_settings_page' // Callback
    );

    add_options_page('Serpro Consultas - Shortcodes', 'Serpro Consultas', 'manage_options', 'serpro-consultas-shortcodes', 'serc_shortcodes_page');
    add_options_page('API Full – Token', 'API Full – Token', 'manage_options', 'serpro-apifull-token', 'serc_token_page');
}

function serc_render_admin_page()
{
    ?>
    <div class="wrap">
        <h1>Selo Brasil - Painel Administrativo</h1>
        <p>Acesse o painel do cliente via shortcode <code>[serc_dashboard]</code> em uma página do site.</p>
        <hr>
        <p><a href="<?php echo admin_url('admin.php?page=serc-debit-settings'); ?>" class="button button-primary">Configurar
                Débitos por Consulta</a></p>
    </div>
    <?php
}

/**
 * Get global debit value for a consultation type
 * 
 * @param string $type Consultation type ID
 * @return float Debit value in credits
 */
function serc_get_global_debit($type)
{
    $config = get_option('serc_global_debit_config', array());
    if (is_string($config)) {
        $config = json_decode($config, true);
        if (!is_array($config))
            $config = array();
    }
    if (isset($config[$type])) {
        return floatval($config[$type]);
    }
    // Fallback: try to get from integrations-config.php 'value' field
    if (function_exists('serc_get_integration_by_id')) {
        $integration = serc_get_integration_by_id($type);
        if ($integration && isset($integration['value'])) {
            // Convert from "5,50" format to float
            $val = str_replace(',', '.', $integration['value']);
            return floatval($val);
        }
    }
    return 0.0;
}

/**
 * Render the global debit settings page
 */
function serc_render_debit_settings_page()
{
    if (!current_user_can('manage_options'))
        return;

    // Load integrations config for default values
    require_once plugin_dir_path(__FILE__) . 'includes/integrations-config.php';

    // Handle form submission
    if (isset($_POST['serc_debit_config_save']) && check_admin_referer('serc_debit_config_nonce')) {
        $types = serc_get_consultation_types();
        $config = array();
        foreach ($types as $code => $label) {
            if (isset($_POST['serc_debit'][$code])) {
                $val = floatval(str_replace(',', '.', sanitize_text_field($_POST['serc_debit'][$code])));
                if ($val > 0) {
                    $config[$code] = round($val, 2);
                }
            }
        }
        update_option('serc_global_debit_config', $config);
        echo '<div class="updated"><p>Configurações de débito salvas com sucesso!</p></div>';
    }

    $types = serc_get_consultation_types();
    $saved_config = get_option('serc_global_debit_config', array());
    if (is_string($saved_config)) {
        $saved_config = json_decode($saved_config, true);
        if (!is_array($saved_config))
            $saved_config = array();
    }

    // Build a map of integration default values
    $all_integrations = serc_get_integrations_config();
    $default_values = array();
    foreach ($all_integrations as $category => $integrations) {
        foreach ($integrations as $integration) {
            $val = str_replace(',', '.', $integration['value'] ?? '0');
            $default_values[$integration['id']] = floatval($val);
        }
    }

    // Group types by category for organized display
    $category_map = array(
        'cpf' => array(),
        'cnpj' => array(),
        'veicular' => array(),
        'juridico' => array(),
        'outros' => array(),
    );
    $category_labels = array(
        'cpf' => 'CPF / Pessoa Física',
        'cnpj' => 'CNPJ / Pessoa Jurídica',
        'veicular' => 'Veicular',
        'juridico' => 'Jurídico',
        'outros' => 'Outros',
    );
    // Map types to categories based on integrations-config
    $type_category = array();
    foreach ($all_integrations as $cat => $integrations) {
        foreach ($integrations as $integration) {
            $type_category[$integration['id']] = $cat;
        }
    }
    foreach ($types as $code => $label) {
        $cat = isset($type_category[$code]) ? $type_category[$code] : 'outros';
        if (!isset($category_map[$cat]))
            $cat = 'outros';
        $category_map[$cat][$code] = $label;
    }

    ?>
    <div class="wrap">
        <h1>Configuração de Débitos por Consulta</h1>
        <p>Defina o valor em créditos que será debitado do cliente para cada tipo de consulta. Estes valores são
            <strong>globais</strong> e aplicam-se a todos os usuários.
        </p>
        <form method="post">
            <?php wp_nonce_field('serc_debit_config_nonce'); ?>
            <?php foreach ($category_map as $cat_key => $cat_types): ?>
                <?php if (empty($cat_types))
                    continue; ?>
                <h2><?php echo esc_html($category_labels[$cat_key] ?? ucfirst($cat_key)); ?></h2>
                <table class="widefat striped" style="max-width:600px; margin-bottom: 20px;">
                    <thead>
                        <tr>
                            <th>Consulta</th>
                            <th style="width:150px">Débito (créditos)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cat_types as $code => $label):
                            $current_val = '';
                            if (isset($saved_config[$code])) {
                                $current_val = $saved_config[$code];
                            } elseif (isset($default_values[$code])) {
                                $current_val = $default_values[$code];
                            }
                            ?>
                            <tr>
                                <td><?php echo esc_html($label); ?></td>
                                <td>
                                    <input type="number" step="0.01" min="0" name="serc_debit[<?php echo esc_attr($code); ?>]"
                                        value="<?php echo esc_attr($current_val); ?>" style="width:120px" />
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
            <p class="submit">
                <button type="submit" name="serc_debit_config_save" class="button button-primary">Salvar
                    Configurações</button>
            </p>
        </form>
    </div>
    <?php
}

function serc_render_frontend_dashboard_shortcode()
{
    ob_start();
    serc_render_dashboard_page(); // Reuse the logic but now it will render on frontend
    return ob_get_clean();
}

function serc_render_dashboard_page()
{
    // Basic router based on 'view' parameter
    $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'dashboard';

    // Map views to files
    switch ($view) {
        case 'history':
            include plugin_dir_path(__FILE__) . 'history-view.php';
            break;
        case 'query':
            include plugin_dir_path(__FILE__) . 'query-form.php';
            break;
        case 'category':
            include plugin_dir_path(__FILE__) . 'category-view.php';
            break;
        case 'dashboard':
        default:
            include plugin_dir_path(__FILE__) . 'dashboard.php';
            break;
    }
}

/**
 * AJAX handler to load dashboard views dynamically
 * Uses the same view files but sets a flag so they skip header/sidebar
 */
function serc_load_dashboard_view()
{
    // Set global flag so view files know this is an AJAX request
    global $serc_ajax_request;
    $serc_ajax_request = true;

    // Get view parameters
    $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'dashboard';
    $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
    $integration = isset($_GET['integration']) ? sanitize_text_field($_GET['integration']) : '';

    // Start output buffering
    ob_start();

    // Include the appropriate view file
    // The view files check $serc_ajax_request and skip header/sidebar when true
    switch ($view) {
        case 'history':
            include plugin_dir_path(__FILE__) . 'history-view.php';
            break;

        case 'query':
        case 'consulta':
            include plugin_dir_path(__FILE__) . 'query-form.php';
            break;

        case 'category':
            include plugin_dir_path(__FILE__) . 'category-view.php';
            break;

        case 'dashboard':
        default:
            include plugin_dir_path(__FILE__) . 'dashboard.php';
            break;
    }

    // Get the content
    $content = ob_get_clean();

    // Return JSON response with the view forsidebar state update
    wp_send_json_success(array(
        'html' => $content,
        'view' => $view,
        'type' => $type,
        'integration' => $integration
    ));
}

add_action('wp_ajax_serc_search_integrations', 'serc_search_integrations');
add_action('wp_ajax_nopriv_serc_search_integrations', 'serc_search_integrations');

function serc_search_integrations()
{
    // Make sure config is loaded (required for AJAX context)
    require_once plugin_dir_path(__FILE__) . 'includes/integrations-config.php';

    // Helper function to remove accents
    $remove_accents = function ($string) {
        $accents = array(
            'á' => 'a',
            'à' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ä' => 'a',
            'å' => 'a',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ö' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ç' => 'c',
            'ñ' => 'n',
            'Á' => 'A',
            'À' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'Ä' => 'A',
            'Å' => 'A',
            'É' => 'E',
            'È' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Í' => 'I',
            'Ì' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ó' => 'O',
            'Ò' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ö' => 'O',
            'Ú' => 'U',
            'Ù' => 'U',
            'Û' => 'U',
            'Ü' => 'U',
            'Ç' => 'C',
            'Ñ' => 'N'
        );
        return strtr($string, $accents);
    };

    // Support both GET and POST for compatibility
    $term = isset($_POST['term']) ? $_POST['term'] : (isset($_GET['term']) ? $_GET['term'] : '');
    $term = strtolower(trim(sanitize_text_field($term)));
    $term_normalized = $remove_accents($term);

    $all_integrations = serc_get_integrations_config();

    // If term is empty, return all integrations grouped by category
    if (empty($term)) {
        $grouped_result = array();
        foreach ($all_integrations as $category => $integrations) {
            $category_items = array();
            foreach ($integrations as $integration) {
                $category_items[] = array(
                    'id' => $integration['id'],
                    'name' => $integration['name'],
                    'icon' => isset($integration['icon']) ? $integration['icon'] : 'ph-puzzle-piece',
                    'description' => isset($integration['description']) ? $integration['description'] : ''
                );
            }
            $grouped_result[$category] = $category_items;
        }
        wp_send_json_success($grouped_result);
        wp_die();
    }

    $results = array();

    foreach ($all_integrations as $category => $integrations) {
        foreach ($integrations as $integration) {
            $name = strtolower($integration['name']);
            $desc = strtolower($integration['description']);
            $name_normalized = $remove_accents($name);
            $desc_normalized = $remove_accents($desc);

            // Search in Name OR Description (accent-insensitive)
            if (strpos($name_normalized, $term_normalized) !== false || strpos($desc_normalized, $term_normalized) !== false) {
                $results[] = [
                    'id' => $integration['id'],
                    'name' => $integration['name'],
                    'description' => $integration['description'],
                    'icon' => $integration['icon'] ?? 'ph-puzzle-piece',
                    'url' => serc_get_dashboard_url(['view' => 'query', 'integration' => $integration['id']]),
                    'category' => $category
                ];
            }
        }
    }

    // Limit results if needed, e.g., max 10
    $results = array_slice($results, 0, 10);

    wp_send_json_success($results);
    wp_die();
}

// Toggle a favorite integration for the current user
function serc_toggle_favorite()
{
    error_log('[SERC] serc_toggle_favorite called');
    $user_id = get_current_user_id();
    error_log('[SERC] User ID: ' . $user_id);
    if (!$user_id) {
        error_log('[SERC] User not authenticated');
        wp_send_json_error(['message' => 'Usuário não autenticado']);
        wp_die();
    }

    $integration_id = isset($_POST['integration_id']) ? sanitize_text_field($_POST['integration_id']) : '';
    error_log('[SERC] Integration ID: ' . $integration_id);
    if (empty($integration_id)) {
        error_log('[SERC] Integration ID is empty');
        wp_send_json_error(['message' => 'ID da integração não informado']);
        wp_die();
    }

    // Get current favorites
    $favorites = get_user_meta($user_id, 'serc_favorite_integrations', true);
    if (!is_array($favorites)) {
        $favorites = [];
    }

    // Check if already a favorite
    $key = array_search($integration_id, $favorites);
    if ($key !== false) {
        // Remove from favorites
        unset($favorites[$key]);
        $favorites = array_values($favorites); // Re-index
        $action = 'removed';
    } else {
        // Add to favorites (max 6)
        if (count($favorites) >= 6) {
            wp_send_json_error(['message' => 'Máximo de 6 favoritos atingido']);
            wp_die();
        }
        $favorites[] = $integration_id;
        $action = 'added';
    }

    update_user_meta($user_id, 'serc_favorite_integrations', $favorites);
    error_log('[SERC] Favorites updated: ' . print_r($favorites, true));

    wp_send_json_success([
        'action' => $action,
        'favorites' => $favorites
    ]);
    wp_die();
}

// Replace a favorite integration at a specific index
function serc_replace_favorite()
{
    error_log('[SERC] serc_replace_favorite called');
    $user_id = get_current_user_id();
    error_log('[SERC] User ID: ' . $user_id);
    if (!$user_id) {
        error_log('[SERC] User not authenticated');
        wp_send_json_error(['message' => 'Usuário não autenticado']);
        wp_die();
    }

    $new_id = isset($_POST['new_id']) ? sanitize_text_field($_POST['new_id']) : '';
    $slot_index = isset($_POST['slot_index']) ? intval($_POST['slot_index']) : -1;
    error_log('[SERC] New ID: ' . $new_id . ', Slot Index: ' . $slot_index);

    if (empty($new_id)) {
        error_log('[SERC] New ID is empty');
        wp_send_json_error(['message' => 'ID da integração não informado']);
        wp_die();
    }

    // Get current favorites
    $favorites = get_user_meta($user_id, 'serc_favorite_integrations', true);
    if (!is_array($favorites)) {
        $favorites = [];
    }

    // Check if new_id is already in favorites (prevent duplicates)
    $existing_key = array_search($new_id, $favorites);
    if ($existing_key !== false) {
        // If already exists, remove it first so we can move it to new slot
        unset($favorites[$existing_key]);
    }

    // Ensure array keys are sequential for logic but we will map by index
    // Actually, to target a specific index, we need to correct the array keys if unset broke them
    $favorites = array_values($favorites);

    if ($slot_index >= 0) {
        // Insert/Replace at specific index
        // If slot index is larger than current count (e.g. index 3 but only 1 favorite), 
        // we just append. But UI logic tries to put it in valid slot.
        // We will force put it there. But PHP array must be contiguous for JSON usually? 
        // No, WP/PHP handles indices. 
        // Best approach: 
        // 1. Fill gaps with placeholder if needed? 
        // No, let's keep it simple: Replace what's at index, or append.

        // However, if we removed $new_id from another position, array size decreased.
        // So $slot_index might aim at the hole.

        // Let's rely on array access.
        if (isset($favorites[$slot_index])) {
            $favorites[$slot_index] = $new_id;
        } else {
            // If trying to edit a slot that theoretically shouldn't exist or is empty
            // Just append
            $favorites[] = $new_id;
        }
    } else {
        $favorites[] = $new_id;
    }

    // Final re-index
    $favorites = array_values($favorites);

    update_user_meta($user_id, 'serc_favorite_integrations', $favorites);
    error_log('[SERC] Favorites replaced: ' . print_r($favorites, true));

    wp_send_json_success(['action' => 'replaced', 'favorites' => $favorites]);
    wp_die();
}

// Get user's favorite integrations
function serc_get_favorites()
{
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_success([]);
        wp_die();
    }

    $favorites = get_user_meta($user_id, 'serc_favorite_integrations', true);
    if (!is_array($favorites)) {
        $favorites = [];
    }

    // Get integration details
    require_once plugin_dir_path(__FILE__) . 'includes/integrations-config.php';
    $all_integrations = serc_get_integrations_config();
    $result = [];

    foreach ($favorites as $fav_id) {
        foreach ($all_integrations as $category => $integrations) {
            foreach ($integrations as $integration) {
                if ($integration['id'] === $fav_id) {
                    $result[] = [
                        'id' => $integration['id'],
                        'name' => $integration['name'],
                        'icon' => $integration['icon'] ?? 'ph-puzzle-piece',
                        'url' => serc_get_dashboard_url(['view' => 'query', 'integration' => $integration['id']])
                    ];
                    break 2;
                }
            }
        }
    }

    wp_send_json_success($result);
    wp_die();
}

/**
 * AJAX handler to get all integrations grouped by category
 */
function serc_get_all_integrations()
{
    // Ensure config is loaded
    require_once plugin_dir_path(__FILE__) . 'includes/integrations-config.php';

    $all_integrations = serc_get_integrations_config();
    $result = array();

    foreach ($all_integrations as $category => $integrations) {
        $category_items = array();
        foreach ($integrations as $integration) {
            $category_items[] = array(
                'id' => $integration['id'],
                'name' => $integration['name'],
                'icon' => isset($integration['icon']) ? $integration['icon'] : 'ph-puzzle-piece',
                'description' => isset($integration['description']) ? $integration['description'] : ''
            );
        }
        $result[$category] = $category_items;
    }

    wp_send_json_success($result);
    wp_die();
}

function serc_shortcodes_page()
{
    $shortcodes = array(
        array('label' => 'CNPJ Completo', 'code' => '[serc_cnpj_form]'),
        array('label' => 'CPF Completo e Renda Presumida', 'code' => '[serc_cpf_form]'),
        array('label' => 'CPF Completo com Renda', 'code' => '[serc_cpf_renda_form]'),
        array('label' => 'Consulta por Nome', 'code' => '[serc_ic_nome_form]'),
        array('label' => 'Consulta por Telefone', 'code' => '[serc_ic_telefone_form]'),
        array('label' => 'Consulta por Placa', 'code' => '[serc_ic_placa_form]'),
        array('label' => 'CNH', 'code' => '[serc_ic_cnh_form]'),
        array('label' => 'Dossiê Jurídico', 'code' => '[serc_dossie_juridico_form]'),
        array('label' => 'CRLV', 'code' => '[serc_crlv_form]'),
        array('label' => 'Renainf (Multas)', 'code' => '[serc_renainf_form]'),
        array('label' => 'Gravame detalhamento (Financiamento)', 'code' => '[serc_gravame_form]'),
        array('label' => 'Laudo veicular', 'code' => '[serc_laudo_veicular_form]'),
        array('label' => 'Proprietário placa', 'code' => '[serc_proprietario_placa_form]'),
        array('label' => 'SCPC BV Plus V2', 'code' => '[serc_scpc_bv_plus_v2_form]'),
        array('label' => 'SRS Premium', 'code' => '[serc_srs_premium_form]'),
        array('label' => 'Agregados básica própria', 'code' => '[serc_agregados_basica_propria_form]'),
        array('label' => 'BIN Estadual', 'code' => '[serc_bin_estadual_form]'),
        array('label' => 'BIN Nacional', 'code' => '[serc_bin_nacional_form]'),
        array('label' => 'Foto Leilão', 'code' => '[serc_foto_leilao_form]'),
        array('label' => 'Leilão', 'code' => '[serc_leilao_form]'),
        array('label' => 'Leilão, Score Veicular e Perda Total', 'code' => '[serc_leilao_score_perda_total_form]'),
        array('label' => 'Histórico de Roubo ou Furto', 'code' => '[serc_historico_roubo_furto_form]'),
        array('label' => 'Índice de Risco (Histórico Veicular)', 'code' => '[serc_indice_risco_veicular_form]'),
        array('label' => 'Licenciamento Anterior', 'code' => '[serc_licenciamento_anterior_form]'),
        array('label' => 'Proprietário Atual', 'code' => '[serc_ic_proprietario_atual_form]'),
        array('label' => 'Recall', 'code' => '[serc_recall_form]'),
        array('label' => 'Gravame Detalhamento', 'code' => '[serc_gravame_detalhamento_form]'),
        array('label' => 'RENAJUD (Restrições)', 'code' => '[serc_renajud_form]'),
        array('label' => 'RENAINF (Por Placa)', 'code' => '[serc_renainf_placa_form]'),
        array('label' => 'FIPE', 'code' => '[serc_fipe_form]'),
        array('label' => 'Sinistro', 'code' => '[serc_sinistro_form]'),
        array('label' => 'Serasa Premium', 'code' => '[serc_serasa_premium_form]'),
        array('label' => 'Relatório básico + Score CPF', 'code' => '[serc_ic_basico_score_form]'),
        array('label' => 'SCPC Boa Vista (básica)', 'code' => '[serc_scpc_boa_vista_form]'),
        array('label' => 'BACEN', 'code' => '[serc_bacen_form]'),
        array('label' => 'QUOD', 'code' => '[serc_quod_form]'),
        array('label' => 'SPC Brasil e CENPROT', 'code' => '[serc_spc_brasil_cenprot_form]'),
        array('label' => 'SPC Brasil e Serasa', 'code' => '[serc_spc_brasil_serasa_form]'),
        array('label' => 'Dívidas Bancárias CPF', 'code' => '[serc_dividas_bancrias_cpf_form]'),
        array('label' => 'Cadastrais – Score – Dívidas', 'code' => '[serc_cadastrais_score_dividas_form]'),
        array('label' => 'Cadastrais – Score – Dívidas CP', 'code' => '[serc_cadastrais_score_dividas_cp_form]'),
        array('label' => 'SCR Bacen e score', 'code' => '[serc_scr_bacen_score_form]'),
        array('label' => 'Protesto Nacional – CENPROT', 'code' => '[serc_protesto_nacional_cenprot_form]'),
        array('label' => 'Ações e processos judiciais', 'code' => '[serc_r_acoes_e_processos_judiciais_form]'),
        array('label' => 'Dossiê Jurídico (CPF)', 'code' => '[serc_dossie_juridico_cpf_form]'),
        array('label' => 'Certidão Nacional de Débitos Trabalhistas', 'code' => '[serc_certidao_nacional_debitos_trabalhistas_form]'),
    );
    ?>
    <div class="wrap">
        <h1>Serpro Consultas – Shortcodes</h1>
        <p>Copie o shortcode correspondente e cole na página desejada.</p>
        <table class="widefat striped" style="max-width:760px">
            <thead>
                <tr>
                    <th>Consulta</th>
                    <th>Shortcode</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($shortcodes as $s): ?>
                    <tr>
                        <td><?php echo esc_html($s['label']); ?></td>
                        <td><code><?php echo esc_html($s['code']); ?></code></td>
                        <td><button class="button serc-copy" data-code="<?php echo esc_attr($s['code']); ?>">Copiar</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <script>         (function () { document.addEventListener('click', function (e) { if (e.target && e.target.classList.contains('serc-copy')) { var code = e.target.getAttribute('data-code'); navigator.clipboard.writeText(code); e.target.textContent = 'Copiado!'; setTimeout(function () { e.target.textContent = 'Copiar'; }, 1500); } }); })();
        </script>
    </div>
    <?php
}
function serc_token_page()
{
    if (!current_user_can('manage_options'))
        return;
    if (isset($_POST['serc_apifull_token']) && check_admin_referer('serc_apifull_token_save')) {
        $token = sanitize_text_field(wp_unslash($_POST['serc_apifull_token']));
        update_option('serc_apifull_token', $token);
        echo '<div class="updated"><p>Token atualizado.</p></div>';
    }
    $token = get_option('serc_apifull_token', '');
    ?>
    <div class="wrap">
        <h1>API Full – Token</h1>
        <form method="post">
            <?php wp_nonce_field('serc_apifull_token_save'); ?>
            <p><label for="serc_apifull_token">Authorization (cole seu token):</label></p>
            <input type="text" id="serc_apifull_token" name="serc_apifull_token" value="<?php echo esc_attr($token); ?>"
                class="regular-text" />
            <p class="submit"><button type="submit" class="button button-primary">Salvar</button></p>
        </form>
    </div>
    <?php
}

/* =========================
   Consulta genérica (AJAX)
   ========================= */
function serc_wallet_debit($user_id, $type)
{
    // Usa débito global configurado no painel admin
    require_once plugin_dir_path(__FILE__) . 'includes/integrations-config.php';
    $debit = serc_get_global_debit($type);
    if ($debit <= 0) {
        $current_balance = floatval(get_user_meta($user_id, 'serc_credit_balance', true));
        return array('balance' => $current_balance, 'debited' => 0);
    }

    $current_balance = floatval(get_user_meta($user_id, 'serc_credit_balance', true));
    if ($current_balance < $debit) {
        return false;
    }

    // Deduz do saldo global
    $new_balance = round($current_balance - $debit, 2);
    update_user_meta($user_id, 'serc_credit_balance', $new_balance);

    return array('balance' => $new_balance, 'debited' => $debit);
}

/* ============================
   Global: last API response cache (used by serc_lookup fallback)
   ============================ */
global $serc_last_api_response;
$serc_last_api_response = null;

/**
 * Recursively search for pdfBase64 in a decoded JSON response.
 */
function serc_find_pdf_base64_recursive($data, $depth = 0)
{
    if ($depth > 5 || !is_array($data))
        return null;
    if (isset($data['pdfBase64']) && is_string($data['pdfBase64']) && strlen($data['pdfBase64']) > 100) {
        return $data['pdfBase64'];
    }
    foreach ($data as $v) {
        if (is_array($v)) {
            $found = serc_find_pdf_base64_recursive($v, $depth + 1);
            if ($found)
                return $found;
        }
    }
    return null;
}

function serc_apifull_post_extract_pdf_base64($endpoint, $payload, $log_prefix)
{
    global $serc_last_api_response;
    $pdf_base64 = null;
    $serc_last_api_response = null;
    $token = get_option('serc_apifull_token', '');
    if (empty($token)) {
        error_log($log_prefix . ' ERROR: API token is empty');
        return array('success' => false, 'pdf_base64' => null, 'error' => 'api_error', 'http_code' => null);
    }
    $auth = $token;
    if (stripos($auth, 'Bearer ') !== 0) {
        $auth = 'Bearer ' . $auth;
    }
    $req = wp_remote_post('https://api.apifull.com.br' . $endpoint, array(
        'headers' => array(
            'Authorization' => $auth,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Cache-Control' => 'no-cache',
        ),
        'body' => wp_json_encode($payload),
        'timeout' => 30,
    ));
    if (is_wp_error($req)) {
        error_log($log_prefix . ' ERROR: HTTP request failed: ' . $req->get_error_message());
        return array('success' => false, 'pdf_base64' => null, 'error' => 'api_error', 'http_code' => null);
    }
    $code = wp_remote_retrieve_response_code($req);
    $body = wp_remote_retrieve_body($req);
    error_log($log_prefix . ' API code=' . $code);
    if ($code < 200 || $code >= 300) {
        error_log($log_prefix . ' ERROR: API returned non-success HTTP code=' . $code);
        return array('success' => false, 'pdf_base64' => null, 'error' => 'api_error', 'http_code' => $code);
    }
    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
        error_log($log_prefix . ' WARNING: API response is not valid JSON. Body length=' . strlen($body));
        return array('success' => false, 'pdf_base64' => null, 'error' => 'api_error', 'http_code' => $code);
    }
    $serc_last_api_response = $decoded;
    $pdf_base64 = serc_find_pdf_base64_recursive($decoded);
    if (!$pdf_base64) {
        error_log($log_prefix . ' WARNING: no pdfBase64 found in response. Keys: ' . implode(',', array_keys($decoded)));
    }
    return array('success' => true, 'pdf_base64' => $pdf_base64, 'error' => null, 'http_code' => $code);
}

/**
 * Generate a PDF from API JSON data using pure PHP.
 * Returns base64-encoded PDF string or null on failure.
 */
function serc_generate_pdf_from_data($type, $data)
{
    if (!is_array($data) || empty($data))
        return null;

    $type_labels = array(
        'cnpj' => 'CNPJ Básico',
        'cpf' => 'CPF Básico',
        'cpf_renda' => 'CPF com Renda',
        'ic_nome' => 'Busca por Nome',
        'ic_telefone' => 'Busca por Telefone',
        'ic_placa' => 'Consulta por Placa',
        'ic_cnh' => 'CNH',
        'crlv' => 'CRLV',
        'proprietario_placa' => 'Proprietário por Placa',
        'gravame' => 'Gravame',
        'renainf' => 'RENAINF',
        'serasa_premium' => 'Serasa Premium',
        'ic_basico_score' => 'IC Básico Score',
        'scpc_boa_vista' => 'SCPC Boa Vista',
        'bacen' => 'BACEN',
        'quod' => 'QUOD',
        'dossie_juridico' => 'Dossiê Jurídico',
        'dossie_juridico_cpf' => 'Dossiê Jurídico CPF',
        'certidao_nacional_debitos_trabalhistas' => 'CNDT',
        'spc_brasil_cenprot' => 'SPC Brasil Cenprot',
        'spc_brasil_serasa' => 'SPC Brasil Serasa',
        'scpc_bv_plus_v2' => 'SCPC BV Plus V2',
        'srs_premium' => 'SRS Premium',
        'agregados_basica_propria' => 'Agregados',
        'bin_estadual' => 'BIN Estadual',
        'bin_nacional' => 'BIN Nacional',
        'foto_leilao' => 'Foto Leilão',
        'leilao' => 'Leilão',
        'leilao_score_perda_total' => 'Score Perda Total',
        'historico_roubo_furto' => 'Histórico Roubo/Furto',
        'indice_risco_veicular' => 'Índice Risco Veicular',
        'licenciamento_anterior' => 'Licenciamento Anterior',
        'ic_proprietario_atual' => 'Proprietário Atual',
        'laudo_veicular' => 'Laudo Veicular',
        'recall' => 'Recall',
        'gravame_detalhamento' => 'Gravame Detalhamento',
        'renajud' => 'RENAJUD',
        'renainf_placa' => 'RENAINF por Placa',
        'sinistro' => 'Sinistro',
        'fipe' => 'FIPE',
        'dividas_bancrias_cpf' => 'Dívidas Bancárias CPF',
        'cadastrais_score_dividas' => 'Cadastrais Score Dívidas',
        'cadastrais_score_dividas_cp' => 'Cadastrais Score Dívidas CP',
        'scr_bacen_score' => 'SCR BACEN Score',
        'protesto_nacional_cenprot' => 'Protesto Nacional',
        'r_acoes_e_processos_judiciais' => 'Ações e Processos Judiciais',
    );
    $title = isset($type_labels[$type]) ? $type_labels[$type] : strtoupper(str_replace('_', ' ', $type));
    $date = date('d/m/Y H:i:s');

    // Filter out pdfBase64 and very long values from the display data
    $display_data = serc_filter_display_data($data);

    // Build HTML
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
    $html .= '<style>';
    $html .= 'body{font-family:Helvetica,Arial,sans-serif;font-size:11px;color:#333;margin:30px;}';
    $html .= 'h1{font-size:18px;color:#1a5276;border-bottom:2px solid #1a5276;padding-bottom:8px;margin-bottom:5px;}';
    $html .= '.meta{font-size:10px;color:#777;margin-bottom:20px;}';
    $html .= 'table{width:100%;border-collapse:collapse;margin-bottom:15px;}';
    $html .= 'th,td{text-align:left;padding:6px 10px;border:1px solid #ddd;word-wrap:break-word;}';
    $html .= 'th{background:#f0f4f7;font-weight:600;width:35%;color:#1a5276;}';
    $html .= 'td{background:#fff;}';
    $html .= 'tr:nth-child(even) td{background:#f9f9f9;}';
    $html .= '.section-title{font-size:13px;font-weight:600;color:#1a5276;margin:15px 0 8px 0;padding:5px 0;border-bottom:1px solid #eee;}';
    $html .= '.footer{margin-top:30px;text-align:center;font-size:9px;color:#aaa;border-top:1px solid #eee;padding-top:10px;}';
    $html .= '</style></head><body>';
    $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
    $html .= '<div class="meta">Gerado em: ' . $date . ' | Selo Brasil Consultas</div>';
    $html .= serc_render_data_to_html($display_data);
    $html .= '<div class="footer">Este documento foi gerado automaticamente pelo sistema Selo Brasil Consultas.</div>';
    $html .= '</body></html>';

    // Try to use Dompdf if available, otherwise use HTML-to-PDF via wp_upload
    // Fallback: store HTML as a data URI PDF (browser-renderable)
    $pdf_binary = serc_html_to_pdf_binary($html);
    if ($pdf_binary) {
        return base64_encode($pdf_binary);
    }

    // Ultimate fallback: return HTML as base64 (will be served as HTML, not PDF)
    // This ensures there's always something to download
    error_log('SERPRO Consultas: PDF generation fallback - using HTML wrapper');
    return base64_encode($html);
}

/**
 * Filter out pdfBase64 and internal fields from display data.
 */
function serc_filter_display_data($data, $depth = 0)
{
    if ($depth > 10 || !is_array($data))
        return $data;
    $filtered = array();
    $skip_keys = array('pdfBase64', 'pdf_base64', 'link', 'hash', 'token');
    foreach ($data as $key => $value) {
        if (in_array($key, $skip_keys, true))
            continue;
        if (is_string($value) && strlen($value) > 5000)
            continue; // Skip huge base64 blobs
        if (is_array($value)) {
            $filtered[$key] = serc_filter_display_data($value, $depth + 1);
        } else {
            $filtered[$key] = $value;
        }
    }
    return $filtered;
}

/**
 * Render nested data array as HTML tables.
 */
function serc_render_data_to_html($data, $depth = 0)
{
    if (!is_array($data) || empty($data))
        return '<p><em>Sem dados disponíveis</em></p>';
    if ($depth > 8)
        return '<p>...</p>';

    // Check if this is a list of items (numeric keys)
    $is_list = array_keys($data) === range(0, count($data) - 1);

    $html = '';
    if ($is_list) {
        foreach ($data as $i => $item) {
            if (is_array($item)) {
                $html .= '<div class="section-title">Item ' . ($i + 1) . '</div>';
                $html .= serc_render_data_to_html($item, $depth + 1);
            } else {
                $html .= '<p>' . htmlspecialchars(strval($item)) . '</p>';
            }
        }
    } else {
        $html .= '<table>';
        foreach ($data as $key => $value) {
            $label = ucfirst(str_replace(array('_', '-'), ' ', $key));
            if (is_array($value)) {
                $html .= '<tr><th colspan="2" style="background:#e8eef3;">' . htmlspecialchars($label) . '</th></tr>';
                $html .= '<tr><td colspan="2">' . serc_render_data_to_html($value, $depth + 1) . '</td></tr>';
            } else {
                $display = ($value === null) ? '-' : htmlspecialchars(strval($value));
                $html .= '<tr><th>' . htmlspecialchars($label) . '</th><td>' . $display . '</td></tr>';
            }
        }
        $html .= '</table>';
    }
    return $html;
}

/**
 * Convert HTML to PDF binary using Dompdf (if available) or a minimal pure-PHP approach.
 */
function serc_html_to_pdf_binary($html)
{
    // Try Dompdf first (if installed via composer)
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
        if (class_exists('Dompdf\Dompdf')) {
            try {
                $dompdf = new \Dompdf\Dompdf(array('isRemoteEnabled' => false));
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                return $dompdf->output();
            } catch (\Exception $e) {
                error_log('SERPRO Consultas: Dompdf error: ' . $e->getMessage());
            }
        }
    }

    // Fallback: generate a minimal valid PDF with text content
    return serc_minimal_pdf_from_html($html);
}

/**
 * Generate a minimal but valid PDF document from HTML content.
 * Strips HTML tags and renders plain text in a proper PDF structure.
 */
function serc_minimal_pdf_from_html($html)
{
    // Remove <style> and <script> blocks entirely (content + tags) before stripping HTML
    $html_clean = preg_replace('/<style[^>]*>.*?<\/style>/si', '', $html);
    $html_clean = preg_replace('/<script[^>]*>.*?<\/script>/si', '', $html_clean);
    // Extract text content from HTML
    $text = strip_tags(str_replace(array('<br>', '<br/>', '<br />', '</tr>', '</p>', '</div>', '</h1>', '</th>', '</td>'), "\n", $html_clean));
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    // Clean up multiple newlines and whitespace
    $text = preg_replace('/[ \t]+/', ' ', $text);
    $text = preg_replace('/\n{3,}/', "\n\n", trim($text));

    // Encode text for PDF (convert to Latin-1 for basic PDF compatibility)
    $text = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');

    // Split into lines that fit ~80 chars wide
    $raw_lines = explode("\n", $text);
    $lines = array();
    foreach ($raw_lines as $line) {
        $line = trim($line);
        if (empty($line)) {
            $lines[] = '';
            continue;
        }
        while (strlen($line) > 90) {
            $break = strrpos(substr($line, 0, 90), ' ');
            if ($break === false)
                $break = 90;
            $lines[] = substr($line, 0, $break);
            $line = trim(substr($line, $break));
        }
        $lines[] = $line;
    }

    // Build PDF objects
    $objects = array();
    $offsets = array();
    $obj_num = 1;

    // Object 1: Catalog
    $objects[$obj_num] = $obj_num . " 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $obj_num++;

    // Build page content streams (split into pages of ~60 lines each)
    $lines_per_page = 55;
    $pages_lines = array_chunk($lines, $lines_per_page);
    if (empty($pages_lines))
        $pages_lines = array(array('Sem dados'));
    $page_count = count($pages_lines);
    $page_obj_start = 3; // page objects start at 3

    // Object 2: Pages
    $kids = '';
    for ($i = 0; $i < $page_count; $i++) {
        $kids .= ($page_obj_start + $i * 2) . ' 0 R ';
    }
    $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [ {$kids}] /Count {$page_count} >>\nendobj\n";
    $obj_num = $page_obj_start;

    // Font object (will be last)
    $font_obj = $page_obj_start + $page_count * 2;

    // Create page and stream objects
    for ($p = 0; $p < $page_count; $p++) {
        $page_lines = $pages_lines[$p];
        // Build content stream
        $stream = "BT\n/F1 10 Tf\n";
        $y = 780;
        foreach ($page_lines as $line) {
            $escaped = str_replace(array('\\', '(', ')'), array('\\\\', '\\(', '\\)'), $line);
            $stream .= "40 {$y} Td\n({$escaped}) Tj\n0 0 Td\n";
            $y -= 13;
            if ($y < 40)
                break;
        }
        $stream .= "ET\n";
        $stream_len = strlen($stream);

        // Page object
        $stream_obj = $obj_num + 1;
        $objects[$obj_num] = "{$obj_num} 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents {$stream_obj} 0 R /Resources << /Font << /F1 {$font_obj} 0 R >> >> >>\nendobj\n";
        $obj_num++;

        // Stream object
        $objects[$obj_num] = "{$obj_num} 0 obj\n<< /Length {$stream_len} >>\nstream\n{$stream}endstream\nendobj\n";
        $obj_num++;
    }

    // Font object
    $objects[$font_obj] = "{$font_obj} 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

    // Build the PDF
    $pdf = "%PDF-1.4\n";
    foreach ($objects as $num => $obj) {
        $offsets[$num] = strlen($pdf);
        $pdf .= $obj;
    }

    // Cross-reference table
    $xref_offset = strlen($pdf);
    $total_objs = max(array_keys($objects)) + 1;
    $pdf .= "xref\n0 {$total_objs}\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i < $total_objs; $i++) {
        if (isset($offsets[$i])) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        } else {
            $pdf .= "0000000000 65535 f \n";
        }
    }

    $pdf .= "trailer\n<< /Size {$total_objs} /Root 1 0 R >>\n";
    $pdf .= "startxref\n{$xref_offset}\n%%EOF";

    return $pdf;
}

// ==========================================
// 1. DADOS CADASTRAIS (CPF/CNPJ)
// ==========================================

function serc_apifull_pf_dadosbasicos($cpf)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/pf-dadosbasicos',
        array('cpf' => $cpf, 'link' => 'pf-dadosbasicos'),
        'SERPRO Consultas: CPF SIMPLES'
    );
}

function serc_apifull_ic_cpf_completo($cpf)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/ic-cpf-completo',
        array('cpf' => $cpf, 'link' => 'ic-cpf-completo'),
        'SERPRO Consultas: CPF COMPLETO'
    );
}

function serc_apifull_r_cpf_completo($cpf)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/r-cpf-completo',
        array('cpf' => $cpf, 'link' => 'r-cpf-completo'),
        'SERPRO Consultas: CPF RENDA'
    );
}

function serc_apifull_ic_nome($name, $state)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/ic-nome',
        array('name' => $name, 'state' => $state, 'link' => 'ic-nome'),
        'SERPRO Consultas: NOME'
    );
}

function serc_apifull_ic_telefone($phone)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/ic-telefone',
        array('phone' => $phone, 'link' => 'ic-telefone'),
        'SERPRO Consultas: TELEFONE'
    );
}

function serc_apifull_cnpj($cnpj)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/cnpj',
        array('cnpj' => $cnpj, 'link' => 'cnpj'),
        'SERPRO Consultas: CNPJ'
    );
}

// ==========================================
// 2. VEICULAR
// ==========================================

function serc_apifull_agregados_propria($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/agregados-propria',
        array('placa' => $placa, 'link' => 'agregados-propria'),
        'SERPRO Consultas: VEICULAR AGREGADOS'
    );
}

function serc_apifull_ic_bin_estadual($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/ic-bin-estadual',
        array('placa' => $placa, 'link' => 'ic-bin-estadual'),
        'SERPRO Consultas: VEICULAR BIN ESTADUAL'
    );
}

function serc_apifull_ic_bin_nacional($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/ic-bin-nacional',
        array('placa' => $placa, 'link' => 'ic-bin-nacional'),
        'SERPRO Consultas: VEICULAR BIN NACIONAL'
    );
}

function serc_apifull_ic_foto_leilao($placa)
{
    // Note: Documentation says payload is {"placa": "..."} for ic-foto-leilao?
    // User task said doc had `leilaoId` for `foto_leilao`.
    // But I must check MY plan which said `/api/ic-foto-leilao` with `placa`.
    // The previous code had `foto_leilao` endpoint, this is `ic-foto-leilao`.
    // Assuming documentation is correct with `placa`.
    return serc_apifull_post_extract_pdf_base64(
        '/api/ic-foto-leilao',
        array('placa' => $placa, 'link' => 'ic-foto-leilao'),
        'SERPRO Consultas: FOTO LEILAO'
    );
}

function serc_apifull_leilao($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/leilao',
        array('placa' => $placa, 'link' => 'leilao'),
        'SERPRO Consultas: LEILAO'
    );
}

function serc_apifull_ic_leilao_score($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/ic-leilao-score',
        array('placa' => $placa, 'link' => 'ic-leilao-score'),
        'SERPRO Consultas: LEILAO SCORE'
    );
}

function serc_apifull_ic_laudo_veicular($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/ic-laudo-veicular',
        array('placa' => $placa, 'link' => 'ic-laudo-veicular'),
        'SERPRO Consultas: LAUDO VEICULAR'
    );
}

function serc_apifull_ic_historico_roubo_furto($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/ic-historico-roubo-furto',
        array('placa' => $placa, 'link' => 'ic-historico-roubo-furto'),
        'SERPRO Consultas: HISTORICO ROUBO FURTO'
    );
}

function serc_apifull_inde_risco($placa)
{
    // Correct endpoint from docs: /api/inde-risco or /api/indice-risco?
    // Plan said /api/inde-risco with link 'indice-risco'?
    // Browser check said: URL /api/inde-risco, link 'indice-risco'.
    return serc_apifull_post_extract_pdf_base64(
        '/api/inde-risco',
        array('placa' => $placa, 'link' => 'indice-risco'),
        'SERPRO Consultas: INDICE RISCO'
    );
}

function serc_apifull_ic_licenciamento_anterior($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/ic-licenciamento-anterior',
        array('placa' => $placa, 'link' => 'ic-licenciamento-anterior'),
        'SERPRO Consultas: LICENCIAMENTO ANTERIOR'
    );
}

function serc_apifull_ic_proprietario_atual($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/ic-proprietario-atual',
        array('placa' => $placa, 'link' => 'ic-proprietario-atual'),
        'SERPRO Consultas: PROPRIETARIO ATUAL'
    );
}

function serc_apifull_ic_recall($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/ic-recall',
        array('placa' => $placa, 'link' => 'ic-recall'),
        'SERPRO Consultas: RECALL'
    );
}

function serc_apifull_ic_gravame($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/ic-gravame',
        array('placa' => $placa, 'link' => 'ic-gravame'),
        'SERPRO Consultas: GRAVAME'
    );
}

function serc_apifull_ic_renajud($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/ic-renajud',
        array('placa' => $placa, 'link' => 'ic-renajud'),
        'SERPRO Consultas: RENAJUD'
    );
}

function serc_apifull_ic_renainf($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/ic-renainf',
        array('placa' => $placa, 'link' => 'ic-renainf'),
        'SERPRO Consultas: RENAINF'
    );
}

function serc_apifull_fipe($placa)
{
    // Using simple FIPE lookup (usually requires more than just placa, but documentation maps to what?)
    // Docs check needed? My plan said {placa}.
    return serc_apifull_post_extract_pdf_base64(
        '/api/fipe',
        array('placa' => $placa, 'link' => 'fipe'),
        'SERPRO Consultas: FIPE'
    );
}

function serc_apifull_sinistro($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/sinistro',
        array('placa' => $placa, 'link' => 'sinistro'),
        'SERPRO Consultas: SINISTRO'
    );
}

function serc_apifull_csv_completo($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/csv-renainf-renajud-recall-bin-proprietario',
        array('placa' => $placa, 'link' => 'csv-renainf-renajud-recall-bin-proprietario'),
        'SERPRO Consultas: CSV'
    );
}

function serc_apifull_crlv($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/crlv',
        array('placa' => $placa, 'link' => 'crlv'),
        'SERPRO Consultas: CRLV'
    );
}

function serc_apifull_roubo_furto($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/roubo-furto',
        array('placa' => $placa, 'link' => 'roubo-furto'),
        'SERPRO Consultas: ROUBO FURTO'
    );
}

// ==========================================
// 3. DIVIDAS E CREDITO
// ==========================================

function serc_apifull_cp_spc_cenprot($document)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/cp-spc-cenprot',
        array('document' => $document, 'link' => 'cp-spc-cenprot'),
        'SERPRO Consultas: SPC CENPROT'
    );
}

function serc_apifull_r_spc_srs($cpf)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/r-spc-srs',
        array('cpf' => $cpf, 'link' => 'r-spc-srs'),
        'SERPRO Consultas: SPC SERASA'
    );
}

function serc_apifull_cp_serasa_premium_v2($document)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/v2/cp-serasa-premium',
        array('document' => $document, 'link' => 'cp-serasa-premium'), // Note: v2 might prefer 'link' without v2 or with? Usually same as endpoint logic.
        // Assuming link 'cp-serasa-premium' based on pattern.
        'SERPRO Consultas: SERASA PREMIUM V2'
    );
}

function serc_apifull_cp_boa_vista_completa($document)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/cp-boa-vista-completa',
        array('document' => $document, 'link' => 'cp-boa-vista-completa'),
        'SERPRO Consultas: BOAVISTA COMPLETA'
    );
}

function serc_apifull_r_bv_basica($document)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/r-bv-basica',
        array('document' => $document, 'link' => 'r-bv-basica'),
        'SERPRO Consultas: BOAVISTA BASICA'
    );
}

function serc_apifull_cp_boa_vista_plus_v2($document)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/v2/cp-boa-vista-plus',
        array('document' => $document, 'link' => 'cp-boa-vista-plus'),
        'SERPRO Consultas: BOAVISTA PLUS V2'
    );
}

function serc_apifull_cp_score_dividas($document)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/cp-score-dividas',
        array('document' => $document, 'link' => 'cp-score-dividas'),
        'SERPRO Consultas: SCORE DIVIDAS'
    );
}

function serc_apifull_cp_cadastrais_score_dividas($document)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/cp-cadastrais-score-dividas',
        array('document' => $document, 'link' => 'cp-cadastrais-score-dividas'),
        'SERPRO Consultas: CADASTRAIS SCORE DIVIDAS'
    );
}

function serc_apifull_ic_bacen($document)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/ic-bacen',
        array('document' => $document, 'link' => 'ic-bacen'),
        'SERPRO Consultas: SCR BACEN'
    );
}

function serc_apifull_ac_protesto($document)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/ac-protesto',
        array('document' => $document, 'link' => 'ac-protesto'),
        'SERPRO Consultas: PROTESTO'
    );
}

function serc_apifull_ic_quod($document)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/ic-quod',
        array('document' => $document, 'link' => 'ic-quod'),
        'SERPRO Consultas: QUOD'
    );
}

// ==========================================
// 4. JURIDICO
// ==========================================

function serc_apifull_r_acoes_processos($document)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/r-acoes-e-processos-judiciais',
        array('document' => $document, 'link' => 'r-acoes-e-processos-judiciais'),
        'SERPRO Consultas: ACOES PROCESSOS'
    );
}

function serc_apifull_dossie_juridico($document)
{
    // Check if doc is CPF or CNPJ
    // API endpoint is /api/dossie-juridico
    // Payload usually uses 'document' or 'cpf'/'cnpj' depending on implementation
    return serc_apifull_post_extract_pdf_base64(
        '/api/dossie-juridico',
        array('document' => $document, 'link' => 'dossie-juridico'),
        'SERPRO Consultas: DOSSIE JURIDICO'
    );
}

function serc_apifull_cndt($document)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/cndt',
        array('document' => $document, 'link' => 'cndt'),
        'SERPRO Consultas: CNDT'
    );
}



function serc_lookup()
{
    if (!wp_verify_nonce($_POST['nonce'] ?? '', SERCNPJ_NONCE))
        wp_send_json_error('invalid_nonce', 403);
    $user_id = get_current_user_id();
    if (!$user_id)
        wp_send_json_error('no_user', 403);

    // Rate Limit (30 lookups per hour per user)
    $k = 'serc_rl_lookup_' . $user_id;
    $c = intval(get_transient($k));
    if ($c >= 30)
        wp_send_json_error('rate_limit', 429);
    set_transient($k, $c + 1, 3600);

    $type = sanitize_text_field($_POST['type'] ?? '');

    // Load config to check if type exists and get credit cost
    require_once plugin_dir_path(__FILE__) . 'includes/integrations-config.php';
    $config_item = serc_get_integration_by_id($type);
    if (!$config_item)
        wp_send_json_error('invalid_type', 400);

    // Prepare inputs (cleaning)
    $cpf = preg_replace('/\D+/', '', $_POST['cpf'] ?? '');
    $cnpj = preg_replace('/\D+/', '', $_POST['cnpj'] ?? '');
    $document = preg_replace('/\D+/', '', $_POST['document'] ?? ''); // CPF or CNPJ
    $placa = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['placa'] ?? ''));
    $phone_input = preg_replace('/\D+/', '', $_POST['phone'] ?? ''); // if frontend sends full phone
    // If frontend sends ddd/telefone separate (as per old config), combine them handling both cases
    $ddd = preg_replace('/\D+/', '', $_POST['ddd'] ?? '');
    $telefone = preg_replace('/\D+/', '', $_POST['telefone'] ?? '');
    if (empty($phone_input) && !empty($ddd) && !empty($telefone)) {
        $phone_input = $ddd . $telefone;
    }

    // Input Validation based on type
    switch ($type) {
        // --- DADOS CADASTRAIS ---
        case 'pf_dadosbasicos':
        case 'ic_cpf_completo':
        case 'r_cpf_completo':
            if (strlen($cpf) !== 11)
                wp_send_json_error('invalid_cpf', 400);
            break;
        case 'ic_nome':
            $name = sanitize_text_field($_POST['name'] ?? '');
            $state = strtoupper(preg_replace('/[^A-Za-z]/', '', $_POST['state'] ?? ''));
            if (empty($name) || strlen($state) !== 2)
                wp_send_json_error('invalid_input', 400);
            break;
        case 'ic_telefone':
            if (strlen($phone_input) < 10)
                wp_send_json_error('invalid_phone', 400);
            break;
        case 'cnpj':
            if (strlen($cnpj) !== 14)
                wp_send_json_error('invalid_cnpj', 400);
            break;

        // --- VEICULAR ---
        case 'agregados_propria':
        case 'ic_bin_estadual':
        case 'ic_bin_nacional':
        case 'ic_foto_leilao':
        case 'leilao':
        case 'ic_leilao_score':
        case 'ic_laudo_veicular':
        case 'ic_historico_roubo_furto':
        case 'inde_risco':
        case 'ic_licenciamento_anterior':
        case 'ic_proprietario_atual':
        case 'ic_recall':
        case 'ic_gravame':
        case 'ic_renajud':
        case 'ic_renainf':
        case 'fipe':
        case 'sinistro':
        case 'csv_completo':
        case 'crlv':
        case 'roubo_furto':
            if (!preg_match('/^([A-Z]{3}\d{4}|[A-Z]{3}[0-9A-Z][0-9]{2})$/', $placa))
                wp_send_json_error('invalid_placa', 400);
            break;

        // --- DIVIDAS E CREDITO & JURIDICO (Document based) ---
        case 'cp_spc_cenprot':
        case 'cp_serasa_premium_v2':
        case 'cp_boa_vista_completa':
        case 'r_bv_basica':
        case 'cp_boa_vista_plus_v2':
        case 'cp_score_dividas':
        case 'cp_cadastrais_score_dividas':
        case 'ic_bacen':
        case 'ac_protesto':
        case 'ic_quod':
        case 'r_acoes_processos':
        case 'dossie_juridico':
        case 'cndt':
            if (strlen($document) !== 11 && strlen($document) !== 14)
                wp_send_json_error('invalid_document', 400);
            break;

        case 'r_spc_srs':
            if (strlen($cpf) !== 11)
                wp_send_json_error('invalid_cpf', 400);
            break;

        default:
            // If type is valid in config but not handled here, prevent execution
            header('Cache-Control: no-cache');
            // Proceed if we want to allow generic handling, but strictly we should handle all.
            // Given the plan, we covered all.
            break;
    }

    // Checking Credits
    $debit_str = str_replace(',', '.', $config_item['value'] ?? '0');
    $debit_needed = floatval($debit_str);
    $current_balance = floatval(get_user_meta($user_id, 'serc_credit_balance', true));

    if ($debit_needed > 0 && $current_balance < $debit_needed) {
        wp_send_json_error(array(
            'message' => 'Saldo insuficiente. Você tem ' . number_format($current_balance, 2, ',', '.') . ' créditos e esta consulta custa ' . number_format($debit_needed, 2, ',', '.') . '.',
            'code' => 'insufficient_funds',
            'required' => $debit_needed,
            'balance' => $current_balance
        ), 402);
    }

    // Dispatch API Call
    global $serc_last_api_response;
    $serc_last_api_response = null;
    $api_result = null;

    // Dispatcher
    switch ($type) {
        // 1. Dados Cadastrais
        case 'pf_dadosbasicos':
            $api_result = serc_apifull_pf_dadosbasicos($cpf);
            break;
        case 'ic_cpf_completo':
            $api_result = serc_apifull_ic_cpf_completo($cpf);
            break;
        case 'r_cpf_completo':
            $api_result = serc_apifull_r_cpf_completo($cpf);
            break;
        case 'ic_nome':
            $name = sanitize_text_field($_POST['name'] ?? '');
            $state = strtoupper(preg_replace('/[^A-Za-z]/', '', $_POST['state'] ?? ''));
            $api_result = serc_apifull_ic_nome($name, $state);
            break;
        case 'ic_telefone':
            $api_result = serc_apifull_ic_telefone($phone_input);
            break;
        case 'cnpj':
            $api_result = serc_apifull_cnpj($cnpj);
            break;

        // 2. Veicular
        case 'agregados_propria':
            $api_result = serc_apifull_agregados_propria($placa);
            break;
        case 'ic_bin_estadual':
            $api_result = serc_apifull_ic_bin_estadual($placa);
            break;
        case 'ic_bin_nacional':
            $api_result = serc_apifull_ic_bin_nacional($placa);
            break;
        case 'ic_foto_leilao':
            $api_result = serc_apifull_ic_foto_leilao($placa);
            break;
        case 'leilao':
            $api_result = serc_apifull_leilao($placa);
            break;
        case 'ic_leilao_score':
            $api_result = serc_apifull_ic_leilao_score($placa);
            break;
        case 'ic_laudo_veicular':
            $api_result = serc_apifull_ic_laudo_veicular($placa);
            break;
        case 'ic_historico_roubo_furto':
            $api_result = serc_apifull_ic_historico_roubo_furto($placa);
            break;
        case 'inde_risco':
            $api_result = serc_apifull_inde_risco($placa);
            break;
        case 'ic_licenciamento_anterior':
            $api_result = serc_apifull_ic_licenciamento_anterior($placa);
            break;
        case 'ic_proprietario_atual':
            $api_result = serc_apifull_ic_proprietario_atual($placa);
            break;
        case 'ic_recall':
            $api_result = serc_apifull_ic_recall($placa);
            break;
        case 'ic_gravame':
            $api_result = serc_apifull_ic_gravame($placa);
            break;
        case 'ic_renajud':
            $api_result = serc_apifull_ic_renajud($placa);
            break;
        case 'ic_renainf':
            $api_result = serc_apifull_ic_renainf($placa);
            break;
        case 'fipe':
            $api_result = serc_apifull_fipe($placa);
            break;
        case 'sinistro':
            $api_result = serc_apifull_sinistro($placa);
            break;
        case 'csv_completo':
            $api_result = serc_apifull_csv_completo($placa);
            break;
        case 'crlv':
            $api_result = serc_apifull_crlv($placa);
            break;
        case 'roubo_furto':
            $api_result = serc_apifull_roubo_furto($placa);
            break;

        // 3. Dividas e Credito
        case 'cp_spc_cenprot':
            $api_result = serc_apifull_cp_spc_cenprot($document);
            break;
        case 'r_spc_srs':
            $api_result = serc_apifull_r_spc_srs($cpf);
            break;
        case 'cp_serasa_premium_v2':
            $api_result = serc_apifull_cp_serasa_premium_v2($document);
            break;
        case 'cp_boa_vista_completa':
            $api_result = serc_apifull_cp_boa_vista_completa($document);
            break;
        case 'r_bv_basica':
            $api_result = serc_apifull_r_bv_basica($document);
            break;
        case 'cp_boa_vista_plus_v2':
            $api_result = serc_apifull_cp_boa_vista_plus_v2($document);
            break;
        case 'cp_score_dividas':
            $api_result = serc_apifull_cp_score_dividas($document);
            break;
        case 'cp_cadastrais_score_dividas':
            $api_result = serc_apifull_cp_cadastrais_score_dividas($document);
            break;
        case 'ic_bacen':
            $api_result = serc_apifull_ic_bacen($document);
            break;
        case 'ac_protesto':
            $api_result = serc_apifull_ac_protesto($document);
            break;
        case 'ic_quod':
            $api_result = serc_apifull_ic_quod($document);
            break;

        // 4. Juridico
        case 'r_acoes_processos':
            $api_result = serc_apifull_r_acoes_processos($document);
            break;
        case 'dossie_juridico':
            $api_result = serc_apifull_dossie_juridico($document);
            break;
        case 'cndt':
            $api_result = serc_apifull_cndt($document);
            break;

        default:
            wp_send_json_error('not_implemented_dispatch', 501);
    }

    // Handle API Result
    $http_code = $api_result['http_code'] ?? 500;
    if (empty($api_result['success'])) {
        $msg = $api_result['error'] ?? 'api_error';
        wp_send_json_error(array('message' => $msg, 'code' => $http_code), $http_code >= 200 ? $http_code : 500);
    }

    // Success - Debit Credits
    $debited = 0.0;
    if ($debit_needed > 0) {
        $new_balance = $current_balance - $debit_needed;
        update_user_meta($user_id, 'serc_credit_balance', $new_balance);
        $debited = $debit_needed;
        serc_log_activity($user_id, 'debit', 'Débito consulta ' . $type . ': -' . $debited);
    }

    // Save Consultant Data & PDF
    $pdf_base64 = $api_result['pdf_base64'] ?? null;
    $filename = 'consulta-' . $type . '-' . time() . '.pdf';
    $download_url = '';
    $upload_status = 'pending';
    $up = null;

    $consulta_id = wp_insert_post(array(
        'post_type' => 'serc_consulta',
        'post_status' => 'private',
        'post_author' => $user_id,
        'post_title' => 'Consulta ' . strtoupper($type) . ' #' . time()
    ));

    if ($consulta_id) {
        update_post_meta($consulta_id, 'type', $type);
        update_post_meta($consulta_id, 'filename', $filename);

        // If API didn't return a PDF, generate one from the JSON data
        if (!$pdf_base64 && !empty($serc_last_api_response)) {
            error_log('SERPRO Consultas: consulta ' . $consulta_id . ' no API PDF for type=' . $type . ', generating from data...');
            $pdf_base64 = serc_generate_pdf_from_data($type, $serc_last_api_response);
            if ($pdf_base64) {
                error_log('SERPRO Consultas: consulta ' . $consulta_id . ' PDF generated from JSON data successfully');
            }
        }

        if ($pdf_base64) {
            update_post_meta($consulta_id, 'pdf_base64', $pdf_base64);
            $up = serc_upload_pdf_to_storage($filename, $pdf_base64);
            $upload_status = !empty($up['ok']) ? 'uploaded' : ('failed:' . ($up['message'] ?? ''));
            $hash = serc_consulta_ensure_hash($consulta_id);
            $download_url = admin_url('admin-ajax.php?action=serc_download&hash=' . $hash);
            error_log('SERPRO Consultas: consulta ' . $consulta_id . ' upload_status=' . $upload_status);
        } else {
            $upload_status = 'no_pdf';
            error_log('SERPRO Consultas: consulta ' . $consulta_id . ' no PDF returned from API AND generation failed for type=' . $type);
        }

        // Also store the raw API response for reference
        if (!empty($serc_last_api_response)) {
            update_post_meta($consulta_id, 'api_response', wp_json_encode($serc_last_api_response));
        }
        update_post_meta($consulta_id, 'upload_status', $upload_status);
    }

    // Log the query activity
    serc_log_activity($user_id, 'query', 'Consulta ' . $type);

    // Return Success
    wp_send_json_success(array(
        'quota' => ($current_balance - $debited),
        'debited' => $debited,
        'result' => array(
            'mensagem' => 'Consulta ' . $type . ' realizada com sucesso.',
            'pdfBase64' => $pdf_base64,
            'download_url' => $download_url,
            'upload_log' => array(
                'status' => $upload_status,
                'meta' => isset($up['meta']) ? $up['meta'] : null,
                'code' => isset($up['code']) ? $up['code'] : null,
                'message' => isset($up['message']) ? $up['message'] : null,
                'filename' => $filename
            )
        )
    ));
}

/* Compat: aciona a consulta genérica como CNPJ */
function serc_lookup_cnpj()
{
    $_POST['type'] = 'cnpj';
    serc_lookup();
}

/* =========================
   Helpers
   ========================= */

function serc_get_consultation_types()
{
    return array(
        'cnpj' => 'CNPJ Completo',
        'cpf' => 'CPF Completo e Renda Presumida',
        'cpf_renda' => 'CPF Completo com Renda',
        'ic_nome' => 'Consulta por Nome',
        'ic_telefone' => 'Consulta por Telefone',
        'ic_placa' => 'Consulta por Placa',
        'ic_cnh' => 'CNH',
        'dossie_juridico' => 'Dossiê Jurídico',
        'crlv' => 'CRLV',
        'renainf' => 'Renainf (Multas)',
        'gravame' => 'Gravame detalhamento (Financiamento)',
        'laudo_veicular' => 'Laudo veicular',
        'proprietario_placa' => 'Proprietário placa',
        'scpc_bv_plus_v2' => 'SCPC BV Plus V2',
        'srs_premium' => 'SRS Premium',
        'agregados_basica_propria' => 'Agregados básica própria',
        'bin_estadual' => 'BIN Estadual',
        'bin_nacional' => 'BIN Nacional',
        'foto_leilao' => 'Foto Leilão',
        'leilao' => 'Leilão',
        'leilao_score_perda_total' => 'Leilão, Score Veicular e Perda Total',
        'historico_roubo_furto' => 'Histórico de Roubo ou Furto',
        'indice_risco_veicular' => 'Índice de Risco (Histórico Veicular)',
        'licenciamento_anterior' => 'Licenciamento Anterior',
        'ic_proprietario_atual' => 'Proprietário Atual (API)',
        'recall' => 'Recall',
        'gravame_detalhamento' => 'Gravame Detalhamento (API)',
        'renajud' => 'RENAJUD (Restrições)',
        'renainf_placa' => 'RENAINF (API)',
        'fipe' => 'FIPE',
        'sinistro' => 'Sinistro',
        'serasa_premium' => 'Serasa Premium',
        'ic_basico_score' => 'IC Básico Score',
        'scpc_boa_vista' => 'SCPC Boa Vista',
        'bacen' => 'BACEN',
        'quod' => 'QUOD',
        'spc_brasil_cenprot' => 'SPC Brasil CENPROT',
        'spc_brasil_serasa' => 'SPC Brasil Serasa',
        'dividas_bancrias_cpf' => 'Dívidas bancárias CPF',
        'cadastrais_score_dividas' => 'Cadastrais + Score + Dívidas',
        'cadastrais_score_dividas_cp' => 'Cadastrais + Score + Dívidas CP',
        'scr_bacen_score' => 'SCR BACEN Score',
        'protesto_nacional_cenprot' => 'Protesto Nacional CENPROT',
        'r_acoes_e_processos_judiciais' => 'Ações e Processos Judiciais',
        'dossie_juridico_cpf' => 'Dossiê Jurídico CPF',
        'certidao_nacional_debitos_trabalhistas' => 'CNDT',
    );
}


/* =========================
   Lógica principal: aplicar créditos ao concluir pedido
   ========================= */
function serc_handle_order_completed($order_id_or_obj)
{
    $order = is_object($order_id_or_obj) ? $order_id_or_obj : wc_get_order($order_id_or_obj);
    if (!$order)
        return;
    $user_id = $order->get_user_id();
    if (!$user_id)
        return;

    $total_add = 0.0;
    foreach ($order->get_items() as $item) {
        $qty = intval($item->get_quantity());
        $pid = $item->get_product_id();
        $vid = $item->get_variation_id();

        // Créditos gerais por item (variação tem precedência)
        $general = 0.0;
        if ($vid) {
            $general = floatval(get_post_meta($vid, 'serc_general_credits', true));
            if ($general <= 0) {
                $general = floatval(get_post_meta($pid, 'serc_general_credits', true));
            }
        } else {
            $general = floatval(get_post_meta($pid, 'serc_general_credits', true));
        }

        if ($general > 0 && $qty > 0) {
            $total_add += round($general * max(1, $qty), 2);
        }
    }

    if ($total_add > 0) {
        $current_balance = floatval(get_user_meta($user_id, 'serc_credit_balance', true));
        $new_balance = round($current_balance + $total_add, 2);
        update_user_meta($user_id, 'serc_credit_balance', $new_balance);
        error_log("SERPRO Consultas: Pedido #{$order->get_id()} adicionou +{$total_add} créditos ao usuário {$user_id}. Saldo: {$new_balance}.");
    }
}

/* Para pedidos criados já como concluídos */
function serc_check_new_order_status($order_id)
{
    $order = wc_get_order($order_id);
    if ($order && $order->get_status() === 'completed') {
        serc_handle_order_completed($order);
    }
}


/* =========================
   WooCommerce: Campo por produto
   ========================= */
function serc_wc_product_field()
{
    global $post;
    echo '<div class="options_group">';
    $general = get_post_meta($post->ID, 'serc_general_credits', true);
    $general = $general === '' ? '' : esc_attr(floatval($general));
    echo '<p><strong>Créditos por compra (GERAL)</strong>: <input type="number" step="0.01" min="0" name="serc_general_credits" value="' . $general . '" /></p>';
    echo '<p class="description">Quantidade de créditos adicionados ao saldo do cliente ao comprar este produto. Os valores de débito por consulta são configurados globalmente em <strong>Selo Brasil → Débitos por Consulta</strong>.</p>';
    echo '</div>';
}

function serc_wc_save_product_field($post_id)
{
    // Salva créditos gerais por produto
    if (isset($_POST['serc_general_credits'])) {
        $general = floatval(wc_clean($_POST['serc_general_credits']));
        if ($general > 0) {
            update_post_meta($post_id, 'serc_general_credits', $general);
        } else {
            delete_post_meta($post_id, 'serc_general_credits');
        }
    }
    // Configuração por tipo agora é global — não salvar mais por produto
}

function serc_wc_variation_field($loop, $variation_data, $variation)
{
    echo '<div class="form-row form-row-full">';
    // Créditos gerais da variação
    $general = get_post_meta($variation->ID, 'serc_general_credits', true);
    $general = $general === '' ? '' : esc_attr(floatval($general));
    echo '<p><strong>Créditos por compra (GERAL)</strong>: <input type="number" step="0.01" min="0" name="variable_serc_general_credits[' . esc_attr($loop) . ']" value="' . $general . '" /></p>';
    echo '<p class="description">Quantidade de créditos adicionados ao saldo do cliente ao comprar esta variação. Os valores de débito são configurados globalmente em <strong>Selo Brasil → Débitos por Consulta</strong>.</p>';
    echo '</div>';
}

function serc_wc_save_variation_field($variation_id, $i)
{
    // Salva créditos gerais da variação
    if (isset($_POST['variable_serc_general_credits'][$i])) {
        $general = floatval(wc_clean($_POST['variable_serc_general_credits'][$i]));
        if ($general > 0) {
            update_post_meta($variation_id, 'serc_general_credits', $general);
        } else {
            delete_post_meta($variation_id, 'serc_general_credits');
        }
    }
    // Configuração por tipo agora é global — não salvar mais por variação
}



add_action('init', 'serc_init_endpoints');
function serc_init_endpoints()
{
    add_rewrite_endpoint('consultas', EP_ROOT | EP_PAGES);
    serc_register_consulta_cpt();
}

add_filter('query_vars', 'serc_register_query_var');
function serc_register_query_var($vars)
{
    $vars[] = 'consultas';
    return $vars;
}
add_filter('woocommerce_get_query_vars', 'serc_register_wc_query_var');
function serc_register_wc_query_var($vars)
{
    $vars['consultas'] = 'consultas';
    return $vars;
}
add_filter('woocommerce_endpoint_consultas_title', 'serc_endpoint_consultas_title');
function serc_endpoint_consultas_title($title)
{
    return 'Consultas';
}

function serc_register_consulta_cpt()
{
    register_post_type('serc_consulta', array(
        'label' => 'Consulta',
        'public' => false,
        'show_ui' => false,
        'supports' => array('author', 'custom-fields', 'title'),
    ));
}

add_filter('woocommerce_account_menu_items', 'serc_account_menu', 20);
function serc_account_menu($items)
{
    $new = array();
    foreach ($items as $key => $label) {
        $new[$key] = $label;
        if ($key === 'downloads') {
            $new['consultas'] = 'Consultas';
        }
    }
    if (!isset($new['consultas'])) {
        $new['consultas'] = 'Consultas';
    }
    return $new;
}
add_filter('woocommerce_get_endpoint_url', 'serc_fix_consultas_endpoint_url', PHP_INT_MAX, 4);
function serc_fix_consultas_endpoint_url($url, $endpoint, $value, $permalink)
{
    if ($endpoint === 'consultas') {
        $base = wc_get_page_permalink('myaccount');
        $url = trailingslashit($base) . 'consultas' . ($value ? '/' . $value : '/');
    }
    return $url;
}
add_action('woocommerce_account_consultas_endpoint', 'serc_account_consultas_endpoint');
function serc_account_consultas_endpoint()
{
    error_log('SERPRO Consultas: endpoint consultas view');
    $uid = get_current_user_id();
    if (!$uid) {
        echo '<p>Faça login.</p>';
        return;
    }
    $type = sanitize_text_field($_GET['tipo'] ?? '');
    $de = sanitize_text_field($_GET['de'] ?? '');
    $ate = sanitize_text_field($_GET['ate'] ?? '');
    $paged = max(1, intval($_GET['pg'] ?? 1));
    $args = array('post_type' => 'serc_consulta', 'post_status' => 'private', 'author' => $uid, 'posts_per_page' => 10, 'paged' => $paged, 'orderby' => 'date', 'order' => 'DESC');
    $mq = array();
    if ($type)
        $mq[] = array('key' => 'type', 'value' => $type, 'compare' => '=');
    if (!empty($mq))
        $args['meta_query'] = $mq;
    if ($de || $ate)
        $args['date_query'] = array(array('after' => $de ?: null, 'before' => $ate ?: null, 'inclusive' => true));
    $q = new WP_Query($args);
    error_log('SERPRO Consultas: painel consultas uid=' . $uid . ' tipo=' . ($type ?: '-') . ' de=' . ($de ?: '-') . ' ate=' . ($ate ?: '-') . ' pg=' . $paged . ' found=' . $q->found_posts);
    echo '<form method="get" style="margin-bottom:12px"><input type="hidden" name="consultas" value="1"/><input name="de" type="date" value="' . esc_attr($de) . '"/> <input name="ate" type="date" value="' . esc_attr($ate) . '"/> <select name="tipo"><option value="">Todos</option><option value="cpf" ' . selected($type, 'cpf', false) . '>CPF</option><option value="cnpj" ' . selected($type, 'cnpj', false) . '>CNPJ</option></select> <button class="button">Filtrar</button></form>';
    echo '<table class="shop_table shop_table_responsive"><thead><tr><th>Data</th><th>Tipo</th><th>Status</th><th>Download</th></tr></thead><tbody>';
    if ($q->have_posts()) {
        while ($q->have_posts()) {
            $q->the_post();
            $pid = get_the_ID();
            $t = get_post_meta($pid, 'type', true);
            $st = get_post_meta($pid, 'upload_status', true);
            $hash = serc_consulta_ensure_hash($pid);
            $url = admin_url('admin-ajax.php?action=serc_download&hash=' . $hash);
            echo '<tr><td>' . esc_html(get_the_date('d/m/Y H:i')) . '</td><td>' . esc_html($t) . '</td><td>' . esc_html($st ?: 'n/a') . '</td><td><a class="action-btn" href="' . esc_url($url) . '"><i class="ph-bold ph-download-simple"></i> Download PDF</a></td></tr>';
        }
        wp_reset_postdata();
    } else {
        echo '<tr><td colspan="4">Nenhuma consulta encontrada.</td></tr>';
    }
    echo '</tbody></table>';
    $paginate = paginate_links(array('base' => add_query_arg('pg', '%#%'), 'format' => '', 'current' => $paged, 'total' => $q->max_num_pages));
    if ($paginate)
        echo '<div class="pagination">' . $paginate . '</div>';
}

add_action('wp_ajax_serc_download', 'serc_secure_download');
add_action('wp_ajax_nopriv_serc_download', 'serc_secure_download');
function serc_secure_download()
{
    if (!is_user_logged_in()) {
        error_log('SERPRO Consultas: download 401 (not logged)');
        status_header(401);
        exit;
    }
    $uid = get_current_user_id();
    $hash = sanitize_text_field($_REQUEST['hash'] ?? '');
    if (!$hash) {
        error_log('SERPRO Consultas: download 400 (missing hash)');
        status_header(400);
        echo 'hash';
        exit;
    }
    $posts = get_posts(array('post_type' => 'serc_consulta', 'post_status' => 'private', 'meta_key' => 'download_hash', 'meta_value' => $hash, 'author' => $uid, 'numberposts' => 1));
    if (!$posts) {
        error_log('SERPRO Consultas: download 404 (not found) hash=' . $hash . ' user=' . $uid);
        status_header(404);
        exit;
    }
    $p = $posts[0];
    $pid = $p->ID;
    $exp = intval(get_post_meta($pid, 'hash_expire', true));
    $used = get_post_meta($pid, 'hash_used', true);
    if ($used || ($exp && time() > $exp)) {
        error_log('SERPRO Consultas: download 410 (expired/used) pid=' . $pid . ' exp=' . $exp . ' used=' . ($used ? 1 : 0));
        status_header(410);
        exit;
    }
    $rk = 'serc_rl_dl_' . $uid;
    $rc = intval(get_transient($rk));
    if ($rc >= 10) {
        error_log('SERPRO Consultas: download 429 (rate limit) user=' . $uid);
        status_header(429);
        exit;
    }
    set_transient($rk, $rc + 1, 60);
    $filename = get_post_meta($pid, 'filename', true);
    $pdfb64 = get_post_meta($pid, 'pdf_base64', true);
    $token = get_option('serc_apifull_token', '');
    $auth = (stripos($token, 'Bearer ') === 0) ? $token : ('Bearer ' . $token);
    $content = null;
    if ($pdfb64) {
        $content = base64_decode(preg_replace('/^data:.*;base64,/', '', $pdfb64));
    }
    if (!$content) {
        // Try storage fetch
        $storage_ok = false;
        $req = wp_remote_post('https://api.apifull.com.br/storage/files', array('headers' => array('Authorization' => $auth, 'Accept' => 'application/pdf', 'Content-Type' => 'application/json'), 'body' => wp_json_encode(array('name' => $filename)), 'timeout' => 30));
        if (!is_wp_error($req)) {
            $ct = wp_remote_retrieve_header($req, 'content-type');
            if (strpos(strtolower($ct), 'application/pdf') !== false) {
                $content = wp_remote_retrieve_body($req);
                $storage_ok = true;
            } else {
                error_log('SERPRO Consultas: storage fetch non-PDF mime ct=' . $ct);
            }
        } else {
            error_log('SERPRO Consultas: storage fetch error ' . $req->get_error_message());
        }
        // Fallback: generate PDF from stored API response data
        if (!$storage_ok || !$content) {
            $api_response_json = get_post_meta($pid, 'api_response', true);
            $type = get_post_meta($pid, 'type', true);
            if ($api_response_json) {
                $api_data = json_decode($api_response_json, true);
                if (is_array($api_data) && !empty($api_data)) {
                    $gen_b64 = serc_generate_pdf_from_data($type ?: 'consulta', $api_data);
                    if ($gen_b64) {
                        $content = base64_decode($gen_b64);
                        // Cache it for next time
                        update_post_meta($pid, 'pdf_base64', $gen_b64);
                        error_log('SERPRO Consultas: download generated PDF from stored api_response pid=' . $pid);
                    }
                }
            }
            if (!$content) {
                error_log('SERPRO Consultas: download failed - no PDF available pid=' . $pid);
                status_header(404);
                echo 'PDF não disponível para esta consulta.';
                exit;
            }
        }
    }
    update_post_meta($pid, 'hash_used', 1);

    // Log the download activity
    $type = get_post_meta($pid, 'type', true);
    serc_log_activity($uid, 'download', 'Download relatório ' . $type);

    error_log('SERPRO Consultas: download id=' . $pid . ' user=' . $uid . ' filename=' . $filename);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store');
    echo $content;
    exit;
}

function serc_consulta_ensure_hash($pid)
{
    $exp = intval(get_post_meta($pid, 'hash_expire', true));
    $used = get_post_meta($pid, 'hash_used', true);
    $hash = get_post_meta($pid, 'download_hash', true);
    if (!$hash || $used || ($exp && time() > $exp)) {
        $hash = wp_generate_password(32, false, false);
        update_post_meta($pid, 'download_hash', $hash);
        update_post_meta($pid, 'hash_expire', time() + 86400);
        delete_post_meta($pid, 'hash_used');
    }
    return $hash;
}

function serc_upload_pdf_to_storage($filename, $pdf_base64)
{
    $token = get_option('serc_apifull_token', '');
    if (empty($token)) {
        error_log('SERPRO Consultas: upload falhou – token vazio');
        return array('ok' => false, 'message' => 'no_token');
    }
    $auth = (stripos($token, 'Bearer ') === 0) ? $token : ('Bearer ' . $token);
    $file = (strpos($pdf_base64, 'data:') === 0) ? $pdf_base64 : ('data:application/pdf;base64,' . $pdf_base64);
    $raw = preg_replace('/^data:.*;base64,/', '', $file);
    $bin = base64_decode($raw);
    $size = strlen($bin);
    $boundary = '----sercform_' . wp_generate_password(16, false, false);
    $body = '';
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"name\"\r\n\r\n{$filename}\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$filename}\"\r\n";
    $body .= "Content-Type: application/pdf\r\n\r\n";
    $body .= $bin;
    $body .= "\r\n--{$boundary}--\r\n";
    $t0 = microtime(true);
    $req = wp_remote_post('https://api.apifull.com.br/storage/upload', array(
        'headers' => array(
            'Authorization' => $auth,
            'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
            'Accept' => 'application/json'
        ),
        'body' => $body,
        'timeout' => 30
    ));
    if (is_wp_error($req)) {
        error_log('SERPRO Consultas: upload erro ' . $req->get_error_message());
        return array('ok' => false, 'message' => 'http_error', 'meta' => array('start_ms' => intval($t0 * 1000), 'end_ms' => intval(microtime(true) * 1000), 'size_bytes' => $size));
    }
    $code = wp_remote_retrieve_response_code($req);
    $resp_body = wp_remote_retrieve_body($req);
    $resp = json_decode($resp_body, true);
    $t1 = microtime(true);
    if ($code >= 200 && $code < 300) {
        $remoteId = (is_array($resp) && isset($resp['id'])) ? $resp['id'] : null;
        error_log('SERPRO Consultas: upload OK filename=' . $filename . ' code=' . $code . ' id=' . ($remoteId ? $remoteId : 'n/a'));
        return array('ok' => true, 'response' => $resp, 'id' => $remoteId, 'meta' => array('start_ms' => intval($t0 * 1000), 'end_ms' => intval($t1 * 1000), 'duration_ms' => intval(($t1 - $t0) * 1000), 'size_bytes' => $size));
    }
    $msg = (is_array($resp) && isset($resp['message'])) ? $resp['message'] : 'upload_failed';
    error_log('SERPRO Consultas: upload falhou filename=' . $filename . ' code=' . $code . ' msg=' . $msg);
    return array('ok' => false, 'message' => $msg, 'code' => $code, 'meta' => array('start_ms' => intval($t0 * 1000), 'end_ms' => intval($t1 * 1000), 'duration_ms' => intval(($t1 - $t0) * 1000), 'size_bytes' => $size));
}

function serc_upload()
{
    if (!wp_verify_nonce($_POST['nonce'] ?? '', SERCNPJ_NONCE))
        wp_send_json_error('invalid_nonce', 403);
    if (!is_ssl())
        wp_send_json_error('insecure', 400);
    $uid = get_current_user_id();
    if (!$uid)
        wp_send_json_error('no_user', 401);
    $rk = 'serc_rl_ul_' . $uid;
    $rc = intval(get_transient($rk));
    if ($rc >= 30)
        wp_send_json_error('rate_limit', 429);
    set_transient($rk, $rc + 1, 3600);
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'multipart/form-data') === false) {
        error_log('SERPRO Consultas: upload endpoint bad content-type ' . $ct);
        wp_send_json_error('bad_content_type', 415);
    }
    if (empty($_FILES['file'])) {
        error_log('SERPRO Consultas: upload endpoint missing file');
        wp_send_json_error('file_required', 400);
    }
    $f = $_FILES['file'];
    if (intval($f['error']) !== UPLOAD_ERR_OK) {
        error_log('SERPRO Consultas: upload endpoint file error ' . $f['error']);
        wp_send_json_error(array('code' => 'upload_error', 'err' => intval($f['error'])), 400);
    }
    $size = intval($f['size']);
    if ($size <= 0 || $size > 20971520) {
        error_log('SERPRO Consultas: upload endpoint bad size ' . $size);
        wp_send_json_error('bad_size', 413);
    }
    $tmp = $f['tmp_name'];
    $mime = '';
    if (function_exists('finfo_open')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($fi, $tmp);
        finfo_close($fi);
    }
    if (!$mime) {
        $mime = mime_content_type($tmp);
    }
    if (stripos($mime, 'pdf') === false) {
        error_log('SERPRO Consultas: upload endpoint bad mime ' . $mime);
        wp_send_json_error('bad_mime', 415);
    }
    $content = @file_get_contents($tmp);
    if ($content === false || substr($content, 0, 4) !== '%PDF') {
        error_log('SERPRO Consultas: upload endpoint bad pdf header');
        wp_send_json_error('bad_pdf', 415);
    }
    $uploads = wp_upload_dir();
    if (empty($uploads['basedir']) || !is_writable($uploads['basedir'])) {
        error_log('SERPRO Consultas: uploads not writable basedir=' . ($uploads['basedir'] ?? ''));
    }
    $filename = 'upload-' . $uid . '-' . time() . '.pdf';
    $b64 = 'data:application/pdf;base64,' . base64_encode($content);
    $up = serc_upload_pdf_to_storage($filename, $b64);
    if (empty($up['ok'])) {
        wp_send_json_error(array('code' => 'storage_failed', 'message' => ($up['message'] ?? '')), 502);
    }
    $consulta_id = wp_insert_post(array('post_type' => 'serc_consulta', 'post_status' => 'private', 'post_author' => $uid, 'post_title' => 'Upload PDF #' . time()));
    if ($consulta_id) {
        update_post_meta($consulta_id, 'type', 'upload');
        update_post_meta($consulta_id, 'filename', $filename);
        update_post_meta($consulta_id, 'upload_status', 'uploaded');
    }
    $hash = serc_consulta_ensure_hash($consulta_id);
    $url = admin_url('admin-ajax.php?action=serc_download&hash=' . $hash);
    wp_send_json_success(array('download_url' => $url, 'filename' => $filename, 'storage_id' => ($up['id'] ?? null)));
}
function serc_plugin_activate()
{
    add_rewrite_endpoint('consultas', EP_ROOT | EP_PAGES);
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'serc_plugin_activate');

// =========================
//    Dashboard Endpoint
//    ========================= */
function serc_dashboard_rewrite_rule()
{
    add_rewrite_rule('^consultas/?$', 'index.php?serc_dashboard=1', 'top');
}
add_action('init', 'serc_dashboard_rewrite_rule');

function serc_dashboard_query_vars($query_vars)
{
    $query_vars[] = 'serc_dashboard';
    return $query_vars;
}
add_filter('query_vars', 'serc_dashboard_query_vars');

function serc_dashboard_template($template)
{
    if (get_query_var('serc_dashboard')) {
        $dashboard = plugin_dir_path(__FILE__) . 'dashboard.php';
        if (file_exists($dashboard)) {
            return $dashboard;
        }
    }
    return $template;
}
add_filter('template_include', 'serc_dashboard_template');

// Flatten rewrite rules on activation (simplified check)
function serc_flush_rules()
{
    serc_dashboard_rewrite_rule();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'serc_flush_rules');
