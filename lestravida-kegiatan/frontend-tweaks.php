<?php
/**
 * frontend-tweaks.php
 *
 * Frontend tweaks khusus produk event LVK.
 */

if (!defined('ABSPATH')) exit;

final class LVK_Frontend_Tweaks {

    public static function hide_price($price, $product) {
        return '';
    }
    
    public static function hide_availability($text, $product) {
        return '';
    }
    
    public static function force_virtual($virtual, $product) {
        return true;
    }
    
    public static function no_shipping_class($class_id, $product) {
        return 0;
    }

    public static function is_mmm_archive(): bool {

        if (!is_tax('product_cat')) {
            return false;
        }

        $obj = get_queried_object();

        return $obj
            && !is_wp_error($obj)
            && isset($obj->slug)
            && $obj->slug === LVK_Helper::EVENT_CATEGORY;
    }

    public static function hide_subcategory_count($html) {

        return self::is_mmm_archive() ? '' : $html;
    }

    public static function render_chapter_badge($category): void {

        if (!self::is_mmm_archive()) {
            return;
        }

        if (
            !$category
            || empty($category->term_id)
            || !class_exists('LVK_Rules')
        ) {
            return;
        }

        $q = new WP_Query([
            'post_type'              => 'product',
            'post_status'            => 'publish',
            'posts_per_page'         => 1,
            'fields'                 => 'ids',
            'no_found_rows'          => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,

            'tax_query' => [[
                'taxonomy'         => 'product_cat',
                'field'            => 'term_id',
                'terms'            => (int) $category->term_id,
                'include_children' => true,
            ]],

            'meta_query' => LVK_Rules::active_meta_query(),
        ]);

        $ongoing = (int) $q->found_posts;

        wp_reset_postdata();

        $term_link = get_term_link($category);

        if (is_wp_error($term_link)) {
            return;
        }

        echo '<h2 class="woocommerce-loop-category__title lv-chapter-title">';
        echo '<a class="lv-chapter-link" href="' . esc_url($term_link) . '">';
        echo '<span class="lv-chapter-text">Chapter ' . esc_html($category->name) . '</span>';

        if ($ongoing >= 1) {
            echo '<span class="lv-badge">' . esc_html($ongoing) . '</span>';
        }

        echo '</a>';
        echo '</h2>';
    }

    public static function category_sale_badge($html, $post, $product) {
    
        static $badge_cache = [];
    
        if (!$product instanceof WC_Product) {
            return '';
        }
    
        $product_id = $product->get_id();
    
        if (isset($badge_cache[$product_id])) {
            return $badge_cache[$product_id];
        }
    
        $terms = get_the_terms($product_id, 'product_cat');
    
        if (!$terms || is_wp_error($terms)) {
            $badge_cache[$product_id] = '';
            return '';
        }
    
        $term = reset($terms);
    
        if (!$term) {
            $badge_cache[$product_id] = '';
            return '';
        }
    
        $top_cat = LVK_Helper::get_top_term($term);
    
        if (!$top_cat || is_wp_error($top_cat)) {
            $badge_cache[$product_id] = '';
            return '';
        }
    
        $term_link = get_term_link($top_cat);
    
        if (is_wp_error($term_link)) {
            $badge_cache[$product_id] = '';
            return '';
        }
    
        $badge_cache[$product_id] = sprintf(
            '<span class="onsale onsale-category">
                <a href="%s" rel="tag"">%s</a>
            </span>',
            esc_url($term_link),
            esc_html(strtoupper($top_cat->name))
        );
    
        return $badge_cache[$product_id];
    }

    public static function enqueue_assets(): void {

        if (!self::is_mmm_archive()) {
            return;
        }

        $style_path = LVK_DIR . '/assets/css/frontend-tweaks.css';

        $version = file_exists($style_path)
            ? filemtime($style_path)
            : '1.0.0';

        wp_enqueue_style(
            'lvk-frontend-tweaks',
            LVK_URL . 'assets/css/frontend-tweaks.css',
            [],
            $version
        );
    }

    public static function hooks(): void {

        add_filter(
            'woocommerce_get_price_html',
            [__CLASS__, 'hide_price'],
            9999,
            2
        );

        add_filter(
            'woocommerce_get_availability_text',
            [__CLASS__, 'hide_availability'],
            9999,
            2
        );

        add_filter(
            'woocommerce_product_get_virtual',
            [__CLASS__, 'force_virtual'],
            10,
            2
        );

        add_filter(
            'woocommerce_product_get_shipping_class_id',
            [__CLASS__, 'no_shipping_class'],
            10,
            2
        );

        remove_action(
            'woocommerce_single_product_summary',
            'woocommerce_template_single_meta',
            40
        );

        remove_action(
            'woocommerce_single_product_summary',
            'woocommerce_template_single_availability',
            20
        );

        add_filter(
            'woocommerce_subcategory_count_html',
            [__CLASS__, 'hide_subcategory_count'],
            10,
            2
        );

        add_action(
            'woocommerce_after_subcategory',
            [__CLASS__, 'render_chapter_badge'],
            15
        );

        add_filter(
            'woocommerce_sale_flash',
            [__CLASS__, 'category_sale_badge'],
            999,
            3
        );

        add_action(
            'wp_enqueue_scripts',
            [__CLASS__, 'enqueue_assets']
        );
    }
}

add_action('init', ['LVK_Frontend_Tweaks', 'hooks']);