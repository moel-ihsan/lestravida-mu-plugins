<?php

if (!defined('ABSPATH')) exit;

class LVCERT_Admin_Settings {
    public static function init() {
        add_filter('woocommerce_product_data_tabs', [__CLASS__, 'add_cert_tab']);
        add_action('woocommerce_product_data_panels', [__CLASS__, 'render_cert_panel']);
        add_action('woocommerce_process_product_meta', [__CLASS__, 'save_cert_meta']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_scripts']);

        // Izinkan upload font
        add_filter('upload_mimes', [__CLASS__, 'allow_font_mimes']);
        add_filter('wp_check_filetype_and_ext', [__CLASS__, 'check_font_filetype'], 10, 4);
    }

    public static function allow_font_mimes($mimes) {
        $mimes['ttf'] = 'font/ttf';
        $mimes['otf'] = 'font/otf';
        return $mimes;
    }

    public static function check_font_filetype($data, $file, $filename, $mimes) {
        $filetype = wp_check_filetype($filename, $mimes);
        $ext = $filetype['ext'];
        if (in_array($ext, ['ttf', 'otf'])) {
            $data['ext']  = $ext;
            $data['type'] = $filetype['type'];
        }
        return $data;
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
        
        // URL Template (Media Uploader)
        $template_url = get_post_meta($post->ID, '_lvk_cert_template_url', true);
        echo '<p class="form-field _lvk_cert_template_url_field">';
        echo '<label for="_lvk_cert_template_url">' . __('URL Template (JPG/PNG)', 'lestravida') . '</label>';
        echo '<input type="text" class="short" style="" name="_lvk_cert_template_url" id="_lvk_cert_template_url" value="' . esc_attr($template_url) . '" placeholder="https://..."> ';
        echo '<a href="#" class="button lvk-upload-cert-btn" data-target="_lvk_cert_template_url">Upload Gambar</a>';
        echo '<span class="description">' . __('Upload gambar di Media lalu paste URL-nya di sini.', 'lestravida') . '</span>';
        echo '</p>';

        // URL Font (Media Uploader)
        $font_url = get_post_meta($post->ID, '_lvk_cert_font_url', true);
        echo '<p class="form-field _lvk_cert_font_url_field">';
        echo '<label for="_lvk_cert_font_url">' . __('URL Font Kustom (.ttf)', 'lestravida') . '</label>';
        echo '<input type="text" class="short" style="" name="_lvk_cert_font_url" id="_lvk_cert_font_url" value="' . esc_attr($font_url) . '" placeholder="https://..."> ';
        echo '<a href="#" class="button lvk-upload-cert-btn" data-target="_lvk_cert_font_url">Upload Font</a>';
        echo '<span class="description">' . __('Opsional. Biarkan kosong untuk menggunakan font bawaan (Arial/Helvetica).', 'lestravida') . '</span>';
        echo '</p>';

        woocommerce_wp_text_input([
            'id'          => '_lvk_cert_font_size',
            'label'       => __('Ukuran Teks (PT)', 'lestravida'),
            'type'        => 'number',
            'placeholder' => '42',
            'custom_attributes' => ['step' => '1', 'min' => '10'],
            'description' => __('Ukuran teks nama pada sertifikat (misal: 42).', 'lestravida'),
            'desc_tip'    => true,
        ]);

        // Color Picker
        $color = get_post_meta($post->ID, '_lvk_cert_font_color', true);
        if (empty($color)) $color = '#000000';
        echo '<p class="form-field _lvk_cert_font_color_field">';
        echo '<label for="_lvk_cert_font_color">' . __('Warna Teks (Hex)', 'lestravida') . '</label>';
        echo '<input type="text" class="colorpick" name="_lvk_cert_font_color" id="_lvk_cert_font_color" value="' . esc_attr($color) . '">';
        echo '<span class="description">' . __('Kode Hex warna, contoh: #000000.', 'lestravida') . '</span>';
        echo '</p>';

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

    public static function enqueue_admin_scripts($hook) {
        global $post_type;
        if ($post_type === 'product') {
            wp_enqueue_media();
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            
            // Inline script for media uploader and color picker
            wp_add_inline_script('wp-color-picker', "
                jQuery(document).ready(function($){
                    $('.colorpick').wpColorPicker();

                    $('.lvk-upload-cert-btn').on('click', function(e) {
                        e.preventDefault();
                        var button = $(this);
                        var targetId = button.data('target');
                        var targetInput = $('#' + targetId);

                        var customUploader = wp.media({
                            title: 'Pilih File',
                            button: { text: 'Gunakan File Ini' },
                            multiple: false
                        }).on('select', function() {
                            var attachment = customUploader.state().get('selection').first().toJSON();
                            targetInput.val(attachment.url);
                        }).open();
                    });
                });
            ");
        }
    }
}

LVCERT_Admin_Settings::init();
