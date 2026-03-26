<?php
/**
 * View: Settings / Configuração
 * Groups: Account Details, Address, Wishlist, Logout
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
$current_user = wp_get_current_user();
$settings_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'account';

if (!$is_ajax) {
    echo '<div class="area-content">';
}
?>

<div class="account-container settings-container">
    <div class="account-header">
        <h2><i class="ph-fill ph-gear"></i> Configuração</h2>
        <p class="account-description">Gerencie seus dados pessoais e preferências</p>
    </div>

    <!-- Settings Internal Tabs -->
    <div class="settings-tabs">
        <button class="settings-tab active" data-tab="account">
            <i class="ph-bold ph-user-circle"></i> Detalhes da Conta
        </button>
        <button class="settings-tab" data-tab="address">
            <i class="ph-bold ph-map-pin"></i> Endereço
        </button>
        <button class="settings-tab" data-tab="wishlist">
            <i class="ph-bold ph-heart"></i> Lista de Desejos
        </button>
    </div>

    <!-- TAB: Account Details -->
    <div class="settings-panel active" id="settings-panel-account">
        <h3>Detalhes da Conta</h3>
        <form class="settings-form" id="serc-account-form">
            <div class="settings-form-grid">
                <div class="form-group">
                    <label for="account_first_name">Nome</label>
                    <input type="text" id="account_first_name" name="first_name"
                        value="<?php echo esc_attr($current_user->first_name); ?>" />
                </div>
                <div class="form-group">
                    <label for="account_last_name">Sobrenome</label>
                    <input type="text" id="account_last_name" name="last_name"
                        value="<?php echo esc_attr($current_user->last_name); ?>" />
                </div>
                <div class="form-group form-group-full">
                    <label for="account_display_name">Nome de exibição</label>
                    <input type="text" id="account_display_name" name="display_name"
                        value="<?php echo esc_attr($current_user->display_name); ?>" />
                </div>
                <div class="form-group form-group-full">
                    <label for="account_email">Email</label>
                    <input type="email" id="account_email" name="email"
                        value="<?php echo esc_attr($current_user->user_email); ?>" />
                </div>
            </div>

            <h4 style="margin-top:24px; margin-bottom:12px; color:#555;">Alteração de Senha</h4>
            <div class="settings-form-grid">
                <div class="form-group">
                    <label for="password_current">Senha atual (deixe em branco para manter)</label>
                    <input type="password" id="password_current" name="password_current" />
                </div>
                <div class="form-group">
                    <label for="password_new">Nova senha</label>
                    <input type="password" id="password_new" name="password_new" />
                </div>
                <div class="form-group">
                    <label for="password_confirm">Confirmar nova senha</label>
                    <input type="password" id="password_confirm" name="password_confirm" />
                </div>
            </div>

            <div class="settings-form-actions">
                <button type="submit" class="btn-settings-save">
                    <i class="ph-bold ph-floppy-disk"></i> Salvar alterações
                </button>
                <span class="settings-save-status" id="account-save-status"></span>
            </div>
        </form>
    </div>

    <!-- TAB: Address -->
    <div class="settings-panel" id="settings-panel-address">
        <h3>Endereço</h3>
        <?php
        // Get billing address fields
        $billing_fields = array(
            'billing_address_1' => 'Endereço',
            'billing_address_2' => 'Complemento',
            'billing_city' => 'Cidade',
            'billing_state' => 'Estado',
            'billing_postcode' => 'CEP',
            'billing_country' => 'País',
            'billing_phone' => 'Telefone',
        );
        ?>
        <form class="settings-form" id="serc-address-form">
            <div class="settings-form-grid">
                <?php foreach ($billing_fields as $field_key => $field_label):
                    $value = get_user_meta($user_id, $field_key, true);
                    ?>
                    <div class="form-group <?php echo in_array($field_key, ['billing_address_1', 'billing_address_2']) ? 'form-group-full' : ''; ?>">
                        <label for="<?php echo esc_attr($field_key); ?>"><?php echo esc_html($field_label); ?></label>
                        <input type="text" id="<?php echo esc_attr($field_key); ?>" name="<?php echo esc_attr($field_key); ?>"
                            value="<?php echo esc_attr($value); ?>" />
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="settings-form-actions">
                <button type="submit" class="btn-settings-save">
                    <i class="ph-bold ph-floppy-disk"></i> Salvar endereço
                </button>
                <span class="settings-save-status" id="address-save-status"></span>
            </div>
        </form>
    </div>

    <!-- TAB: Wishlist -->
    <div class="settings-panel" id="settings-panel-wishlist">
        <h3>Lista de Desejos</h3>
        <?php
        // Check if YITH Wishlist or similar plugin is active
        if (shortcode_exists('yith_wcwl_wishlist')) {
            echo do_shortcode('[yith_wcwl_wishlist]');
        } elseif (shortcode_exists('ti_wishlistsview')) {
            echo do_shortcode('[ti_wishlistsview]');
        } else {
            ?>
            <div class="settings-empty-state">
                <i class="ph-duotone ph-heart" style="font-size:48px; color:#ddd; margin-bottom:12px;"></i>
                <p>Sua lista de desejos está vazia.</p>
                <small style="color:#999;">Navegue pela loja e adicione produtos à sua lista de desejos.</small>
                <a href="<?php echo serc_get_dashboard_url(['view' => 'shop']); ?>" class="btn-shop-now" style="margin-top:16px;">
                    <i class="ph-bold ph-storefront"></i> Ir para a Loja
                </a>
            </div>
            <?php
        }
        ?>
    </div>

</div>

<?php if (!$is_ajax): ?>
</div>
</div>
<?php endif; ?>
