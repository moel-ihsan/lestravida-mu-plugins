<?php

if (!defined('ABSPATH')) exit;

class LVCERT_Admin_Settings {
    public static function init() {
        add_filter('woocommerce_product_data_tabs', [__CLASS__, 'add_cert_tab']);
        add_action('woocommerce_product_data_panels', [__CLASS__, 'render_cert_panel']);
        add_action('woocommerce_process_product_meta', [__CLASS__, 'save_cert_meta']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_scripts']);
    }

    public static function add_cert_tab($tabs) {
        $tabs['lestravida_cert'] = [
            'label'  => __('Sertifikat', 'lestravida'),
            'target' => 'lvk_cert_options',
            'class'  => [],
        ];
        return $tabs;
    }

    public static function render_cert_panel() {
        global $post;
        
        echo '<div id="lvk_cert_options" class="panel woocommerce_options_panel" style="display: none;">';
        echo '<div class="options_group">';

        woocommerce_wp_checkbox([
            'id'          => '_lvk_cert_enabled',
            'label'       => __('Aktifkan Sertifikat', 'lestravida'),
            'description' => __('Berikan e-Sertifikat otomatis untuk peserta yang menyelesaikan kegiatan ini.', 'lestravida'),
        ]);

        echo '<div class="options_group">';
        woocommerce_wp_text_input([
            'id'          => '_lvk_cert_template_url',
            'label'       => __('URL Template (JPG/PNG)', 'lestravida'),
            'placeholder' => 'https://...',
            'description' => __('Upload gambar di Media lalu paste URL-nya di sini.', 'lestravida'),
            'desc_tip'    => true,
        ]);

        woocommerce_wp_text_input([
            'id'          => '_lvk_cert_font_url',
            'label'       => __('URL Font Kustom (.ttf)', 'lestravida'),
            'placeholder' => 'https://...',
            'description' => __('Opsional. Biarkan kosong untuk menggunakan font bawaan (Arial/Helvetica).', 'lestravida'),
            'desc_tip'    => true,
        ]);

        woocommerce_wp_text_input([
            'id'          => '_lvk_cert_font_size',
            'label'       => __('Ukuran Teks (PT)', 'lestravida'),
            'type'        => 'number',
            'placeholder' => '42',
            'custom_attributes' => ['step' => '1', 'min' => '10'],
            'description' => __('Ukuran teks nama pada sertifikat (misal: 42).', 'lestravida'),
            'desc_tip'    => true,
        ]);

        woocommerce_wp_text_input([
            'id'          => '_lvk_cert_font_color',
            'label'       => __('Warna Teks (Hex)', 'lestravida'),
            'placeholder' => '#000000',
            'description' => __('Kode Hex warna, contoh: #000000 (Hitam), #FFFFFF (Putih).', 'lestravida'),
            'desc_tip'    => true,
        ]);

        woocommerce_wp_text_input([
            'id'          => '_lvk_cert_y_pos',
            'label'       => __('Posisi Y (Pixel)', 'lestravida'),
            'type'        => 'number',
            'placeholder' => '500',
            'custom_attributes' => ['step' => '1'],
            'description' => __('Jarak teks dari batas atas gambar. Posisi horizontal (Kiri-Kanan) akan rata tengah otomatis.', 'lestravida'),
            'desc_tip'    => true,
        ]);
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }

    public static function save_cert_meta($post_id) {
        $fields = [
            '_lvk_cert_enabled',
            '_lvk_cert_template_url',
            '_lvk_cert_font_url',
            '_lvk_cert_font_size',
            '_lvk_cert_font_color',
            '_lvk_cert_y_pos',
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                update_post_meta($post_id, $field, $value);
            } else {
                if ($field === '_lvk_cert_enabled') {
                    update_post_meta($post_id, $field, 'no');
                } else {
                    delete_post_meta($post_id, $field);
                }
            }
        }
    }

    public static function enqueue_admin_scripts() {
        global $post_type;
        if ($post_type === 'product') {
            // Optional: load wp media for better UX if needed in future
            wp_enqueue_media();
        }
    }
}

LVCERT_Admin_Settings::init();
