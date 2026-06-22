<?php
/**
 * settings.php
 */

if (!defined('ABSPATH')) exit;

final class LVC_Settings {

    public static function init() {
        if (!class_exists('WooCommerce')) return;
        add_action('admin_menu', [__CLASS__, 'add_settings_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function add_settings_page() {
        add_submenu_page(
            'lestravida',
            'Settings',
            'Settings',
            'manage_woocommerce',
            'lvc-settings',
            [__CLASS__, 'render_page']
        );
    }

    public static function register_settings() {
        $group = 'lvc_settings_group';

        register_setting($group, 'lvc_default_ig_daerah');
        register_setting($group, 'lvc_min_event_gap_days', ['type' => 'integer']);
        register_setting($group, 'lvc_checkout_fee_amount', ['type' => 'integer']);
        register_setting($group, 'lvc_big_image_threshold', ['type' => 'integer']);
        register_setting($group, 'lvc_google_drive_alert_email', ['type' => 'string']);
        
        // T-Shirt Settings
        register_setting($group, 'lvc_tshirt_price_normal', ['type' => 'integer']);
        register_setting($group, 'lvc_tshirt_price_extra', ['type' => 'integer']);
        register_setting($group, 'lvc_tshirt_image_url', ['type' => 'string']);
    }

    public static function render_page() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        ?>
        <div class="wrap">
            <h1>Lestravida Settings</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('lvc_settings_group');
                do_settings_sections('lvc_settings_group');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="lvc_default_ig_daerah">Default IG Daerah</label></th>
                        <td>
                            <input type="text" id="lvc_default_ig_daerah" name="lvc_default_ig_daerah" value="<?php echo esc_attr(get_option('lvc_default_ig_daerah', '@lestravidaaceh')); ?>" class="regular-text" />
                            <p class="description">Format: @username</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lvc_min_event_gap_days">Min Event Gap (Days)</label></th>
                        <td>
                            <input type="number" id="lvc_min_event_gap_days" name="lvc_min_event_gap_days" value="<?php echo esc_attr(get_option('lvc_min_event_gap_days', 7)); ?>" class="small-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lvc_checkout_fee_amount">Checkout Extra Fee</label></th>
                        <td>
                            <input type="number" id="lvc_checkout_fee_amount" name="lvc_checkout_fee_amount" value="<?php echo esc_attr(get_option('lvc_checkout_fee_amount', 4000)); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lvc_big_image_threshold">Max Image Size Threshold</label></th>
                        <td>
                            <input type="number" id="lvc_big_image_threshold" name="lvc_big_image_threshold" value="<?php echo esc_attr(get_option('lvc_big_image_threshold', 1024)); ?>" class="regular-text" />
                            <p class="description">Image will be resized if width/height is larger than this (default WP is 2560).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lvc_google_drive_alert_email">Google Drive Error Alert Email</label></th>
                        <td>
                            <input type="email" id="lvc_google_drive_alert_email" name="lvc_google_drive_alert_email" value="<?php echo esc_attr(get_option('lvc_google_drive_alert_email', '')); ?>" class="regular-text" />
                            <p class="description">Leave empty to disable email notifications. Errors will still be logged to PHP error log.</p>
                        </td>
                    </tr>
                    
                    <!-- T-Shirt Settings -->
                    <tr>
                        <th scope="row" colspan="2">
                            <hr>
                            <h3 style="margin-bottom: 0;">Pengaturan Baju Kegiatan</h3>
                        </th>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lvc_tshirt_price_normal">Harga Baju Ukuran Standar (S, M, L)</label></th>
                        <td>
                            <input type="number" id="lvc_tshirt_price_normal" name="lvc_tshirt_price_normal" value="<?php echo esc_attr(get_option('lvc_tshirt_price_normal', 88000)); ?>" class="regular-text" />
                            <p class="description">Contoh: 88000 (tanpa titik)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lvc_tshirt_price_extra">Harga Baju Ukuran Besar (&gt; XL)</label></th>
                        <td>
                            <input type="number" id="lvc_tshirt_price_extra" name="lvc_tshirt_price_extra" value="<?php echo esc_attr(get_option('lvc_tshirt_price_extra', 98000)); ?>" class="regular-text" />
                            <p class="description">Contoh: 98000 (tanpa titik)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lvc_tshirt_image_url">URL Desain Baju & Size Chart</label></th>
                        <td>
                            <input type="url" id="lvc_tshirt_image_url" name="lvc_tshirt_image_url" value="<?php echo esc_attr(get_option('lvc_tshirt_image_url', 'https://placehold.co/600x400/eeeeee/333333?text=Gambar+Baju+%26+Size+Chart')); ?>" class="large-text" />
                            <p class="description">Masukkan link URL gambar desain baju dari Media Library.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

add_action('plugins_loaded', ['LVC_Settings', 'init']);
