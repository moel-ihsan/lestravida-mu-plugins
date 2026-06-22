<?php
/**
 * frontend-loop.php
 *
 * Mengatur tampilan loop produk frontend LVK,
 * termasuk kategori, deadline, lokasi,
 * dan visibilitas event aktif.
 */

if (!defined('ABSPATH')) exit;

final class LVK_Loop {

    private static function today(): DateTimeImmutable {

        static $today = null;

        if ($today === null) {
            $today = new DateTimeImmutable(current_time('Y-m-d'), wp_timezone());
        }

        return $today;
    }

    private static function get_product_meta(int $product_id, string $key): string {
        return (string) get_post_meta($product_id, $key, true);
    }

    public static function render_category_line(): void {

        global $product;

        if (!$product instanceof WC_Product) {
            return;
        }

        $product_id = $product->get_id();
        $terms      = get_the_terms($product_id, 'product_cat');

        if (!$terms || is_wp_error($terms)) {
            return;
        }

        $batch = (int) self::get_product_meta(
            $product_id,
            LVK_Helper::META_BATCH
        );

        $html = '';

        foreach ($terms as $term) {

            if ($term->slug === LVK_Helper::EVENT_CATEGORY) {
                continue;
            }

            $term_link = get_term_link($term);

            if (is_wp_error($term_link)) {
                continue;
            }

            if (LVK_Helper::is_mmm_top_level($term)) {

                $label = 'CHAPTER ' . esc_html($term->name);

                if ($batch > 0) {
                    $label .= ' <span class="lv-batch">#Batch ' . esc_html($batch) . '</span>';
                }

            } else {
                $label = esc_html($term->name);
            }

            $html .= '<a href="' . esc_url($term_link) . '" rel="tag" class="lv-cat-link">';
            $html .= $label;
            $html .= '</a>';
        }

        if ($html === '') {
            return;
        }

        echo '<div class="lv-product-category lv-loop-category">' . $html . '</div>';
    }

    public static function render_meta_deadline_location(): void {

        global $product;

        if (!$product instanceof WC_Product) {
            return;
        }

        $product_id = $product->get_id();

        $tanggal = self::get_product_meta(
            $product_id,
            LVK_Helper::META_TANGGAL
        );

        $lokasi = self::get_product_meta(
            $product_id,
            LVK_Helper::META_LOKASI
        );

        if ($tanggal === '' && $lokasi === '') {
            return;
        }

        echo '<div class="lv-loop-meta">';

        if ($tanggal !== '') {

            try {
                $tz       = wp_timezone();
                $today    = self::today();
                $deadline = new DateTimeImmutable($tanggal . ' 00:00:00', $tz);

                $diff    = (int) $today->diff($deadline)->days;
                $is_past = $today > $deadline;

                $closed = class_exists('LVK_Rules')
                    ? LVK_Rules::is_closed($product)
                    : false;

                if ($closed || $is_past) {
                    echo '<div class="entry-meta lv-product-deadline lv-closed">';
                    echo '<span>Pendaftaran Ditutup</span>';
                    echo '</div>';
                } else {
                    echo '<div class="entry-meta lv-product-deadline">';
                    echo '<span>Batas Waktu ' . esc_html($diff) . ' Hari lagi</span>';
                    echo '</div>';
                }

            } catch (Exception $e) {
                // Silent fail.
            }
        }

        if ($lokasi !== '') {
            echo '<div class="entry-meta lv-product-location">';
            echo '<span>' . esc_html($lokasi) . '</span>';
            echo '</div>';
        }

        echo '</div>';
    }

    public static function hooks(): void {
        if (!class_exists('WooCommerce')) return;

        add_action(
            'woocommerce_after_shop_loop_item_title',
            [__CLASS__, 'render_category_line'],
            5
        );

        add_action(
            'woocommerce_after_shop_loop_item_title',
            [__CLASS__, 'render_meta_deadline_location'],
            10
        );
    }
}

add_action('init', ['LVK_Loop', 'hooks']);