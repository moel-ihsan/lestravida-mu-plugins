<?php
/**
 * frontend-single.php
 *
 * Custom single product layout untuk LVK.
 */

if (!defined('ABSPATH')) exit;

final class LVK_Single {

    public static function format_text_block($text): string {

        $text = trim((string) $text);

        if ($text === '') {
            return '';
        }

        if (
            stripos($text, '<ul') !== false
            || stripos($text, '<ol') !== false
        ) {
            return wp_kses_post($text);
        }

        $lines = preg_split("/\r\n|\r|\n/", $text);

        $lines = array_values(
            array_filter(
                array_map('trim', $lines),
                fn($s) => $s !== ''
            )
        );

        if (count($lines) <= 1) {
            return wpautop(wp_kses_post($text));
        }

        $lis = array_map(function ($item) {
            $item = preg_replace(
                '/^(\-|\–|\—|\*|\•|\d+\.)\s*/u',
                '',
                $item
            );

            return '<li>' . wp_kses_post($item) . '</li>';
        }, $lines);

        return '<ul class="lv-bullet-list">' . implode('', $lis) . '</ul>';
    }

    private static function render_meta_item(
        string $label,
        string $content,
        string $class = ''
    ): void {

        if ($content === '') {
            return;
        }

        echo '<div class="lv-meta-item ' . esc_attr($class) . '">';
        echo '<span class="lv-meta-label">' . esc_html($label) . '</span>';
        echo '<div class="lv-meta-value">' . $content . '</div>';
        echo '</div>';
    }

    public static function render_chapter_meta(): void {

        if (!is_product()) {
            return;
        }

        global $product;

        if (!$product instanceof WC_Product) {
            return;
        }

        $product_id = $product->get_id();

        $terms = get_the_terms($product_id, 'product_cat');

        if (!$terms || is_wp_error($terms)) {
            return;
        }

        $batch = (int) get_post_meta(
            $product_id,
            LVK_Helper::META_BATCH,
            true
        );

        $display_term = null;

        foreach ($terms as $term) {

            if (
                LVK_Helper::is_mmm_top_level($term)
                && $term->slug !== LVK_Helper::EVENT_CATEGORY
            ) {
                $display_term = $term;
                break;
            }

            $top = LVK_Helper::get_top_term($term);

            if ($top && !is_wp_error($top)) {
                $display_term = $top;
            }
        }

        if (!$display_term || is_wp_error($display_term)) {
            return;
        }

        $term_link = get_term_link($display_term);

        if (is_wp_error($term_link)) {
            return;
        }

        echo '<div class="lv-single-chapter">';
        echo '<a href="' . esc_url($term_link) . '" class="lv-single-chapter-link" rel="tag">';
        echo '<span class="lv-single-chapter-name">';

        if (LVK_Helper::is_mmm_top_level($display_term)) {
            echo 'CHAPTER ' . esc_html($display_term->name);
        } else {
            echo esc_html($display_term->name);
        }

        echo '</span>';

        if ($batch > 0) {
            echo '<span class="lv-single-batch">#Batch ' . esc_html($batch) . '</span>';
        }

        echo '</a>';
        echo '</div>';
    }

    public static function render_blocks(): void {

        if (!is_product()) {
            return;
        }

        global $product;

        if (!$product instanceof WC_Product) {
            return;
        }

        $id = $product->get_id();

        /**
         * Ambil semua meta sekali saja.
         */
        $meta = get_post_meta($id);

        $get_meta = function ($key) use ($meta) {
            return isset($meta[$key][0]) ? (string) $meta[$key][0] : '';
        };

        $detail    = $get_meta(LVK_Helper::META_DETAIL);
        $fasilitas = $get_meta(LVK_Helper::META_FASILITAS);
        $kriteria  = $get_meta(LVK_Helper::META_KRITERIA);
        $tanggal   = $get_meta(LVK_Helper::META_TANGGAL);
        $jam       = $get_meta(LVK_Helper::META_JAM);
        $lokasi    = $get_meta(LVK_Helper::META_LOKASI);
        $url       = $get_meta(LVK_Helper::META_DOKUMEN_URL);
        $label     = $get_meta(LVK_Helper::META_DOKUMEN_LABEL);

        $label = $label ?: 'Lihat Dokumen';

        if ($detail || $fasilitas || $kriteria) {

            echo '<div class="lv-product-about product_meta">';

            self::render_meta_item(
                'Detail Kegiatan',
                self::format_text_block($detail),
                'lv-detail'
            );

            self::render_meta_item(
                'Fasilitas',
                self::format_text_block($fasilitas),
                'lv-fasilitas'
            );

            self::render_meta_item(
                'Kriteria',
                self::format_text_block($kriteria),
                'lv-kriteria'
            );

            echo '</div>';
        }

        echo '<div class="lv-product-schedule product_meta">';

        if ($product->managing_stock()) {

            $stock_qty = (int) $product->get_stock_quantity();

            $quota = $stock_qty > 0
                ? esc_html($stock_qty) . ' Orang'
                : '<span class="lv-outofstock">Kuota Habis</span>';

            self::render_meta_item(
                'Kuota Tersedia',
                $quota
            );
        }

        if ($tanggal) {

            $timestamp = strtotime($tanggal);

            $formatted = $timestamp
                ? date_i18n('d-m-Y', $timestamp)
                : $tanggal;

            $datetime = esc_html($formatted);

            if ($jam) {
                $datetime .= ' Pukul ' . esc_html($jam);
            }

            self::render_meta_item(
                'Waktu Kegiatan',
                $datetime
            );
            
            do_action('lvk_after_waktu_kegiatan', $product);
        }

        self::render_meta_item(
            'Lokasi',
            esc_html($lokasi)
        );

        if ($url) {

            $button =
                '<a class="lv-doc-btn" href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' .
                    esc_html($label) .
                '</a>';

            self::render_meta_item(
                'Informasi Tambahan',
                $button,
                'lv-meta-doc'
            );
        }

        echo '</div>';
    }

    public static function enqueue_assets(): void {

        if (!is_product()) {
            return;
        }

        $style_path = LVK_DIR . '/assets/css/frontend-single.css';

        $version = file_exists($style_path)
            ? filemtime($style_path)
            : '1.0.0';

        wp_enqueue_style(
            'lvk-single',
            LVK_URL . 'assets/css/frontend-single.css',
            [],
            $version
        );
    }

    public static function hooks(): void {

        add_action(
            'woocommerce_single_product_summary',
            [__CLASS__, 'render_chapter_meta'],
            6
        );

        add_action(
            'woocommerce_single_product_summary',
            [__CLASS__, 'render_blocks'],
            20
        );

        add_action(
            'wp_enqueue_scripts',
            [__CLASS__, 'enqueue_assets']
        );
    }
}

add_action('init', ['LVK_Single', 'hooks']);