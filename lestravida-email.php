<?php
/**
 * Plugin Name: Lestravida Email System
 * Description: Global HTML email templates & notifications (MU Plugin)
 */

if (!defined('ABSPATH')) exit;

require_once WPMU_PLUGIN_DIR . '/config.php';

$base = __DIR__ . '/lestravida-email';

if (!is_dir($base)) {
    return;
}

$files = [
    'core.php',
    'new-user.php',
    'admin-new-user.php',
];

foreach ($files as $file) {
    $path = $base . '/' . $file;

    if (file_exists($path)) {
        require_once $path;
    }
}