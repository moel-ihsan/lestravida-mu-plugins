<?php
/**
 * rename-product.php
 */

if (!defined('ABSPATH')) exit;

final class LVC_Rename_Product {

    public static function rename_labels(): void {

        global $wp_post_types;

        if (!isset($wp_post_types['product'])) {
            return;
        }

        $labels = &$wp_post_types['product']->labels;

        $labels->name                  = 'Kegiatan';
        $labels->singular_name         = 'Kegiatan';
        $labels->add_new               = 'Tambah Kegiatan';
        $labels->add_new_item          = 'Tambah Kegiatan';
        $labels->edit_item             = 'Edit Kegiatan';
        $labels->new_item              = 'Kegiatan Baru';
        $labels->view_item             = 'Lihat Kegiatan';
        $labels->search_items          = 'Cari Kegiatan';
        $labels->not_found             = 'Tidak ada kegiatan';
        $labels->not_found_in_trash    = 'Tidak ada kegiatan di trash';
        $labels->all_items             = 'Semua Kegiatan';
        $labels->menu_name             = 'Kegiatan';
        $labels->name_admin_bar        = 'Kegiatan';
        $labels->featured_image        = 'Poster Kegiatan';
        $labels->set_featured_image    = 'Set Poster Kegiatan';
        $labels->remove_featured_image = 'Hapus Poster Kegiatan';
        $labels->use_featured_image    = 'Gunakan sebagai Poster Kegiatan';
    }

    public static function hooks(): void {
        if (!class_exists('WooCommerce')) return;
        add_action('init', [__CLASS__, 'rename_labels'], 20);
    }
}

LVC_Rename_Product::hooks();