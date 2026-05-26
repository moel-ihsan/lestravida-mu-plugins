<?php
/**
 * Plugin Name: LestraVida Checkout System
 * Description: Custom checkout fields + upload bukti ke Google Drive.
 */

if (!defined('ABSPATH')) exit;

require_once WPMU_PLUGIN_DIR . '/config.php';

define('LVC_DIR', __DIR__ . '/lestravida-checkout');
define('LVC_URL', plugin_dir_url(__FILE__) . 'lestravida-checkout/');

function lvc_require($file) {
    $path = LVC_DIR . '/' . ltrim($file, '/');

    if (file_exists($path)) {
        require_once $path;
    }
}


/**
 * CHECKOUT
 */
lvc_require('checkout-fields.php');
lvc_require('google-drive.php');
lvc_require('order-admin.php');
lvc_require('registrations-export.php');
lvc_require('rename-product.php');

add_action('admin_menu', 'lvc_register_admin_menu', 5);
add_action('admin_menu', 'lvc_remove_duplicate_admin_menu', 999);

function lvc_register_admin_menu(): void {

    add_menu_page(
        'Lestravida',
        'Lestravida',
        'manage_woocommerce',
        'lestravida',
        'lvc_admin_home_page',
        'dashicons-groups',
        56
    );
}

function lvc_remove_duplicate_admin_menu(): void {
    remove_submenu_page('lestravida', 'lestravida');
}

function lvc_admin_home_page(): void {
    wp_safe_redirect(
        admin_url('admin.php?page=lvc-registrations')
    );
    exit;
}