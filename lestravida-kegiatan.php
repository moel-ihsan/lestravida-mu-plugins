<?php
/**
 * Plugin Name: LestraVida - Kegiatan (MU)
 * Description: Custom WooCommerce untuk produk "Kegiatan" LestraVida.
 */

if (!defined('ABSPATH')) exit;

define('LVK_DIR', __DIR__ . '/lestravida-kegiatan');
define('LVK_URL', plugin_dir_url(__FILE__) . 'lestravida-kegiatan/');

function lvk_require($file) {
    $path = LVK_DIR . '/' . ltrim($file, '/');

    if (file_exists($path)) {
        require_once $path;
    }
}

/**
 * CORE
 */
lvk_require('helpers.php');
lvk_require('rules-status.php');

/**
 * ADMIN
 */
lvk_require('admin-fields.php');
lvk_require('admin-cleanup.php');

/**
 * FRONTEND
 */
lvk_require('frontend-tweaks.php');
lvk_require('frontend-loop.php');
lvk_require('frontend-single.php');
lvk_require('cart-ui.php');

/**
 * FEATURES
 */
lvk_require('counter.php');
lvk_require('thankyou-wa.php');
lvk_require('checkout-fee.php');
lvk_require('tshirt-order.php');