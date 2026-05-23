<?php
if (!defined('ABSPATH')) exit;

/**
 * KONFIGURASI LOGO
 */
$GLOBALS['LV_FORCE_LOGO_URL'] = 'https://lestravida.com/wp-content/uploads/2025/07/logo-header.png';

function lv_use_cid() {
    return strpos(home_url('/'), 'localhost') !== false;
}

function lv_get_email_logo_url() {
    if (!empty($GLOBALS['LV_FORCE_LOGO_URL'])) {
        return esc_url_raw($GLOBALS['LV_FORCE_LOGO_URL']);
    }

    $logo_id = get_theme_mod('custom_logo');

    if ($logo_id) {
        $src = wp_get_attachment_image_src($logo_id, 'full');

        if ($src && !empty($src[0])) {
            return esc_url_raw($src[0]);
        }
    }

    return esc_url_raw(get_site_icon_url(128) ?: '');
}

function lv_logo_url_to_path($url) {
    if (!$url) {
        return '';
    }

    $uploads = wp_upload_dir();

    if (
        !empty($uploads['baseurl'])
        && !empty($uploads['basedir'])
        && strpos($url, $uploads['baseurl']) === 0
    ) {
        return wp_normalize_path(
            $uploads['basedir'] . substr($url, strlen($uploads['baseurl']))
        );
    }

    return '';
}

function lv_mail_content_type() {
    return 'text/html';
}

add_filter('wp_mail_content_type', 'lv_mail_content_type');

add_action('phpmailer_init', function ($phpmailer) {

    if (!lv_use_cid()) {
        return;
    }

    if (
        !is_object($phpmailer)
        || !method_exists($phpmailer, 'AddEmbeddedImage')
    ) {
        return;
    }

    $logo_url  = lv_get_email_logo_url();
    $file_path = lv_logo_url_to_path($logo_url);

    if (!$file_path || !file_exists($file_path)) {
        return;
    }

    static $done = false;

    if ($done) {
        return;
    }

    $phpmailer->AddEmbeddedImage(
        $file_path,
        'site_logo_cid',
        basename($file_path)
    );

    $done = true;
});