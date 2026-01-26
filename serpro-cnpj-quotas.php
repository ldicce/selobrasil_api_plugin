<?php
/*
Plugin Name: Selo Brasil - Consultas
Description: Define cotas fixas automaticamente sempre que um pedido é criado com status Concluído.
Version: 1.40
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

/* Hook: toda vez que um pedido é criado ou atualizado para "completed" */
add_action('woocommerce_new_order', 'serc_check_new_order_status');
add_action('woocommerce_order_status_completed', 'serc_handle_order_completed', 10, 1);


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
    wp_enqueue_script('serc-frontend', plugins_url('assets/js/serc-frontend.js', __FILE__), array('jquery', 'jquery-mask'), '1.40', true);

    // External Assets (Google Fonts & Phosphor Icons)
    wp_enqueue_style('serc-google-fonts', 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap', array(), null);
    wp_enqueue_script('serc-phosphor-icons', 'https://unpkg.com/@phosphor-icons/web', array(), null, false);


    // Main Dashboard Styles
    wp_enqueue_style('serc-dashboard-style', plugins_url('assets/css/style.css', __FILE__), array(), '1.40');

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

    // Fallback logic if needed (legacy wallet system)
    if ($balance === '') {
        $wallet = serc_get_wallet($user_id);
        $sum = 0.0;
        foreach ($wallet as $entry) {
            $sum += floatval($entry['balance'] ?? 0);
        }
        $balance = $sum;
    }

    return floatval($balance);
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

    add_options_page('Serpro Consultas - Shortcodes', 'Serpro Consultas', 'manage_options', 'serpro-consultas-shortcodes', 'serc_shortcodes_page');
    add_options_page('API Full – Token', 'API Full – Token', 'manage_options', 'serpro-apifull-token', 'serc_token_page');
}

function serc_render_admin_page()
{
    ?>
    <div class="wrap">
        <h1>Selo Brasil - Painel Administrativo</h1>
        <p>Funcionalidades administrativas em breve...</p>
        <hr>
        <p>Acesse o painel do cliente via shortcode <code>[serc_dashboard]</code> em uma página do site.</p>
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

    // Return JSON response with the view for sidebar state update
    wp_send_json_success(array(
        'html' => $content,
        'view' => $view,
        'type' => $type,
        'integration' => $integration
    ));
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
        array('label' => 'Proprietário Atual (API)', 'code' => '[serc_ic_proprietario_atual_form]'),
        array('label' => 'Recall', 'code' => '[serc_recall_form]'),
        array('label' => 'Gravame Detalhamento (API)', 'code' => '[serc_gravame_detalhamento_form]'),
        array('label' => 'RENAJUD (Restrições)', 'code' => '[serc_renajud_form]'),
        array('label' => 'RENAINF (API)', 'code' => '[serc_renainf_placa_form]'),
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
        <script>
            (function () {
                document.addEventListener('click', function (e) {
                    if (e.target && e.target.classList.contains('serc-copy')) {
                        var code = e.target.getAttribute('data-code');
                        navigator.clipboard.writeText(code);
                        e.target.textContent = 'Copiado!';
                        setTimeout(function () { e.target.textContent = 'Copiar'; }, 1500);
                    }
                });
            })();
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
    $wallet = serc_get_wallet($user_id);
    if (!empty($wallet)) {
        foreach ($wallet as $key => $entry) {
            $entry_balance = floatval(isset($entry['balance']) ? $entry['balance'] : 0);
            $pid = isset($entry['product_id']) ? intval($entry['product_id']) : 0;
            $vid = isset($entry['variation_id']) ? intval($entry['variation_id']) : 0;
            if ($entry_balance > 0 && ($pid || $vid)) {
                // Busca configuração por item para obter o débito do tipo
                $cfg = serc_get_consultations_config_for_ids($pid, $vid);
                $row = isset($cfg[$type]) ? $cfg[$type] : array();
                $enabled = !empty($row['enabled']);
                $debit = floatval(isset($row['debit']) ? $row['debit'] : 0);
                if ($enabled && $debit > 0 && $entry_balance >= $debit) {
                    $wallet[$key]['balance'] = round($entry_balance - $debit, 2);
                    serc_set_wallet($user_id, $wallet);
                    $new_balance = floatval(get_user_meta($user_id, 'serc_credit_balance', true));
                    return array('balance' => $new_balance, 'debited' => $debit);
                }
            }
        }
    }
    return false;
}

function serc_apifull_post_extract_pdf_base64($endpoint, $payload, $log_prefix)
{
    $pdf_base64 = null;
    $token = get_option('serc_apifull_token', '');
    if (!empty($token)) {
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
        if (!is_wp_error($req)) {
            $code = wp_remote_retrieve_response_code($req);
            $body = wp_remote_retrieve_body($req);
            error_log($log_prefix . ' API code=' . $code);
            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                if (isset($decoded['pdfBase64']) && is_string($decoded['pdfBase64'])) {
                    $pdf_base64 = $decoded['pdfBase64'];
                } else {
                    foreach ($decoded as $k => $v) {
                        if (is_array($v) && isset($v['pdfBase64']) && is_string($v['pdfBase64'])) {
                            $pdf_base64 = $v['pdfBase64'];
                            break;
                        }
                    }
                }
            }
        }
    }
    return $pdf_base64;
}

function serc_apifull_lookup_cpf_renda($cpf)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/r-cpf-completo',
        array('cpf' => $cpf, 'link' => 'r-cpf-completo'),
        'SERPRO Consultas: CPF RENDA'
    );
}

function serc_apifull_lookup_ic_nome($name, $state)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/ic-nome',
        array('name' => $name, 'state' => $state, 'link' => 'ic-nome'),
        'SERPRO Consultas: NOME'
    );
}

function serc_apifull_lookup_ic_telefone($ddd, $telefone, $state)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/ic-telefone',
        array('ddd' => $ddd, 'telefone' => $telefone, 'state' => $state, 'link' => 'ic-telefone'),
        'SERPRO Consultas: TELEFONE'
    );
}

function serc_apifull_lookup_ic_placa($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/ic-placa',
        array('placa' => $placa, 'link' => 'ic-placa'),
        'SERPRO Consultas: PLACA'
    );
}

function serc_apifull_lookup_ic_cnh($cpf)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/ic-cnh',
        array('cpf' => $cpf, 'link' => 'ic-cnh'),
        'SERPRO Consultas: CNH'
    );
}

function serc_apifull_lookup_cnpj($cnpj)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/cnpj',
        array('cnpj' => $cnpj, 'link' => 'cnpj'),
        'SERPRO Consultas: CNPJ'
    );
}

function serc_apifull_lookup_crlv($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/crlv',
        array('placa' => $placa, 'link' => 'crlv'),
        'SERPRO Consultas: CRLV'
    );
}

function serc_apifull_lookup_proprietario_placa($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/proprietario-placa',
        array('placa' => $placa, 'link' => 'proprietario-placa'),
        'SERPRO Consultas: PROPRIETARIO PLACA'
    );
}

function serc_apifull_lookup_gravame($chassi)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/gravame',
        array('chassi' => $chassi, 'link' => 'gravame'),
        'SERPRO Consultas: GRAVAME'
    );
}

function serc_apifull_lookup_renainf_placa_renavam($placa, $renavam)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/renainf',
        array('placa' => $placa, 'renavam' => $renavam, 'link' => 'renainf'),
        'SERPRO Consultas: RENAINF'
    );
}

function serc_apifull_lookup_scpc_bv_plus_v2($cpf)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/scpc-bv-plus-v2',
        array('cpf' => $cpf, 'link' => 'scpc-bv-plus-v2'),
        'SERPRO Consultas: SCPC BV PLUS V2'
    );
}

function serc_apifull_lookup_srs_premium($cpf)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/srs-premium',
        array('cpf' => $cpf, 'link' => 'srs-premium'),
        'SERPRO Consultas: SRS PREMIUM'
    );
}

function serc_apifull_lookup_agregados_basica_propria($param)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/agregados-basica-propria',
        array('param' => $param, 'link' => 'agregados-basica-propria'),
        'SERPRO Consultas: AGREGADOS BASICA PROPRIA'
    );
}

function serc_apifull_lookup_bin_estadual($estado)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/bin-estadual',
        array('estado' => $estado, 'link' => 'bin-estadual'),
        'SERPRO Consultas: BIN ESTADUAL'
    );
}

function serc_apifull_lookup_bin_nacional($cpf)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/bin-nacional',
        array('cpf' => $cpf, 'link' => 'bin-nacional'),
        'SERPRO Consultas: BIN NACIONAL'
    );
}

function serc_apifull_lookup_foto_leilao($leilao_id)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/foto-leilao',
        array('leilaoId' => $leilao_id, 'link' => 'foto-leilao'),
        'SERPRO Consultas: FOTO LEILAO'
    );
}

function serc_apifull_lookup_leilao($filtro)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/leilao',
        array('filtro' => $filtro, 'link' => 'leilao'),
        'SERPRO Consultas: LEILAO'
    );
}

function serc_apifull_lookup_leilao_score_perda_total($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/leilao-score-perda-total',
        array('placa' => $placa, 'link' => 'leilao-score-perda-total'),
        'SERPRO Consultas: LEILAO SCORE PERDA TOTAL'
    );
}

function serc_apifull_lookup_laudo_veicular($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/laudo-veicular',
        array('placa' => $placa, 'link' => 'laudo-veicular'),
        'SERPRO Consultas: LAUDO VEICULAR'
    );
}

function serc_apifull_lookup_laudo_veicular_params($placa, $chassi)
{
    $payload = array('link' => 'laudo-veicular');
    if (!empty($placa))
        $payload['placa'] = $placa;
    if (!empty($chassi))
        $payload['chassi'] = $chassi;
    return serc_apifull_post_extract_pdf_base64(
        '/api/laudo-veicular',
        $payload,
        'SERPRO Consultas: LAUDO VEICULAR'
    );
}

function serc_apifull_lookup_historico_roubo_furto($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/historico-roubo-furto',
        array('placa' => $placa, 'link' => 'historico-roubo-furto'),
        'SERPRO Consultas: HISTORICO ROUBO FURTO'
    );
}

function serc_apifull_lookup_indice_risco_veicular($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/indice-risco-veicular',
        array('placa' => $placa, 'link' => 'indice-risco-veicular'),
        'SERPRO Consultas: INDICE RISCO VEICULAR'
    );
}

function serc_apifull_lookup_licenciamento_anterior($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/licenciamento-anterior',
        array('placa' => $placa, 'link' => 'licenciamento-anterior'),
        'SERPRO Consultas: LICENCIAMENTO ANTERIOR'
    );
}

function serc_apifull_lookup_ic_proprietario_atual($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/ic-proprietario-atual',
        array('placa' => $placa, 'link' => 'ic-proprietario-atual'),
        'SERPRO Consultas: IC PROPRIETARIO ATUAL'
    );
}

function serc_apifull_lookup_recall($modelo)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/recall',
        array('modelo' => $modelo, 'link' => 'recall'),
        'SERPRO Consultas: RECALL'
    );
}

function serc_apifull_lookup_gravame_detalhamento($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/gravame-detalhamento',
        array('placa' => $placa, 'link' => 'gravame-detalhamento'),
        'SERPRO Consultas: GRAVAME DETALHAMENTO'
    );
}

function serc_apifull_lookup_renajud($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/renajud',
        array('placa' => $placa, 'link' => 'renajud'),
        'SERPRO Consultas: RENAJUD'
    );
}

function serc_apifull_lookup_renainf($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/renainf',
        array('placa' => $placa, 'link' => 'renainf'),
        'SERPRO Consultas: RENAINF'
    );
}

function serc_apifull_lookup_fipe($marca, $modelo, $ano)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/fipe',
        array('marca' => $marca, 'modelo' => $modelo, 'ano' => $ano, 'link' => 'fipe'),
        'SERPRO Consultas: FIPE'
    );
}

function serc_apifull_lookup_sinistro($placa)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/sinistro',
        array('placa' => $placa, 'link' => 'sinistro'),
        'SERPRO Consultas: SINISTRO'
    );
}

function serc_apifull_lookup_serasa_premium($cpf)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/serasa-premium',
        array('cpf' => $cpf, 'link' => 'serasa-premium'),
        'SERPRO Consultas: SERASA PREMIUM'
    );
}

function serc_apifull_lookup_ic_basico_score($cpf)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/ic-basico-score',
        array('cpf' => $cpf, 'link' => 'ic-basico-score'),
        'SERPRO Consultas: IC BASICO SCORE'
    );
}

function serc_apifull_lookup_scpc_boa_vista($cpf)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/scpc-boa-vista',
        array('cpf' => $cpf, 'link' => 'scpc-boa-vista'),
        'SERPRO Consultas: SCPC BOA VISTA'
    );
}

function serc_apifull_lookup_bacen($cpf)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/bacen',
        array('cpf' => $cpf, 'link' => 'bacen'),
        'SERPRO Consultas: BACEN'
    );
}

function serc_apifull_lookup_quod($cpf)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/quod',
        array('cpf' => $cpf, 'link' => 'quod'),
        'SERPRO Consultas: QUOD'
    );
}

function serc_apifull_lookup_spc_brasil_cenprot($cpf)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/spc-brasil-cenprot',
        array('cpf' => $cpf, 'link' => 'spc-brasil-cenprot'),
        'SERPRO Consultas: SPC BRASIL CENPROT'
    );
}

function serc_apifull_lookup_spc_brasil_serasa($cpf)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/spc-brasil-serasa',
        array('cpf' => $cpf, 'link' => 'spc-brasil-serasa'),
        'SERPRO Consultas: SPC BRASIL SERASA'
    );
}

function serc_apifull_lookup_dividas_bancrias_cpf($cpf)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/dividas-bancrias-cpf',
        array('cpf' => $cpf, 'link' => 'dividas-bancrias-cpf'),
        'SERPRO Consultas: DIVIDAS BANCRIAS CPF'
    );
}

function serc_apifull_lookup_cadastrais_score_dividas($cpf)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/cadastrais-score-dividas',
        array('cpf' => $cpf, 'link' => 'cadastrais-score-dividas'),
        'SERPRO Consultas: CADASTRAIS SCORE DIVIDAS'
    );
}

function serc_apifull_lookup_cadastrais_score_dividas_cp($cpf)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/cadastrais-score-dividas-cp',
        array('cpf' => $cpf, 'link' => 'cadastrais-score-dividas-cp'),
        'SERPRO Consultas: CADASTRAIS SCORE DIVIDAS CP'
    );
}

function serc_apifull_lookup_scr_bacen_score($cpf)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/scr-bacen-score',
        array('cpf' => $cpf, 'link' => 'scr-bacen-score'),
        'SERPRO Consultas: SCR BACEN SCORE'
    );
}

function serc_apifull_lookup_protesto_nacional_cenprot($cpf)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/protesto-nacional-cenprot',
        array('cpf' => $cpf, 'link' => 'protesto-nacional-cenprot'),
        'SERPRO Consultas: PROTESTO NACIONAL CENPROT'
    );
}

function serc_apifull_lookup_r_acoes_e_processos_judiciais($cpf)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/r-acoes-e-processos-judiciais',
        array('cpf' => $cpf, 'link' => 'r-acoes-e-processos-judiciais'),
        'SERPRO Consultas: ACOES E PROCESSOS JUDICIAIS'
    );
}

function serc_apifull_lookup_dossie_juridico($cpf)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/dossie-juridico',
        array('cpf' => $cpf, 'link' => 'dossie-juridico'),
        'SERPRO Consultas: DOSSIE JURIDICO'
    );
}

function serc_apifull_lookup_certidao_nacional_debitos_trabalhistas($cpf)
{
    return serc_apifull_post_extract_pdf_base64(
        '/api/certidao-nacional-debitos-trabalhistas',
        array('cpf' => $cpf, 'link' => 'certidao-nacional-debitos-trabalhistas'),
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
    $k = 'serc_rl_lookup_' . $user_id;
    $c = intval(get_transient($k));
    if ($c >= 30)
        wp_send_json_error('rate_limit', 429);
    set_transient($k, $c + 1, 3600);
    $type = sanitize_text_field($_POST['type'] ?? '');
    // Valida entradas por tipo
    switch ($type) {
        case 'cnpj':
            header('Cache-Control: no-cache');
            $cnpj = preg_replace('/\D+/', '', $_POST['cnpj'] ?? '');
            if (strlen($cnpj) !== 14)
                wp_send_json_error('invalid_input', 400);
            break;
        case 'cpf':
            // Ensure no caching for CPF external API request
            header('Cache-Control: no-cache');
            $cpf = preg_replace('/\D+/', '', $_POST['cpf'] ?? '');
            if (strlen($cpf) !== 11)
                wp_send_json_error('invalid_input', 400);
            break;
        case 'cpf_renda':
            header('Cache-Control: no-cache');
            $cpf = preg_replace('/\D+/', '', $_POST['cpf'] ?? '');
            if (strlen($cpf) !== 11)
                wp_send_json_error('invalid_input', 400);
            break;
        case 'ic_nome':
            header('Cache-Control: no-cache');
            $name = sanitize_text_field($_POST['name'] ?? '');
            $state = strtoupper(preg_replace('/[^A-Za-z]/', '', $_POST['state'] ?? ''));
            if (empty($name) || strlen($state) !== 2)
                wp_send_json_error('invalid_input', 400);
            break;
        case 'ic_telefone':
            header('Cache-Control: no-cache');
            $ddd = preg_replace('/\D+/', '', $_POST['ddd'] ?? '');
            $telefone = preg_replace('/\D+/', '', $_POST['telefone'] ?? '');
            $state = strtoupper(preg_replace('/[^A-Za-z]/', '', $_POST['state'] ?? ''));
            if (!preg_match('/^\d{2}$/', $ddd))
                wp_send_json_error('invalid_input', 400);
            if (!preg_match('/^\d{8,9}$/', $telefone))
                wp_send_json_error('invalid_input', 400);
            if (strlen($state) !== 2)
                wp_send_json_error('invalid_input', 400);
            break;
        case 'ic_placa':
            header('Cache-Control: no-cache');
            $placa = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['placa'] ?? ''));
            if (!preg_match('/^([A-Z]{3}\d{4}|[A-Z]{3}[0-9A-Z][0-9]{2})$/', $placa))
                wp_send_json_error('invalid_input', 400);
            break;
        case 'ic_cnh':
            header('Cache-Control: no-cache');
            $cpf = preg_replace('/\D+/', '', $_POST['cpf'] ?? '');
            if (strlen($cpf) !== 11)
                wp_send_json_error('invalid_input', 400);
            break;
        case 'agregados_basica_propria':
            header('Cache-Control: no-cache');
            $param = sanitize_text_field($_POST['param'] ?? '');
            if (empty($param))
                wp_send_json_error('invalid_input', 400);
            break;
        case 'bin_estadual':
            header('Cache-Control: no-cache');
            $estado = strtoupper(preg_replace('/[^A-Za-z]/', '', $_POST['estado'] ?? ''));
            if (strlen($estado) !== 2)
                wp_send_json_error('invalid_input', 400);
            break;
        case 'bin_nacional':
            header('Cache-Control: no-cache');
            $cpf = preg_replace('/\D+/', '', $_POST['cpf'] ?? '');
            if (strlen($cpf) !== 11)
                wp_send_json_error('invalid_input', 400);
            break;
        case 'foto_leilao':
            header('Cache-Control: no-cache');
            $leilao_id = preg_replace('/\D+/', '', $_POST['leilaoId'] ?? '');
            if (empty($leilao_id))
                wp_send_json_error('invalid_input', 400);
            break;
        case 'leilao':
            header('Cache-Control: no-cache');
            $filtro = sanitize_text_field($_POST['filtro'] ?? '');
            if (empty($filtro))
                wp_send_json_error('invalid_input', 400);
            break;
        case 'leilao_score_perda_total':
        case 'historico_roubo_furto':
        case 'indice_risco_veicular':
        case 'licenciamento_anterior':
        case 'ic_proprietario_atual':
        case 'gravame_detalhamento':
        case 'renajud':
        case 'renainf_placa':
        case 'sinistro':
            header('Cache-Control: no-cache');
            $placa = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['placa'] ?? ''));
            if (!preg_match('/^([A-Z]{3}\d{4}|[A-Z]{3}[0-9A-Z][0-9]{2})$/', $placa))
                wp_send_json_error('invalid_input', 400);
            break;
        case 'recall':
            header('Cache-Control: no-cache');
            $modelo = sanitize_text_field($_POST['modelo'] ?? '');
            if (empty($modelo))
                wp_send_json_error('invalid_input', 400);
            break;
        case 'fipe':
            header('Cache-Control: no-cache');
            $marca = sanitize_text_field($_POST['marca'] ?? '');
            $modelo = sanitize_text_field($_POST['modelo'] ?? '');
            $ano = preg_replace('/\D+/', '', $_POST['ano'] ?? '');
            if (empty($marca) || empty($modelo) || strlen($ano) !== 4)
                wp_send_json_error('invalid_input', 400);
            break;
        case 'serasa_premium':
        case 'ic_basico_score':
        case 'scpc_boa_vista':
        case 'bacen':
        case 'quod':
        case 'spc_brasil_cenprot':
        case 'spc_brasil_serasa':
        case 'dividas_bancrias_cpf':
        case 'cadastrais_score_dividas':
        case 'cadastrais_score_dividas_cp':
        case 'scr_bacen_score':
        case 'protesto_nacional_cenprot':
        case 'r_acoes_e_processos_judiciais':
        case 'dossie_juridico_cpf':
        case 'certidao_nacional_debitos_trabalhistas':
            header('Cache-Control: no-cache');
            $cpf = preg_replace('/\D+/', '', $_POST['cpf'] ?? '');
            if (strlen($cpf) !== 11)
                wp_send_json_error('invalid_input', 400);
            break;
        case 'dossie_juridico':
            $doc_type = sanitize_text_field($_POST['doc_type'] ?? 'cpf');
            $document = preg_replace('/\D+/', '', $_POST['document'] ?? '');
            if ($doc_type === 'cpf') {
                if (strlen($document) !== 11)
                    wp_send_json_error('invalid_input', 400);
            } else {
                if (strlen($document) !== 14)
                    wp_send_json_error('invalid_input', 400);
            }
            break;
        case 'crlv':
        case 'proprietario_placa':
            $placa = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['placa'] ?? ''));
            if (!preg_match('/^([A-Z]{3}\d{4}|[A-Z]{3}[0-9A-Z][0-9]{2})$/', $placa))
                wp_send_json_error('invalid_input', 400);
            break;
        case 'renainf':
            $placa = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['placa'] ?? ''));
            $renavam = preg_replace('/\D+/', '', $_POST['renavam'] ?? '');
            if (!preg_match('/^([A-Z]{3}\d{4}|[A-Z]{3}[0-9A-Z][0-9]{2})$/', $placa))
                wp_send_json_error('invalid_input', 400);
            if (!preg_match('/^\d{9,11}$/', $renavam))
                wp_send_json_error('invalid_input', 400);
            break;
        case 'gravame':
            $chassi = strtoupper(preg_replace('/[^A-HJ-NPR-Z0-9]/', '', $_POST['chassi'] ?? ''));
            if (!preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $chassi))
                wp_send_json_error('invalid_input', 400);
            break;
        case 'laudo_veicular':
            $placa = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['placa'] ?? ''));
            $chassi = strtoupper(preg_replace('/[^A-HJ-NPR-Z0-9]/', '', $_POST['chassi'] ?? ''));
            $placa_ok = $placa && preg_match('/^([A-Z]{3}\d{4}|[A-Z]{3}[0-9A-Z][0-9]{2})$/', $placa);
            $chassi_ok = $chassi && preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $chassi);
            if (!$placa_ok && !$chassi_ok)
                wp_send_json_error('invalid_input', 400);
            break;
        case 'scpc_bv_plus_v2':
        case 'srs_premium':
            $cpf = preg_replace('/\D+/', '', $_POST['cpf'] ?? '');
            if (strlen($cpf) !== 11)
                wp_send_json_error('invalid_input', 400);
            break;
        default:
            wp_send_json_error('invalid_type', 400);
    }

    $debit_info = serc_wallet_debit($user_id, $type);
    if ($debit_info === false) {
        $purchase_url = '';
        if (function_exists('wc_get_page_permalink')) {
            $purchase_url = wc_get_page_permalink('shop');
        } elseif (function_exists('wc_get_page_id')) {
            $shop_id = wc_get_page_id('shop');
            if ($shop_id)
                $purchase_url = get_permalink($shop_id);
        }
        if (empty($purchase_url)) {
            $purchase_url = home_url('/');
        }
        wp_send_json_error(array('code' => 'no_quota', 'purchase_url' => $purchase_url), 402);
    }

    $pdf_base64 = null;
    if ($type === 'cpf') {
        $token = get_option('serc_apifull_token', '');
        if (!empty($token)) {
            $auth = $token;
            if (stripos($auth, 'Bearer ') !== 0) {
                $auth = 'Bearer ' . $auth;
            }
            $req = wp_remote_post('https://api.apifull.com.br/api/ic-cpf-completo', array(
                'headers' => array(
                    'Authorization' => $auth,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Cache-Control' => 'no-cache',
                ),
                'body' => wp_json_encode(array('cpf' => $cpf, 'link' => 'ic-cpf-completo')),
                'timeout' => 30,
            ));
            if (!is_wp_error($req)) {
                $code = wp_remote_retrieve_response_code($req);
                $body = wp_remote_retrieve_body($req);
                error_log('SERPRO Consultas: CPF API code=' . $code);
                $decoded = json_decode($body, true);
                if (is_array($decoded)) {
                    if (isset($decoded['pdfBase64']) && is_string($decoded['pdfBase64'])) {
                        $pdf_base64 = $decoded['pdfBase64'];
                    } else {
                        foreach ($decoded as $k => $v) {
                            if (is_array($v) && isset($v['pdfBase64']) && is_string($v['pdfBase64'])) {
                                $pdf_base64 = $v['pdfBase64'];
                                break;
                            }
                        }
                    }
                }
            }
        }
    } elseif ($type === 'cpf_renda') {
        $pdf_base64 = serc_apifull_lookup_cpf_renda($cpf);
    } elseif ($type === 'ic_nome') {
        $pdf_base64 = serc_apifull_lookup_ic_nome($name, $state);
    } elseif ($type === 'ic_telefone') {
        $pdf_base64 = serc_apifull_lookup_ic_telefone($ddd, $telefone, $state);
    } elseif ($type === 'ic_placa') {
        $pdf_base64 = serc_apifull_lookup_ic_placa($placa);
    } elseif ($type === 'ic_cnh') {
        $pdf_base64 = serc_apifull_lookup_ic_cnh($cpf);
    } elseif ($type === 'cnpj') {
        $pdf_base64 = serc_apifull_lookup_cnpj($cnpj);
    } elseif ($type === 'crlv') {
        $pdf_base64 = serc_apifull_lookup_crlv($placa);
    } elseif ($type === 'proprietario_placa') {
        $pdf_base64 = serc_apifull_lookup_proprietario_placa($placa);
    } elseif ($type === 'gravame') {
        $pdf_base64 = serc_apifull_lookup_gravame($chassi);
    } elseif ($type === 'renainf') {
        $pdf_base64 = serc_apifull_lookup_renainf_placa_renavam($placa, $renavam);
    } elseif ($type === 'scpc_bv_plus_v2') {
        $pdf_base64 = serc_apifull_lookup_scpc_bv_plus_v2($cpf);
    } elseif ($type === 'srs_premium') {
        $pdf_base64 = serc_apifull_lookup_srs_premium($cpf);
    } elseif ($type === 'agregados_basica_propria') {
        $pdf_base64 = serc_apifull_lookup_agregados_basica_propria($param);
    } elseif ($type === 'bin_estadual') {
        $pdf_base64 = serc_apifull_lookup_bin_estadual($estado);
    } elseif ($type === 'bin_nacional') {
        $pdf_base64 = serc_apifull_lookup_bin_nacional($cpf);
    } elseif ($type === 'foto_leilao') {
        $pdf_base64 = serc_apifull_lookup_foto_leilao($leilao_id);
    } elseif ($type === 'leilao') {
        $pdf_base64 = serc_apifull_lookup_leilao($filtro);
    } elseif ($type === 'leilao_score_perda_total') {
        $pdf_base64 = serc_apifull_lookup_leilao_score_perda_total($placa);
    } elseif ($type === 'historico_roubo_furto') {
        $pdf_base64 = serc_apifull_lookup_historico_roubo_furto($placa);
    } elseif ($type === 'indice_risco_veicular') {
        $pdf_base64 = serc_apifull_lookup_indice_risco_veicular($placa);
    } elseif ($type === 'licenciamento_anterior') {
        $pdf_base64 = serc_apifull_lookup_licenciamento_anterior($placa);
    } elseif ($type === 'ic_proprietario_atual') {
        $pdf_base64 = serc_apifull_lookup_ic_proprietario_atual($placa);
    } elseif ($type === 'laudo_veicular') {
        $pdf_base64 = serc_apifull_lookup_laudo_veicular_params(!empty($placa_ok) ? $placa : '', !empty($chassi_ok) ? $chassi : '');
    } elseif ($type === 'recall') {
        $pdf_base64 = serc_apifull_lookup_recall($modelo);
    } elseif ($type === 'gravame_detalhamento') {
        $pdf_base64 = serc_apifull_lookup_gravame_detalhamento($placa);
    } elseif ($type === 'renajud') {
        $pdf_base64 = serc_apifull_lookup_renajud($placa);
    } elseif ($type === 'renainf_placa') {
        $pdf_base64 = serc_apifull_lookup_renainf($placa);
    } elseif ($type === 'sinistro') {
        $pdf_base64 = serc_apifull_lookup_sinistro($placa);
    } elseif ($type === 'fipe') {
        $pdf_base64 = serc_apifull_lookup_fipe($marca, $modelo, $ano);
    } elseif ($type === 'serasa_premium') {
        $pdf_base64 = serc_apifull_lookup_serasa_premium($cpf);
    } elseif ($type === 'ic_basico_score') {
        $pdf_base64 = serc_apifull_lookup_ic_basico_score($cpf);
    } elseif ($type === 'scpc_boa_vista') {
        $pdf_base64 = serc_apifull_lookup_scpc_boa_vista($cpf);
    } elseif ($type === 'bacen') {
        $pdf_base64 = serc_apifull_lookup_bacen($cpf);
    } elseif ($type === 'quod') {
        $pdf_base64 = serc_apifull_lookup_quod($cpf);
    } elseif ($type === 'spc_brasil_cenprot') {
        $pdf_base64 = serc_apifull_lookup_spc_brasil_cenprot($cpf);
    } elseif ($type === 'spc_brasil_serasa') {
        $pdf_base64 = serc_apifull_lookup_spc_brasil_serasa($cpf);
    } elseif ($type === 'dividas_bancrias_cpf') {
        $pdf_base64 = serc_apifull_lookup_dividas_bancrias_cpf($cpf);
    } elseif ($type === 'cadastrais_score_dividas') {
        $pdf_base64 = serc_apifull_lookup_cadastrais_score_dividas($cpf);
    } elseif ($type === 'cadastrais_score_dividas_cp') {
        $pdf_base64 = serc_apifull_lookup_cadastrais_score_dividas_cp($cpf);
    } elseif ($type === 'scr_bacen_score') {
        $pdf_base64 = serc_apifull_lookup_scr_bacen_score($cpf);
    } elseif ($type === 'protesto_nacional_cenprot') {
        $pdf_base64 = serc_apifull_lookup_protesto_nacional_cenprot($cpf);
    } elseif ($type === 'r_acoes_e_processos_judiciais') {
        $pdf_base64 = serc_apifull_lookup_r_acoes_e_processos_judiciais($cpf);
    } elseif ($type === 'dossie_juridico_cpf') {
        $pdf_base64 = serc_apifull_lookup_dossie_juridico($cpf);
    } elseif ($type === 'certidao_nacional_debitos_trabalhistas') {
        $pdf_base64 = serc_apifull_lookup_certidao_nacional_debitos_trabalhistas($cpf);
    }
    $consulta_id = wp_insert_post(array(
        'post_type' => 'serc_consulta',
        'post_status' => 'private',
        'post_author' => $user_id,
        'post_title' => 'Consulta ' . $type . ' #' . time(),
    ));
    $filename = 'consulta-' . $type . '-' . $user_id . '-' . time() . '.pdf';
    $upload_status = 'no_pdf';
    $download_url = null;
    if ($consulta_id) {
        update_post_meta($consulta_id, 'type', $type);
        update_post_meta($consulta_id, 'filename', $filename);
        if ($pdf_base64) {
            update_post_meta($consulta_id, 'pdf_base64', $pdf_base64);
            $up = serc_upload_pdf_to_storage($filename, $pdf_base64);
            $upload_status = !empty($up['ok']) ? 'uploaded' : ('failed:' . ($up['message'] ?? ''));
            $hash = serc_consulta_ensure_hash($consulta_id);
            $download_url = admin_url('admin-ajax.php?action=serc_download&hash=' . $hash);
            error_log('SERPRO Consultas: consulta ' . $consulta_id . ' upload_status=' . $upload_status);
        }
        update_post_meta($consulta_id, 'upload_status', $upload_status);
    }
    wp_send_json_success(array(
        'quota' => $debit_info['balance'],
        'debited' => $debit_info['debited'],
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
// Opções via painel removidas; toda configuração é por produto/variação.

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

function serc_get_consultations_config_for_item($item)
{
    $pid = $item->get_product_id();
    $vid = $item->get_variation_id();
    $raw = '';
    if ($vid) {
        $raw = get_post_meta($vid, 'serc_consultations_config', true);
        if (empty($raw))
            $raw = get_post_meta($pid, 'serc_consultations_config', true);
    } else {
        $raw = get_post_meta($pid, 'serc_consultations_config', true);
    }
    $config = array();
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded))
            $config = $decoded;
    }
    return $config; // formato: [type] => ['enabled'=>bool, 'debit'=>float]
}

// Helper: obtém configuração por produto/variação (sem depender de item de pedido)
function serc_get_consultations_config_for_ids($pid, $vid)
{
    $raw = '';
    if ($vid) {
        $raw = get_post_meta($vid, 'serc_consultations_config', true);
        if (empty($raw))
            $raw = get_post_meta($pid, 'serc_consultations_config', true);
    } else {
        $raw = get_post_meta($pid, 'serc_consultations_config', true);
    }
    $config = array();
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded))
            $config = $decoded;
    }
    return $config; // formato: [type] => ['enabled'=>bool, 'debit'=>float]
}

function serc_get_wallet($user_id)
{
    $raw = get_user_meta($user_id, 'serc_credit_wallet', true);
    $wallet = array();
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded))
            $wallet = $decoded;
    }
    return $wallet;
}

function serc_set_wallet($user_id, $wallet)
{
    update_user_meta($user_id, 'serc_credit_wallet', wp_json_encode($wallet));
    $sum = 0.0;
    foreach ($wallet as $k => $entry) {
        $sum += floatval(isset($entry['balance']) ? $entry['balance'] : 0);
    }
    update_user_meta($user_id, 'serc_credit_balance', round($sum, 2));
}

/* =========================
   Lógica principal: aplicar cotas fixas
   ========================= */
function serc_handle_order_completed($order_id_or_obj)
{
    $order = is_object($order_id_or_obj) ? $order_id_or_obj : wc_get_order($order_id_or_obj);
    if (!$order)
        return;
    $user_id = $order->get_user_id();
    if (!$user_id)
        return;
    $wallet = serc_get_wallet($user_id);
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
            $add = round($general * max(1, $qty), 2);
            $key = ($vid ? ('v' . $vid) : ('p' . $pid));
            if (!isset($wallet[$key])) {
                $wallet[$key] = array(
                    'balance' => 0.0,
                    'product_id' => $pid,
                    'variation_id' => $vid,
                );
            }
            $wallet[$key]['balance'] = round(floatval($wallet[$key]['balance']) + $add, 2);
            $total_add += $add;
        }
    }
    if ($total_add > 0) {
        serc_set_wallet($user_id, $wallet);
        $new_balance = floatval(get_user_meta($user_id, 'serc_credit_balance', true));
        error_log("SERPRO Consultas: Pedido #{$order->get_id()} adicionou +{$total_add} créditos (geral) ao usuário {$user_id}. Saldo: {$new_balance}.");
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

/* (Removido) Função antiga de consulta direta CNPJ — usamos serc_lookup(type) */
/* =========================
   WooCommerce: Campo por produto
   ========================= */
function serc_wc_product_field()
{
    global $post;
    $types = serc_get_consultation_types();
    $raw = get_post_meta($post->ID, 'serc_consultations_config', true);
    $cfg = array();
    if (!empty($raw)) {
        $tmp = json_decode($raw, true);
        if (is_array($tmp))
            $cfg = $tmp;
    }
    echo '<div class="options_group">';
    $general = get_post_meta($post->ID, 'serc_general_credits', true);
    $general = $general === '' ? '' : esc_attr(floatval($general));
    echo '<p><strong>Créditos por compra (GERAL)</strong>: <input type="number" step="0.01" min="0" name="serc_general_credits" value="' . $general . '" /></p>';
    echo '<p><strong>Consultas</strong> — habilite e configure por tipo (apenas débito):</p>';
    echo '<table class="widefat" style="margin-top:8px">';
    echo '<thead><tr><th>Tipo</th><th>Habilitar</th><th>Débito por consulta</th></tr></thead><tbody>';
    foreach ($types as $code => $label) {
        $enabled = !empty($cfg[$code]['enabled']);
        $debit = isset($cfg[$code]['debit']) ? floatval($cfg[$code]['debit']) : '';
        echo '<tr>';
        echo '<td>' . esc_html($label) . '</td>';
        echo '<td><input type="checkbox" name="serc_consultations_config[' . esc_attr($code) . '][enabled]" ' . ($enabled ? 'checked' : '') . ' /></td>';
        echo '<td><input type="number" step="0.01" min="0" name="serc_consultations_config[' . esc_attr($code) . '][debit]" value="' . esc_attr($debit) . '" /></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
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
    // Salva configuração por tipo (apenas débito)
    if (isset($_POST['serc_consultations_config']) && is_array($_POST['serc_consultations_config'])) {
        $types = serc_get_consultation_types();
        $out = array();
        foreach ($types as $code => $label) {
            $row = isset($_POST['serc_consultations_config'][$code]) ? $_POST['serc_consultations_config'][$code] : array();
            $enabled = !empty($row['enabled']);
            $debit = isset($row['debit']) ? floatval(wc_clean($row['debit'])) : 0;
            if ($enabled && $debit > 0) {
                $out[$code] = array('enabled' => true, 'debit' => $debit);
            } elseif ($enabled) {
                // habilitado sem números válidos: registra com zero para feedback
                $out[$code] = array('enabled' => true, 'debit' => max(0, $debit));
            }
        }
        if (!empty($out)) {
            update_post_meta($post_id, 'serc_consultations_config', wp_json_encode($out));
        } else {
            delete_post_meta($post_id, 'serc_consultations_config');
        }
    }
}

function serc_wc_variation_field($loop, $variation_data, $variation)
{
    $types = serc_get_consultation_types();
    $raw = get_post_meta($variation->ID, 'serc_consultations_config', true);
    $cfg = array();
    if (!empty($raw)) {
        $tmp = json_decode($raw, true);
        if (is_array($tmp))
            $cfg = $tmp;
    }
    echo '<div class="form-row form-row-full">';
    // Créditos gerais da variação
    $general = get_post_meta($variation->ID, 'serc_general_credits', true);
    $general = $general === '' ? '' : esc_attr(floatval($general));
    echo '<p><strong>Créditos por compra (GERAL)</strong>: <input type="number" step="0.01" min="0" name="variable_serc_general_credits[' . esc_attr($loop) . ']" value="' . $general . '" /></p>';
    echo '<p><strong>Consultas (variação)</strong> — habilite e configure por tipo (apenas débito):</p>';
    echo '<table class="widefat">';
    echo '<thead><tr><th>Tipo</th><th>Habilitar</th><th>Débito por consulta</th></tr></thead><tbody>';
    foreach ($types as $code => $label) {
        $enabled = !empty($cfg[$code]['enabled']);
        $debit = isset($cfg[$code]['debit']) ? floatval($cfg[$code]['debit']) : '';
        echo '<tr>';
        echo '<td>' . esc_html($label) . '</td>';
        echo '<td><input type="checkbox" name="variable_serc_consultations_config[' . esc_attr($loop) . '][' . esc_attr($code) . '][enabled]" ' . ($enabled ? 'checked' : '') . ' /></td>';
        echo '<td><input type="number" step="0.01" min="0" name="variable_serc_consultations_config[' . esc_attr($loop) . '][' . esc_attr($code) . '][debit]" value="' . esc_attr($debit) . '" /></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
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
    // Salva configuração por tipo (apenas débito)
    if (isset($_POST['variable_serc_consultations_config'][$i]) && is_array($_POST['variable_serc_consultations_config'][$i])) {
        $types = serc_get_consultation_types();
        $out = array();
        $rows = $_POST['variable_serc_consultations_config'][$i];
        foreach ($types as $code => $label) {
            $row = isset($rows[$code]) ? $rows[$code] : array();
            $enabled = !empty($row['enabled']);
            $debit = isset($row['debit']) ? floatval(wc_clean($row['debit'])) : 0;
            if ($enabled && $debit > 0) {
                $out[$code] = array('enabled' => true, 'debit' => $debit);
            } elseif ($enabled) {
                $out[$code] = array('enabled' => true, 'debit' => max(0, $debit));
            }
        }
        if (!empty($out)) {
            update_post_meta($variation_id, 'serc_consultations_config', wp_json_encode($out));
        } else {
            delete_post_meta($variation_id, 'serc_consultations_config');
        }
    }
}

// (Removido) Funções antigas de consulta/débito únicos — agora usamos configuração por tipo

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
            echo '<tr><td>' . esc_html(get_the_date('d/m/Y H:i')) . '</td><td>' . esc_html($t) . '</td><td>' . esc_html($st ?: 'n/a') . '</td><td><a class="button" href="' . esc_url($url) . '">download</a></td></tr>';
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
        $req = wp_remote_post('https://api.apifull.com.br/storage/files', array('headers' => array('Authorization' => $auth, 'Accept' => 'application/pdf', 'Content-Type' => 'application/json'), 'body' => wp_json_encode(array('name' => $filename)), 'timeout' => 30));
        if (is_wp_error($req)) {
            error_log('SERPRO Consultas: storage fetch error ' . $req->get_error_message());
            status_header(502);
            exit;
        }
        $ct = wp_remote_retrieve_header($req, 'content-type');
        if (strpos(strtolower($ct), 'application/pdf') === false) {
            error_log('SERPRO Consultas: storage fetch 415 (bad mime) ct=' . $ct);
            status_header(415);
            exit;
        }
        $content = wp_remote_retrieve_body($req);
    }
    update_post_meta($pid, 'hash_used', 1);
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
