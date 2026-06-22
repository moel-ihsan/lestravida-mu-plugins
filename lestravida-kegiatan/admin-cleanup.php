<?php
/**
 * admin-cleanup.php
 *
 * Membersihkan UI product admin WooCommerce LVK.
 */

if (!defined('ABSPATH')) exit;

final class LVK_Admin_Cleanup {

    /**
     * =========================================================
     * REMOVE PRODUCT SUPPORTS
     * =========================================================
     */
    public static function remove_supports(): void {

        remove_post_type_support('product', 'editor');
        remove_post_type_support('product', 'comments');
        remove_post_type_support('product', 'trackbacks');
    }

    /**
     * =========================================================
     * REMOVE META BOXES
     * =========================================================
     */
    public static function remove_metaboxes(): void {

        remove_meta_box(
            'postexcerpt',
            'product',
            'normal'
        );

        remove_meta_box(
            'tagsdiv-product_tag',
            'product',
            'side'
        );

        /**
         * Brand plugins
         */
        remove_meta_box(
            'product_branddiv',
            'product',
            'side'
        );

        remove_meta_box(
            'yith_wcbr_product_brand',
            'product',
            'side'
        );

        /**
         * Reviews/comments
         */
        remove_meta_box(
            'commentsdiv',
            'product',
            'normal'
        );

        remove_meta_box(
            'commentstatusdiv',
            'product',
            'normal'
        );
    }

    /**
     * =========================================================
     * HOOKS
     * =========================================================
     */
    public static function hooks(): void {
        if (!class_exists('WooCommerce')) return;

        add_action(
            'init',
            [__CLASS__, 'remove_supports']
        );

        add_action(
            'add_meta_boxes',
            [__CLASS__, 'remove_metaboxes'],
            99
        );
    }
}

add_action(
    'init',
    ['LVK_Admin_Cleanup', 'hooks']
);