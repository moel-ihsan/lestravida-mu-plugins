<?php
/**
 * thankyou-wa.php
 */

if (!defined('ABSPATH')) exit;

final class LVK_Thankyou_WA {

    const ALLOWED_HOSTS = [
        'chat.whatsapp.com',
        'wa.me',
    ];

    private static $links_cache = [];
    private static $rendered_page_orders = [];
    private static $rendered_email_orders = [];

    public static function is_valid_wa_url($url) {
        $url = trim((string) $url);

        if ($url === '') {
            return false;
        }

        $scheme = strtolower((string) wp_parse_url($url, PHP_URL_SCHEME));
        $host   = strtolower((string) wp_parse_url($url, PHP_URL_HOST));

        if (!in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        return in_array($host, self::ALLOWED_HOSTS, true);
    }

    public static function is_paid_order($order) {
        return $order instanceof WC_Order && $order->is_paid();
    }

    public static function get_order_wa_links($order) {
        if (!$order instanceof WC_Order) {
            return [];
        }

        $order_id = $order->get_id();

        if (isset(self::$links_cache[$order_id])) {
            return self::$links_cache[$order_id];
        }

        $links = [];

        foreach ($order->get_items() as $item) {
            $product_id = (int) $item->get_product_id();

            if (!$product_id) {
                continue;
            }

            $wa_group = trim((string) get_post_meta(
                $product_id,
                LVK_Helper::META_WA_GROUP,
                true
            ));

            if ($wa_group === '' || !self::is_valid_wa_url($wa_group)) {
                continue;
            }

            $key = md5($product_id . '|' . $wa_group);

            $links[$key] = [
                'product_id' => $product_id,
                'event'      => $item->get_name(),
                'url'        => $wa_group,
            ];
        }

        self::$links_cache[$order_id] = array_values($links);

        return self::$links_cache[$order_id];
    }

    public static function render_box($link, $is_email = false) {
        $event = is_array($link) ? ($link['event'] ?? 'Kegiatan') : 'Kegiatan';
        $url   = is_array($link) ? ($link['url'] ?? '') : $link;
    
        if (!$url) {
            return;
        }
    
        if ($is_email) {
            ?>
            <div style="margin:24px 0;padding:20px;border:1px solid #e5e7eb;border-radius:12px;background:#f9fafb;">
                <p style="margin:0 0 8px;color:#374151;font-size:14px;line-height:1.5;">
                    Silakan gabung ke grup peserta untuk kegiatan:
                </p>
    
                <h2 style="margin:0 0 16px;color:#111827;font-size:18px;line-height:1.3;">
                    <?php echo esc_html($event); ?>
                </h2>
    
                <a
                    href="<?php echo esc_url($url); ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    style="display:inline-block;background:#16a34a;color:#ffffff;text-decoration:none;font-weight:700;padding:12px 18px;border-radius:8px;font-size:14px;"
                >
                    Gabung Grup WhatsApp
                </a>
            </div>
            <?php
            return;
        }
    
        ?>
        <div class="lvk-wa-box">
            <p class="lvk-wa-desc">
                Silakan gabung ke grup peserta untuk kegiatan:
            </p>
    
            <h2 class="lvk-wa-title"><?php echo esc_html($event); ?></h2>
    
            <a
                href="<?php echo esc_url($url); ?>"
                target="_blank"
                rel="noopener noreferrer"
                class="lvk-wa-button"
            >
                Gabung Grup WhatsApp
            </a>
        </div>
        <?php
    }

    public static function render_order_page($order) {
        if (!self::is_paid_order($order)) {
            return;
        }

        $order_id = $order->get_id();

        if (isset(self::$rendered_page_orders[$order_id])) {
            return;
        }

        self::$rendered_page_orders[$order_id] = true;

        $links = self::get_order_wa_links($order);

        if (empty($links)) {
            return;
        }

        foreach ($links as $link) {
            self::render_box($link, false);
        }
    }

    public static function render_email($order, $sent_to_admin, $plain_text, $email = null) {
        if ($sent_to_admin || $plain_text) {
            return;
        }

        if (!self::is_paid_order($order)) {
            return;
        }

        $order_id = $order->get_id();

        if (isset(self::$rendered_email_orders[$order_id])) {
            return;
        }

        self::$rendered_email_orders[$order_id] = true;

        $links = self::get_order_wa_links($order);

        if (empty($links)) {
            return;
        }

        foreach ($links as $link) {
            self::render_box($link, true);
        }
    }

    public static function enqueue_assets() {
        if (!is_order_received_page()) {
            return;
        }

        $style_path = LVK_DIR . '/assets/css/thankyou-wa.css';

        $version = file_exists($style_path)
            ? filemtime($style_path)
            : '1.0.0';

        wp_enqueue_style(
            'lvk-thankyou-wa',
            LVK_URL . 'assets/css/thankyou-wa.css',
            [],
            $version
        );
    }

    public static function hooks() {
        if (!class_exists('WooCommerce')) return;
        add_action(
            'woocommerce_order_details_after_order_table',
            [__CLASS__, 'render_order_page'],
            20
        );

        add_action(
            'woocommerce_email_order_meta',
            [__CLASS__, 'render_email'],
            20,
            4
        );

        add_action(
            'wp_enqueue_scripts',
            [__CLASS__, 'enqueue_assets']
        );
    }
}

add_action('init', ['LVK_Thankyou_WA', 'hooks']);