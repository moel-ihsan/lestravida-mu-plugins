<?php

if (!defined('ABSPATH')) exit;

class LVCERT_Generator {
    public static function init() {
        add_action('template_redirect', [__CLASS__, 'process_download_request']);
    }

    public static function process_download_request() {
        if (!isset($_GET['lvk_download_cert']) || !isset($_GET['item_id'])) {
            return;
        }

        $order_id = intval($_GET['lvk_download_cert']);
        $item_id  = intval($_GET['item_id']);

        if (!isset($_GET['_nonce']) || !wp_verify_nonce($_GET['_nonce'], 'download_cert_' . $order_id)) {
            wp_die(__('Akses ditolak atau tautan kedaluwarsa.', 'lestravida'));
        }

        $user_id = get_current_user_id();
        $order = wc_get_order($order_id);

        if (!$order) {
            wp_die(__('Pesanan tidak ditemukan.', 'lestravida'));
        }

        // Cek hak akses
        if (!current_user_can('manage_woocommerce') && $order->get_customer_id() !== $user_id) {
            wp_die(__('Anda tidak memiliki akses ke sertifikat ini.', 'lestravida'));
        }

        // Cek status order
        if ($order->get_status() !== 'completed') {
            wp_die(__('Sertifikat hanya tersedia untuk kegiatan yang telah diselesaikan.', 'lestravida'));
        }

        $item = $order->get_item($item_id);
        if (!$item) {
            wp_die(__('Item tidak ditemukan.', 'lestravida'));
        }

        $product_id = $item->get_product_id();
        $is_enabled = get_post_meta($product_id, '_lvk_cert_enabled', true);

        if ($is_enabled !== 'yes') {
            wp_die(__('Sertifikat tidak diaktifkan untuk kegiatan ini.', 'lestravida'));
        }

        // Konfigurasi
        $template_url = get_post_meta($product_id, '_lvk_cert_template_url', true);
        $font_url     = get_post_meta($product_id, '_lvk_cert_font_url', true);
        $font_size    = intval(get_post_meta($product_id, '_lvk_cert_font_size', true)) ?: 42;
        $color_hex    = get_post_meta($product_id, '_lvk_cert_font_color', true) ?: '#000000';
        $x_pos_meta   = get_post_meta($product_id, '_lvk_cert_x_pos', true);
        $y_pos_meta   = get_post_meta($product_id, '_lvk_cert_y_pos', true);

        if (empty($template_url)) {
            wp_die(__('Admin belum mengupload template sertifikat untuk kegiatan ini.', 'lestravida'));
        }

        // Nama peserta (bisa dari meta 'Nama Lengkap' atau billing name)
        $nama_lengkap = $item->get_meta('Nama Lengkap');
        
        if (empty($nama_lengkap)) {
            $nama_lengkap = $order->get_formatted_billing_full_name();
        }
        if (empty($nama_lengkap)) {
            $nama_lengkap = __('Peserta Kegiatan', 'lestravida');
        }

        // Render Gambar
        self::generate_image($nama_lengkap, $template_url, $font_url, $font_size, $color_hex, $x_pos_meta, $y_pos_meta);
        exit;
    }

    private static function hex_to_rgb($hex) {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex,0,1).substr($hex,0,1));
            $g = hexdec(substr($hex,1,1).substr($hex,1,1));
            $b = hexdec(substr($hex,2,1).substr($hex,2,1));
        } else {
            $r = hexdec(substr($hex,0,2));
            $g = hexdec(substr($hex,2,2));
            $b = hexdec(substr($hex,4,2));
        }
        return ['r' => $r, 'g' => $g, 'b' => $b];
    }

    private static function generate_image($text, $template_url, $font_url, $font_size, $color_hex, $x_pos_meta, $y_pos_meta) {
        // Ambil gambar template
        $response = wp_remote_get($template_url);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            wp_die(__('Gagal memuat template sertifikat.', 'lestravida'));
        }
        
        $image_data = wp_remote_retrieve_body($response);
        $image = @imagecreatefromstring($image_data);
        if (!$image) {
            wp_die(__('Format template sertifikat tidak valid.', 'lestravida'));
        }

        // Font file path
        $font_path = LVCERT_DIR . '/fonts/Luxia-Display.otf'; // Default Bundled Font
        $temp_font = false;

        if (!empty($font_url)) {
            // Coba ambil local path langsung jika itu adalah attachment di website yang sama
            $attachment_id = attachment_url_to_postid($font_url);
            $local_font_found = false;
            
            if ($attachment_id) {
                $local_path = get_attached_file($attachment_id);
                if ($local_path && file_exists($local_path)) {
                    $font_path = $local_path;
                    $local_font_found = true;
                }
            }

            if (!$local_font_found) {
                // Download font ke temp file jika URL dari luar
                $font_response = wp_remote_get($font_url);
                if (!is_wp_error($font_response) && wp_remote_retrieve_response_code($font_response) === 200) {
                    // Beberapa versi GD butuh ekstensi .ttf
                    $upload_dir = wp_upload_dir();
                    $temp_font = trailingslashit($upload_dir['basedir']) . 'temp_cert_font_' . time() . '_' . rand(100,999) . '.ttf';
                    file_put_contents($temp_font, wp_remote_retrieve_body($font_response));
                    if (file_exists($temp_font)) {
                        $font_path = $temp_font;
                    }
                }
            }
        }

        // Pastikan path valid untuk GD Library
        if (!file_exists($font_path)) {
            $font_path = LVCERT_DIR . '/fonts/Luxia-Display.otf'; // Fallback keras
        }

        // Resolusi absolute path (Penting untuk GD Library)
        if (file_exists($font_path)) {
            $font_path = realpath($font_path);
        } else {
            // Jika font fallback juga tidak ada di server user, hentikan proses untuk menghindari error imagettfbbox
            wp_die(__('Error kritis: File font tidak ditemukan di server. Pastikan folder fonts/ sudah terupload.', 'lestravida'));
        }

        // Warna teks
        $rgb = self::hex_to_rgb($color_hex);
        $text_color = imagecolorallocate($image, $rgb['r'], $rgb['g'], $rgb['b']);

        // Kalkulasi Dimensi Image dan Teks
        $image_width = imagesx($image);
        $image_height = imagesy($image);
        $bbox = @imagettfbbox($font_size, 0, $font_path, $text);
        
        if (!$bbox) {
            wp_die(__('Error GD Library: Gagal membaca file font meskipun file ada. Hubungi pihak hosting. Path: ' . esc_html($font_path), 'lestravida'));
        }
        
        // $bbox[2] adalah X lower right, $bbox[0] adalah X lower left
        $text_width = abs($bbox[2] - $bbox[0]);
        // $bbox[1] adalah Y lower right, $bbox[5] adalah Y upper right
        $text_height = abs($bbox[1] - $bbox[5]);

        // Kalkulasi Posisi X
        if ($x_pos_meta !== '' && is_numeric($x_pos_meta)) {
            $x_pos = intval($x_pos_meta);
        } else {
            // Auto Center X
            $x_pos = ($image_width - $text_width) / 2;
        }

        // Kalkulasi Posisi Y
        // GD Y adalah garis baseline, jadi kita adjust agar sesuai posisi atas visual
        if ($y_pos_meta !== '' && is_numeric($y_pos_meta)) {
            $y_pos = intval($y_pos_meta) + $text_height; 
        } else {
            // Auto Center Y
            $y_pos = ($image_height + $text_height) / 2;
        }

        // Cetak Teks
        imagettftext($image, $font_size, 0, (int)$x_pos, (int)$y_pos, $text_color, $font_path, $text);

        // Bersihkan temp font
        if ($temp_font && file_exists($temp_font)) {
            @unlink($temp_font);
        }

        // Output ke Browser
        header('Content-Type: image/jpeg');
        // Uncomment baris di bawah ini jika ingin langsung memicu download file:
        header('Content-Disposition: attachment; filename="Sertifikat-' . sanitize_title($text) . '.jpg"');
        
        // Output as high quality JPEG
        imagejpeg($image, null, 95);
        imagedestroy($image);
    }
}

LVCERT_Generator::init();
