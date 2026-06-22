<?php
/**
 * cart-ui.php
 *
 * Custom frontend UI untuk produk kegiatan LVK.
 */

if (!defined('ABSPATH')) exit;

final class LVK_Cart_UI {

    public static function render_share_button(): void {

        if (!is_product()) {
            return;
        }

        echo '
        <a href="#"
           class="lv-share-btn"
           data-lv-share>
            <span>Bagikan</span>
        </a>';
    }

    public static function translate_strings(
        $translated,
        $text,
        $domain
    ) {

        if (is_admin()) {
            return $translated;
        }

        if (
            $domain === 'woocommerce'
            && $text === 'View cart'
        ) {
            return 'Cek Keranjang';
        }

        return $translated;
    }

    public static function category_badge(
        $html,
        $post,
        $product
    ) {

        if (
            !$product instanceof WC_Product
            || is_wp_error($product)
        ) {
            return $html;
        }

        if (
            !is_product()
            && !is_shop()
            && !is_product_taxonomy()
            && !is_product_category()
            && !is_product_tag()
        ) {
            return $html;
        }

        $terms = get_the_terms(
            $product->get_id(),
            'product_cat'
        );

        if (!$terms || is_wp_error($terms)) {
            return $html;
        }

        $term = reset($terms);

        if (!$term || !isset($term->term_id)) {
            return $html;
        }

        $top_cat = LVK_Helper::get_top_term($term);

        if (!$top_cat || is_wp_error($top_cat)) {
            return $html;
        }

        $cat_link = get_term_link($top_cat);

        if (is_wp_error($cat_link)) {
            return $html;
        }

        return sprintf(
            '<span class="onsale onsale-category">
                <a
                    style="color:#ffffff!important"
                    href="%s"
                    rel="tag"
                >
                    %s
                </a>
            </span>',
            esc_url($cat_link),
            esc_html($top_cat->name)
        );
    }

    public static function enqueue_assets(): void {

        if (!is_product()) {
            return;
        }

        $script_path = LVK_DIR . '/assets/js/cart-share.js';

        $version = file_exists($script_path)
            ? filemtime($script_path)
            : '1.0.0';

        wp_enqueue_script(
            'lvk-cart-share',
            LVK_URL . 'assets/js/cart-share.js',
            [],
            $version,
            true
        );
    }

    public static function hooks(): void {
        if (!class_exists('WooCommerce')) return;

        add_action(
            'woocommerce_before_add_to_cart_button',
            [__CLASS__, 'render_share_button'],
            9
        );

        add_action(
            'wp_enqueue_scripts',
            [__CLASS__, 'enqueue_assets']
        );

        add_filter(
            'gettext',
            [__CLASS__, 'translate_strings'],
            20,
            3
        );

        add_filter(
            'woocommerce_sale_flash',
            [__CLASS__, 'category_badge'],
            10,
            3
        );
    }
}

add_action('init', ['LVK_Cart_UI', 'hooks']);