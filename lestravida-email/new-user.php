<?php
if (!defined('ABSPATH')) exit;

add_filter('wp_new_user_notification_email', function ($orig, $user, $blogname) {

    $reset_key = get_password_reset_key($user);

    if (is_wp_error($reset_key)) {
        return $orig;
    }

    $reset_url = network_site_url(
        "wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login),
        'login'
    );

    $logo_url = lv_get_email_logo_url();
    $cid_path = lv_logo_url_to_path($logo_url);
    $use_cid  = lv_use_cid() && $cid_path && file_exists($cid_path);

    ob_start(); ?>
    <div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:auto;padding:24px;border:1px solid #eee;border-radius:8px;">
        <div style="text-align:center;margin-bottom:16px;">
            <?php if ($use_cid): ?>
                <img src="cid:site_logo_cid" style="height:48px;margin-bottom:10px;">
            <?php elseif ($logo_url): ?>
                <img src="<?php echo esc_url($logo_url); ?>" style="height:48px;margin-bottom:10px;">
            <?php endif; ?>
            <p style="color:#666;margin:0;">Akun Anda berhasil dibuat</p>
        </div>

        <p><strong>Username:</strong> <?php echo esc_html($user->user_login); ?></p>
        <p><strong>Email:</strong> <?php echo esc_html($user->user_email); ?></p>

        <p style="margin-top:20px;text-align:center;">
            <a href="<?php echo esc_url($reset_url); ?>"
               style="display:inline-block;padding:12px 20px;background:#1a7f64;color:#fff;text-decoration:none;border-radius:6px;font-weight:bold;">
                Set Password
            </a>
        </p>

        <p style="font-size:13px;color:#777;">
            Jika tombol tidak berfungsi, buka link ini:<br>
            <?php echo esc_html($reset_url); ?>
        </p>

        <hr style="border:none;border-top:1px solid #eee;margin:20px 0;">
        <p style="font-size:12px;color:#999;text-align:center;">
            © <?php echo date('Y'); ?> <?php echo esc_html($blogname); ?>
        </p>
    </div>
    <?php

    return [
        'to'      => $orig['to'],
        'subject' => sprintf('[%s] Akun Baru Anda', wp_specialchars_decode($blogname, ENT_QUOTES)),
        'headers' => ['Content-Type: text/html; charset=UTF-8'],
        'message' => ob_get_clean(),
    ];
}, 10, 3);
