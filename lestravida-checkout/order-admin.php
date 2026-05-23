<?php
/**
 * order-admin.php
 */

if (!defined('ABSPATH')) exit;

final class LVC_Order_Admin {

    public static function render_participant_meta($order): void {

        if (!$order instanceof WC_Order) {
            return;
        }

        $fields = [
            '_billing_school'      => 'Sekolah/ Universitas/ Tempat Kerja',
            '_billing_age'         => 'Umur',
            '_billing_instagram'   => 'Instagram',
            '_billing_source_info' => 'Sumber Informasi',
        ];

        echo '<div class="address lvc-order-admin-box">';
        echo '<h3>Data Peserta</h3>';

        foreach ($fields as $key => $label) {
            $value = $order->get_meta($key);

            if ($value === '') {
                continue;
            }

            echo '<p>';
            echo '<strong>' . esc_html($label) . ':</strong> ';
            echo esc_html($value);
            echo '</p>';
        }

        self::render_drive_files($order);

        echo '</div>';
    }

    private static function render_drive_files(WC_Order $order): void {

        echo '<h3>Bukti Google Drive</h3>';

        $error = $order->get_meta('_lvc_drive_last_error');

        if ($error) {
            echo '<h3>Google Drive Error</h3>';
            echo '<p style="color:red;">' . esc_html($error) . '</p>';
        }

        $event_drive_files = $order->get_meta('_lvc_event_drive_files');

        if (is_array($event_drive_files) && !empty($event_drive_files)) {
            self::render_event_drive_files($order, $event_drive_files);
            return;
        }

        self::render_legacy_drive_files($order);
    }

    private static function render_event_drive_files(WC_Order $order, array $event_drive_files): void {

        $labels = [
            'bukti_lestravida_token' => 'Bukti Follow Lestravida',
            'bukti_lvdaerah_token'   => 'Bukti Follow Lestravida Daerah',
            'bukti_share_token'      => 'Bukti Share Poster',
        ];

        $has_drive = false;

        foreach ($event_drive_files as $product_id => $files) {
            $product_id = absint($product_id);

            if (!$product_id || !is_array($files)) {
                continue;
            }

            $product = wc_get_product($product_id);
            $event_name = $product ? $product->get_name() : 'Event #' . $product_id;

            echo '<div class="lvc-order-event-files">';
            echo '<h4>' . esc_html($event_name) . '</h4>';

            $has_event_file = false;

            foreach ($labels as $key => $label) {
                $url = $files[$key] ?? '';

                if (!$url) {
                    continue;
                }

                $has_drive = true;
                $has_event_file = true;

                echo '<p>';
                echo '<strong>' . esc_html($label) . ':</strong> ';
                echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">Buka File</a>';
                echo '</p>';
            }

            if (!$has_event_file) {
                echo '<p><em>Belum ada bukti untuk event ini.</em></p>';
            }

            echo '</div>';
        }

        if (!$has_drive) {
            echo '<p><em>Belum ada bukti Google Drive.</em></p>';
        }
    }

    private static function render_legacy_drive_files(WC_Order $order): void {

        $drive_fields = [
            '_lvc_drive_bukti_lestravida' => 'Bukti Follow Lestravida',
            '_lvc_drive_bukti_lvdaerah'   => 'Bukti Follow Lestravida Daerah',
            '_lvc_drive_bukti_share'      => 'Bukti Share Poster',
        ];

        $has_drive = false;

        foreach ($drive_fields as $key => $label) {
            $url = $order->get_meta($key);

            if ($url === '') {
                continue;
            }

            $has_drive = true;

            echo '<p>';
            echo '<strong>' . esc_html($label) . ':</strong> ';
            echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">Buka File</a>';
            echo '</p>';
        }

        if (!$has_drive) {
            echo '<p><em>Belum ada bukti Google Drive.</em></p>';
        }
    }

    public static function enqueue_admin_assets($hook): void {

        $css_path = LVC_DIR . '/assets/css/admin.css';

        $css_version = file_exists($css_path)
            ? filemtime($css_path)
            : null;

        wp_enqueue_style(
            'lvc-admin',
            LVC_URL . 'assets/css/admin.css',
            [],
            $css_version
        );
    }

    public static function hooks(): void {

        add_action(
            'woocommerce_admin_order_data_after_billing_address',
            [__CLASS__, 'render_participant_meta'],
            20
        );

        add_action(
            'admin_enqueue_scripts',
            [__CLASS__, 'enqueue_admin_assets']
        );
    }
}

add_action('init', ['LVC_Order_Admin', 'hooks']);