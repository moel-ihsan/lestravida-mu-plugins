<?php
/**
 * Plugin Name: Lestravida Auth Tweaks
 * Description: Auth integration for Blocksy modal (MU Plugin)
 */

if (!defined('ABSPATH')) exit;

$base = __DIR__ . '/lestravida-auth';

if (!is_dir($base)) {
    return;
}

$files = [
    // 'nextend-blocksy-modal.php',
    'lv-loginizer-blocksy-modal.php',
    'force-logout-redirect.php',
];

foreach ($files as $file) {
    $path = $base . '/' . $file;

    if (file_exists($path)) {
        require_once $path;
    }
}