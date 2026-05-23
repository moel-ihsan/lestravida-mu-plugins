<?php
/**
 * rules-status.php
 *
 * Rules status aktif/tutup produk kegiatan LVK.
 */

if (!defined('ABSPATH')) exit;

final class LVK_Rules {

    private static $closed_cache = [];
    private static $buffering_closed_card = false;

    public static function is_closed($product): bool {

        $product = is_numeric($product)
            ? wc_get_product((int) $product)
            : $product;

        if (!$product instanceof WC_Product) {
            return true;
        }

        $product_id = $product->get_id();

        if (isset(self::$closed_cache[$product_id])) {
            return self::$closed_cache[$product_id];
        }

        if (
            $product->managing_stock()
            && (int) $product->get_stock_quantity() <= 0
        ) {
            self::$closed_cache[$product_id] = true;
            return true;
        }

        $tanggal = (string) get_post_meta(
            $product_id,
            LVK_Helper::META_TANGGAL,
            true
        );

        if ($tanggal === '') {
            self::$closed_cache[$product_id] = false;
            return false;
        }

        try {
            $tz    = wp_timezone();
            $now   = new DateTimeImmutable('now', $tz);
            $event = new DateTimeImmutable($tanggal . ' 00:00:00', $tz);
            $close = $event->modify('-1 day');

            self::$closed_cache[$product_id] = $now >= $close;

            return self::$closed_cache[$product_id];

        } catch (Exception $e) {
            self::$closed_cache[$product_id] = false;
            return false;
        }
    }

    public static function button_label($product): string {

        return self::is_closed($product)
            ? __('Pendaftaran Ditutup', 'lestravida')
            : __('Gabung Sekarang', 'lestravida');
    }

    public static function active_meta_query(): array {

        $tz = wp_timezone();

        $threshold_ts = (int) current_time('timestamp') + DAY_IN_SECONDS;

        $threshold_date = wp_date(
            'Y-m-d',
            $threshold_ts,
            $tz
        );

        return [
            [
                'key'     => '_stock_status',
                'value'   => 'instock',
                'compare' => '=',
            ],
            [
                'key'     => LVK_Helper::META_TANGGAL,
                'value'   => $threshold_date,
                'compare' => '>=',
                'type'    => 'DATE',
            ],
        ];
    }

    public static function filter_purchasable($purchasable, $product) {
        return self::is_closed($product)
            ? false
            : $purchasable;
    }

    public static function filter_loop_button_text($text, $product) {
        return self::button_label($product);
    }

    public static function filter_single_button_text($text) {
        global $product;

        return self::button_label($product);
    }

    public static function filter_on_sale($on_sale, $product) {
        return self::is_closed($product)
            ? false
            : $on_sale;
    }

    public static function add_closed_card_class($classes, $product) {

        if (
            $product instanceof WC_Product
            && self::is_closed($product)
        ) {
            $classes[] = 'lv-product-closed';
        }

        return $classes;
    }

    public static function render_closed_badge(): void {

        global $product;

        if (
            !$product instanceof WC_Product
            || !self::is_closed($product)
        ) {
            return;
        }
        
    }

    public static function replace_closed_loop_button($html, $product) {

        if (
            !$product instanceof WC_Product
            || !self::is_closed($product)
        ) {
            return $html;
        }

        return '<span class="button lv-disabled-button">' .
            esc_html__('Pendaftaran Ditutup', 'lestravida') .
            '</span>';
    }

    public static function start_closed_card_buffer(): void {

        global $product;

        if (
            !$product instanceof WC_Product
            || !self::is_closed($product)
        ) {
            return;
        }

        self::$buffering_closed_card = true;
        ob_start();
    }

    public static function end_closed_card_buffer(): void {

        if (!self::$buffering_closed_card) {
            return;
        }

        self::$buffering_closed_card = false;

        $html = ob_get_clean();

        if ($html === false || $html === '') {
            return;
        }

        /**
         * Remove image link.
         */
        $html = preg_replace(
            '#<a([^>]*class="[^"]*ct-media-container[^"]*"[^>]*)>(.*?)</a>#is',
            '<span class="ct-media-container lv-disabled-media">$2</span>',
            $html
        );

        /**
         * Remove product title link.
         */
        $html = preg_replace(
            '#<h2 class="woocommerce-loop-product__title">\s*<a[^>]*>(.*?)</a>\s*</h2>#is',
            '<h2 class="woocommerce-loop-product__title"><span class="woocommerce-loop-product__title-text">$1</span></h2>',
            $html
        );

        /**
         * Remove fallback add-to-cart/read-more link if theme still prints it.
         */
        $html = preg_replace(
            '#<a([^>]*class="[^"]*(button|add_to_cart_button|product_type_simple)[^"]*"[^>]*)>.*?</a>#is',
            '<span class="button lv-disabled-button">' .
                esc_html__('Pendaftaran Ditutup', 'lestravida') .
            '</span>',
            $html
        );

        echo $html;
    }

    public static function enqueue_assets(): void {

        $style_path = LVK_DIR . '/assets/css/rules-status.css';

        $version = file_exists($style_path)
            ? filemtime($style_path)
            : '1.0.0';

        wp_enqueue_style(
            'lvk-rules-status',
            LVK_URL . 'assets/css/rules-status.css',
            [],
            $version
        );
    }

    public static function hooks(): void {

        add_filter(
            'woocommerce_is_purchasable',
            [__CLASS__, 'filter_purchasable'],
            10,
            2
        );

        add_filter(
            'woocommerce_product_add_to_cart_text',
            [__CLASS__, 'filter_loop_button_text'],
            10,
            2
        );

        add_filter(
            'woocommerce_product_single_add_to_cart_text',
            [__CLASS__, 'filter_single_button_text'],
            10
        );

        add_filter(
            'woocommerce_product_is_on_sale',
            [__CLASS__, 'filter_on_sale'],
            999,
            2
        );

        add_filter(
            'woocommerce_post_class',
            [__CLASS__, 'add_closed_card_class'],
            10,
            2
        );

        add_action(
            'woocommerce_before_shop_loop_item',
            [__CLASS__, 'start_closed_card_buffer'],
            0
        );

        add_action(
            'woocommerce_after_shop_loop_item',
            [__CLASS__, 'end_closed_card_buffer'],
            999
        );

        add_action(
            'woocommerce_before_shop_loop_item_title',
            [__CLASS__, 'render_closed_badge'],
            5
        );

        add_filter(
            'woocommerce_loop_add_to_cart_link',
            [__CLASS__, 'replace_closed_loop_button'],
            10,
            2
        );

        add_action(
            'wp_enqueue_scripts',
            [__CLASS__, 'enqueue_assets']
        );
    }
}

add_action('init', ['LVK_Rules', 'hooks']);