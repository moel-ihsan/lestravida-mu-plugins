<?php
/**
 * MU Plugin: Lestra Vida – Custom Login CSS
 * Berlaku untuk wp-login.php DAN slug login custom (/xxx)
 */

if (!defined('ABSPATH')) exit;

add_action('login_enqueue_scripts', function () {

    wp_add_inline_style(
        'login',
        '
            #login form label:not([for=rememberme]), #login .message, #reg_passmail{
            text-align: center;
            color: #ffffff    
            }
            
        '
    );

});
