<?php
/**
 * helpers.php
 *
 * Shared helper utilities untuk LVK.
 */

if (!defined('ABSPATH')) exit;

final class LVK_Helper {

    const EVENT_CATEGORY = 'muda-mudi-mengabdi';

    const META_DETAIL         = '_lv_detail';
    const META_FASILITAS      = '_lv_fasilitas';
    const META_KRITERIA       = '_lv_kriteria';
    const META_LOKASI         = '_lokasi_kegiatan';
    const META_BATCH          = '_lv_batch';
    const META_TANGGAL        = '_tanggal_tutup';
    const META_JAM            = '_jam_kegiatan';
    const META_DOKUMEN_URL    = '_dokumen_url';
    const META_DOKUMEN_LABEL  = '_dokumen_label';
    const META_WA_GROUP       = '_lv_wa_group';
    const META_IG_DAERAH       = '_lv_ig_daerah';

    private static array $top_term_cache = [];

    public static function is_event_product($product): bool {

        if (!$product instanceof WC_Product) {
            return false;
        }

        $terms = get_the_terms($product->get_id(), 'product_cat');

        if (!$terms || is_wp_error($terms)) {
            return false;
        }

        foreach ($terms as $term) {

            if (!empty($term->parent)) {
                return true;
            }

            if (
                isset($term->slug)
                && $term->slug === self::EVENT_CATEGORY
            ) {
                return true;
            }
        }

        return false;
    }

    public static function get_top_term($term) {

        if (is_numeric($term)) {
            $term_id = (int) $term;
            $term = get_term($term_id, 'product_cat');
        }

        if (!$term || is_wp_error($term) || empty($term->term_id)) {
            return null;
        }

        $term_id = (int) $term->term_id;

        if (isset(self::$top_term_cache[$term_id])) {
            return self::$top_term_cache[$term_id];
        }

        if (!empty($term->parent)) {

            $ancestors = get_ancestors($term_id, 'product_cat');

            if (!empty($ancestors)) {

                $top_id = (int) end($ancestors);
                $top    = get_term($top_id, 'product_cat');

                if ($top && !is_wp_error($top)) {
                    self::$top_term_cache[$term_id] = $top;
                    return $top;
                }
            }
        }

        self::$top_term_cache[$term_id] = $term;

        return $term;
    }

    public static function is_mmm_top_level($term): bool {

        $top = self::get_top_term($term);

        return $top
            && !is_wp_error($top)
            && isset($top->slug)
            && $top->slug === self::EVENT_CATEGORY;
    }
}