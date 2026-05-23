<?php
if (!defined('ABSPATH')) exit;

add_filter('wp_new_user_notification_email_admin', function ($email, $user, $blogname) {

    if (!$user instanceof WP_User) {
        return $email;
    }

    $datetime = date_i18n('Y-m-d H:i:s', current_time('timestamp'));

    $roles = implode(
        ', ',
        array_map('sanitize_text_field', (array) $user->roles)
    );

    ob_start();
    ?>
    <div style="font-family:Arial,Helvetica,sans-serif;max-width:640px;margin:auto;padding:24px;border:1px solid #eee;border-radius:8px;">
        <h2 style="margin-top:0;">User Baru Terdaftar</h2>

        <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;background:#f9f9f9;">
            <tr><td><strong>Tanggal</strong></td><td><?php echo esc_html($datetime); ?></td></tr>
            <tr><td><strong>Username</strong></td><td><?php echo esc_html($user->user_login); ?></td></tr>
            <tr><td><strong>Email</strong></td><td><?php echo esc_html($user->user_email); ?></td></tr>
            <tr><td><strong>Role</strong></td><td><?php echo esc_html($roles); ?></td></tr>
        </table>

        <p style="margin-top:16px;">
            <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . absint($user->ID))); ?>"
               style="display:inline-block;padding:10px 14px;background:#1a7f64;color:#fff;text-decoration:none;border-radius:6px;">
               Lihat User
            </a>
        </p>

        <hr style="border:none;border-top:1px solid #eee;margin:20px 0;">

        <p style="font-size:12px;color:#999;text-align:center;">
            © <?php echo esc_html(date_i18n('Y')); ?> <?php echo esc_html($blogname); ?>
        </p>
    </div>
    <?php

    $email['headers'] = ['Content-Type: text/html; charset=UTF-8'];
    $email['subject'] = sprintf(
        '[%s] User Baru: %s',
        wp_specialchars_decode($blogname, ENT_QUOTES),
        $user->user_login
    );
    $email['message'] = ob_get_clean();

    return $email;

}, 10, 3);