<?php
/**
 * Lestra Vida – Force Logout Redirect
 */

if (!defined('ABSPATH')) exit;

function lv_force_logout_redirect_url() {
    return home_url('/');
}

add_action('wp_logout', function () {
    wp_safe_redirect(lv_force_logout_redirect_url());
    exit;
});

add_filter('logout_redirect', function ($redirect_to, $requested_redirect_to, $user) {
    return lv_force_logout_redirect_url();
}, 10, 3);