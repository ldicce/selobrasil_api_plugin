<?php
/**
 * Preview Mode for Dashboard
 * This file simulates basic WP functions to allow previewing dashboard.php
 */

define('ABSPATH', __DIR__ . '/');

// Mock Data Storage (Simulation)
$mock_db = [
    'user_meta' => [
        1 => [
            'serc_credit_balance' => [145.50]
        ]
    ],
    'posts' => [
        ['ID' => 101, 'post_type' => 'serc_consulta', 'post_status' => 'private', 'post_author' => 1, 'post_date' => '2023-10-25 14:30:00', 'meta' => ['type' => 'CPF Básico', 'upload_status' => 1, 'filename' => 'consulta_cpf.pdf']],
        ['ID' => 102, 'post_type' => 'serc_consulta', 'post_status' => 'private', 'post_author' => 1, 'post_date' => '2023-10-24 09:15:00', 'meta' => ['type' => 'CNPJ Completo', 'upload_status' => 1, 'filename' => 'consulta_cnpj.pdf']],
        ['ID' => 103, 'post_type' => 'serc_consulta', 'post_status' => 'private', 'post_author' => 1, 'post_date' => '2023-10-23 16:45:00', 'meta' => ['type' => 'Veicular', 'upload_status' => 0, 'filename' => '']],
        ['ID' => 104, 'post_type' => 'serc_consulta', 'post_status' => 'private', 'post_author' => 1, 'post_date' => '2023-10-22 11:20:00', 'meta' => ['type' => 'CPF Básico', 'upload_status' => 1, 'filename' => 'consulta_cpf_old.pdf']],
    ]
];

// Mock WP_Query Class
class WP_Query
{
    public $posts = [];
    public $post;
    public $found_posts = 0;
    public $max_num_pages = 1;
    private $current_post_index = -1;

    public function __construct($args = [])
    {
        global $mock_db;
        // Filter mock posts based on args (simplified)
        $this->posts = array_filter($mock_db['posts'], function ($p) use ($args) {
            // Type filter
            if (isset($args['meta_query'])) {
                foreach ($args['meta_query'] as $mq) {
                    if ($mq['key'] === 'type' && stripos($p['meta']['type'], $mq['value']) === false)
                        return false;
                }
            }
            // Date filter (simplified)
            if (isset($args['date_query'])) {
                // Implementation skipped for brevity in mock
            }
            return true;
        });

        // Convert array arrays to objects
        foreach ($this->posts as $k => $v) {
            $this->posts[$k] = (object) $v;
        }

        $this->posts = array_values($this->posts);
        $this->found_posts = count($this->posts);
    }

    public function have_posts()
    {
        return isset($this->posts[$this->current_post_index + 1]);
    }

    public function the_post()
    {
        $this->current_post_index++;
        $this->post = $this->posts[$this->current_post_index];
        // Setup global post mock
        global $post;
        $post = $this->post;
        return $this->post;
    }
}

// Mock Functions
function wp_reset_postdata()
{
}

function get_current_user_id()
{
    return 1;
}
function is_user_logged_in()
{
    return true;
}

function get_user_meta($user_id, $key, $single = false)
{
    global $mock_db;
    $val = $mock_db['user_meta'][$user_id][$key] ?? null;
    if ($single && is_array($val))
        return $val[0];
    return $val;
}

function update_user_meta($user_id, $key, $value)
{
    global $mock_db;
    $mock_db['user_meta'][$user_id][$key] = [$value];
    return true;
}

function get_the_ID()
{
    global $post;
    return $post->ID ?? 0;
}
function get_the_date($format = '')
{
    global $post;
    return date($format ?: 'Y-m-d', strtotime($post->post_date));
}

function get_post_meta($post_id, $key, $single = false)
{
    global $mock_db;
    foreach ($mock_db['posts'] as $p) {
        if ($p['ID'] == $post_id) {
            $val = $p['meta'][$key] ?? '';
            return $val;
        }
    }
    return '';
}

function wp_send_json_error($msg)
{
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'data' => $msg]));
}
function wp_send_json_success($data)
{
    header('Content-Type: application/json');
    die(json_encode(['success' => true, 'data' => $data]));
}
function wp_verify_nonce()
{
    return true;
}
function esc_html($s)
{
    return htmlspecialchars($s);
}
function esc_attr($s)
{
    return htmlspecialchars($s);
}
function sanitize_text_field($s)
{
    return trim(strip_tags($s));
}
function sanitize_key($s)
{
    return strtolower(preg_replace('/[^a-z0-9_\-]/', '', $s));
}
function get_transient()
{
    return false;
}
function set_transient()
{
    return true;
}
function delete_transient()
{
    return true;
}
function home_url($p = '')
{
    return '/preview.php' . $p;
}
function plugin_dir_path($f)
{
    return dirname($f) . '/';
}
function plugin_dir_url($f)
{
    return './';
}
function add_action()
{
}
function register_activation_hook()
{
}
function register_deactivation_hook()
{
}
function plugin_basename($f)
{
    return basename($f);
}
function add_shortcode()
{
}
function add_filter()
{
}
function apply_filters($t, $v)
{
    return $v;
}
function wp_enqueue_script()
{
}
function wp_localize_script()
{
}
function admin_url($p = '')
{
    return '/admin/' . $p;
}
function wp_create_nonce()
{
    return 'nonce';
}
function do_shortcode($c)
{
    return $c;
}

// Helper function to get user credits calling the now-mocked or real function structure
function serc_get_user_credits()
{
    $uid = get_current_user_id();
    return floatval(get_user_meta($uid, 'serc_credit_balance', true));
}

// ... existing code ...

// Mock Functions
function language_attributes()
{
    echo 'lang="pt-BR"';
}
function bloginfo($key)
{
    if ($key == 'charset')
        echo 'UTF-8';
}
function body_class()
{
    echo 'class="wp-mock-preview"';
}
function wp_head()
{
    echo '<style>body{background:#f0f2f5 !important;}</style>';
}
function wp_footer()
{
}
// Note: get_current_user_id, is_user_logged_in, get_user_meta, etc. are provided by WordPress

// Handle AJAX requests
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    error_log('[SERC Preview] AJAX request received');
    error_log('[SERC Preview] POST data: ' . print_r($_POST, true));

    // Load the plugin to access serc_lookup function
    require_once __DIR__ . '/serpro-cnpj-quotas.php';

    // Set jQuery post data to $_POST for compatibility
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Call the lookup function
        if (isset($_POST['action']) && $_POST['action'] === 'serc_lookup') {
            error_log('[SERC Preview] Calling serc_lookup()');
            serc_lookup();
            exit;
        } else {
            error_log('[SERC Preview] Invalid action: ' . ($_POST['action'] ?? 'none'));
        }
    } else {
        error_log('[SERC Preview] Not a POST request: ' . $_SERVER['REQUEST_METHOD']);
    }

    // If no valid action, return error
    error_log('[SERC Preview] Returning error - invalid AJAX request');
    wp_send_json_error('Invalid AJAX request');
    exit;
}


// Allow dashboard.php to run
$page = $_GET['p'] ?? 'dashboard';

if ($page === 'consulta') {
    include 'category-view.php';
} elseif ($page === 'query') {
    include 'query-form.php';
} elseif ($page === 'history') {
    include 'history-view.php';
} else {
    include 'dashboard.php';
}
