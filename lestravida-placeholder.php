<?php
/**
 * Plugin Name: Lestra Vida – WooCommerce Placeholder Image
 * Description: Custom default placeholder image for WooCommerce products
 */

if (!defined('ABSPATH')) exit;

add_filter(
    'woocommerce_placeholder_img_src',
    function ($src) {

        static $cached_url = null;

        if ($cached_url !== null) {
            return $cached_url ?: $src;
        }

        $upload = wp_upload_dir();

        $relative = 'lestravida/default-image-product.webp';

        $path = trailingslashit($upload['basedir']) . $relative;
        $url  = trailingslashit($upload['baseurl']) . $relative;

        if (file_exists($path)) {
            $cached_url = esc_url_raw($url);
            return $cached_url;
        }

        $cached_url = '';

        return $src;
    },
    999
);