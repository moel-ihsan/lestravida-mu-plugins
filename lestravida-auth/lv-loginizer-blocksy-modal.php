<?php
/**
 * Lestra Vida – Loginizer x Blocksy Modal
 */

if (!defined('ABSPATH')) exit;

final class LV_Loginizer_Blocksy_Modal {

    const GOOGLE_LABEL_LOGIN    = 'LOGIN WITH GOOGLE';
    const GOOGLE_LABEL_REGISTER = 'SIGN UP WITH GOOGLE';

    public static function hooks() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    public static function enqueue_assets() {

        if (is_user_logged_in()) {
            return;
        }

        if (!shortcode_exists('loginizer_social')) {
            return;
        }

        $css_path = __DIR__ . '/assets/css/loginizer-modal.css';
        $js_path  = __DIR__ . '/assets/js/loginizer-modal.js';

        $css_url = plugin_dir_url(__FILE__) . 'assets/css/loginizer-modal.css';
        $js_url  = plugin_dir_url(__FILE__) . 'assets/js/loginizer-modal.js';

        wp_enqueue_style(
            'lv-loginizer-modal',
            $css_url,
            array(),
            file_exists($css_path) ? filemtime($css_path) : '1.0.0'
        );

        wp_enqueue_script(
            'lv-loginizer-modal',
            $js_url,
            array(),
            file_exists($js_path) ? filemtime($js_path) : '1.0.0',
            true
        );

        $register_sc = do_shortcode('[loginizer_social container_alignment="right" divider="none"]');

        if (!is_string($register_sc)) {
            $register_sc = '';
        }

        $register_sc = preg_replace(
            '/\bid=("|\')lz-social-login-btns\1\b/i',
            'id="lz-social-login-btns-register"',
            $register_sc,
            1
        );

        wp_localize_script(
            'lv-loginizer-modal',
            'lvLoginizerModal',
            array(
                'registerHtml'  => $register_sc,
                'labelLogin'    => self::GOOGLE_LABEL_LOGIN,
                'labelRegister' => self::GOOGLE_LABEL_REGISTER,
            )
        );
    }
}

add_action('init', array('LV_Loginizer_Blocksy_Modal', 'hooks'));