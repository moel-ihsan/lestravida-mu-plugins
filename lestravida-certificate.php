<?php
/**
 * Plugin Name: LestraVida - Certificate (MU)
 * Description: Auto-Generate Certificates for LestraVida Events.
 */

if (!defined('ABSPATH')) exit;

define('LVCERT_DIR', __DIR__ . '/lestravida-certificate');
define('LVCERT_URL', plugin_dir_url(__FILE__) . 'lestravida-certificate/');

function lvcert_require($file) {
    $path = LVCERT_DIR . '/' . ltrim($file, '/');

    if (file_exists($path)) {
        require_once $path;
    }
}

/**
 * ADMIN
 */
lvcert_require('admin-settings.php');

/**
 * GENERATOR
 */
lvcert_require('generator.php');

/**
 * FRONTEND
 */
lvcert_require('my-account-tab.php');
