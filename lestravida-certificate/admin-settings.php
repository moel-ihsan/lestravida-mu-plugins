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
        
        // URL Template (Media Uploader + Custom Upload)
        $template_url = get_post_meta($post->ID, '_lvk_cert_template_url', true);
        echo '<p class="form-field _lvk_cert_template_url_field" style="background:#f9f9f9; padding:15px; border:1px solid #ddd; border-radius:4px;">';
        echo '<label for="_lvk_cert_template_url" style="font-weight:bold;">' . __('Template Sertifikat (JPG/PNG)', 'lestravida') . '</label>';
        
        if ($template_url) {
            echo '<img src="' . esc_url($template_url) . '" style="max-width:300px; display:block; margin:10px 0; border:1px solid #ccc;" />';
        }

        echo '<span class="description" style="display:block; margin-bottom:5px; margin-left:0;">' . __('Opsi 1: Masukkan URL Gambar / Google Drive Link (Otomatis Download)', 'lestravida') . '</span>';
        echo '<input type="text" class="short" style="width:100%; max-width:400px;" name="_lvk_cert_template_url" id="_lvk_cert_template_url" value="' . esc_attr($template_url) . '" placeholder="https://..."> ';
        echo '<a href="#" class="button lvk-upload-cert-btn" data-target="_lvk_cert_template_url" style="margin-top:5px;">Pilih dari Media Library</a>';
        
        echo '<br><br><span style="font-weight:bold; color:#d63638;">Atau (Sangat Disarankan agar 100% Tajam):</span><br>';
        echo '<span class="description" style="display:block; margin-bottom:5px; margin-left:0;">' . __('Opsi 2: Upload langsung dari komputer Anda (Bypass WP Compression)', 'lestravida') . '</span>';
        echo '<input type="file" name="lvk_custom_cert_upload" accept="image/png, image/jpeg" style="margin-top:5px; background:#fff; padding:5px; border:1px solid #ccc;">';
        echo '</p>';

        // Script untuk memastikan form bisa mengirim file (PENTING)
        echo '<script>jQuery(document).ready(function($){ $("form#post").attr("enctype", "multipart/form-data"); });</script>';

        echo '<div style="margin-bottom:15px; border-bottom:1px solid #eee;"></div>';

        woocommerce_wp_text_input([
            'id'          => '_lvk_cert_font_url',
            'label'       => __('URL Font Kustom (.ttf / .otf)', 'lestravida'),
            'placeholder' => 'https://...',
            'description' => __('Kosongkan untuk menggunakan font bawaan (Luxia-Display).', 'lestravida'),
            'desc_tip'    => true,
        ]);

        echo '<p class="form-field">';
        echo '<label></label>';
        echo '<a href="#" class="button lvk-upload-cert-btn" data-target="_lvk_cert_font_url">Upload Font</a>';
        echo '</p>';

        woocommerce_wp_text_input([
            'id'          => '_lvk_cert_font_size',
            'label'       => __('Ukuran Teks (PT)', 'lestravida'),
            'type'        => 'number',
            'placeholder' => '42',
            'custom_attributes' => ['step' => '1', 'min' => '10'],
        ]);

        $color = get_post_meta($post->ID, '_lvk_cert_font_color', true) ?: '#000000';
        echo '<p class="form-field _lvk_cert_font_color_field ">';
        echo '<label for="_lvk_cert_font_color">' . __('Warna Teks (Hex)', 'lestravida') . '</label>';
        echo '<input type="text" id="_lvk_cert_font_color" name="_lvk_cert_font_color" class="colorpick" value="' . esc_attr($color) . '" />';
        echo '<span class="description">' . __('Kode Hex warna, contoh: #000000.', 'lestravida') . '</span>';
        echo '</p>';

        woocommerce_wp_select([
            'id'          => '_lvk_cert_text_align',
            'label'       => __('Perataan Teks (Align)', 'lestravida'),
            'options'     => [
                'center' => 'Center (Rata Tengah)',
                'left'   => 'Left (Rata Kiri)',
                'right'  => 'Right (Rata Kanan)'
            ],
            'description' => __('Arah perataan teks.', 'lestravida'),
            'desc_tip'    => true,
        ]);

        woocommerce_wp_text_input([
            'id'          => '_lvk_cert_x_pos',
            'label'       => __('Posisi X (Pixel)', 'lestravida'),
            'type'        => 'number',
            'placeholder' => 'Auto Center',
            'custom_attributes' => ['step' => '1'],
            'description' => __('Jarak teks dari batas kiri. Kosongkan untuk rata tengah (Center).', 'lestravida'),
            'desc_tip'    => true,
        ]);

        woocommerce_wp_text_input([
            'id'          => '_lvk_cert_y_pos',
            'label'       => __('Posisi Y (Pixel)', 'lestravida'),
            'type'        => 'number',
            'placeholder' => 'Auto Center',
            'custom_attributes' => ['step' => '1'],
            'description' => __('Jarak teks dari batas atas. Kosongkan untuk rata tengah vertikal.', 'lestravida'),
            'desc_tip'    => true,
        ]);

        // Tombol Visual Editor
        echo '<p class="form-field">';
        echo '<label></label>';
        echo '<a href="#" class="button button-primary lvk-open-visual-editor">Buka Editor Visual</a>';
        echo '</p>';

        // Container Visual Editor (Awalnya Sembunyi)
        echo '<div id="lvk-visual-editor-container" style="display:none; margin: 15px 0; padding: 15px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">';
        echo '<h4 style="margin-top:0;">Simulasi Penempatan Teks</h4>';
        echo '<p style="margin-bottom:10px; color:#666;">Geser teks "NAMA PESERTA" ke posisi yang Anda inginkan. Koordinat X dan Y akan otomatis tersimpan.</p>';
        echo '<p style="margin-bottom:15px; color:#0073aa; font-weight:bold;" id="lvk-center-info"></p>';
        echo '<div id="lvk-visual-editor-canvas" style="position:relative; width:100%; max-width:800px; height:400px; background-color:#eee; background-size:contain; background-repeat:no-repeat; background-position:top left; border:1px dashed #999; overflow:hidden;">';
        echo '<div id="lvk-visual-editor-text" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); cursor:move; color:#000; font-size:24px; font-weight:bold; white-space:nowrap; padding:5px; border:1px solid rgba(0,0,0,0.2); background:rgba(255,255,255,0.5);">NAMA PESERTA</div>';
        echo '</div>';
        echo '</div>';

        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }

    public static function save_cert_meta($post_id) {
        $upload_dir = wp_upload_dir();
        $cert_dir = $upload_dir['basedir'] . '/lestravida-certificates';
        $cert_url = $upload_dir['baseurl'] . '/lestravida-certificates';

        // 1. Cek Auto-Download Google Drive
        if (!empty($_POST['_lvk_cert_template_url'])) {
            $template_url = $_POST['_lvk_cert_template_url'];
            if (strpos($template_url, 'drive.google.com') !== false) {
                preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $template_url, $matches);
                if (!empty($matches[1])) {
                    if (!file_exists($cert_dir)) wp_mkdir_p($cert_dir);

                    $file_id = $matches[1];
                    $direct_url = "https://drive.google.com/uc?export=download&id={$file_id}";
                    
                    $response = wp_remote_get($direct_url, ['timeout' => 30]);
                    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                        $image_data = wp_remote_retrieve_body($response);
                        $ext = (strpos(substr($image_data, 0, 10), 'PNG') !== false) ? 'png' : 'jpg';
                        $new_filename = 'gdrive_' . $post_id . '_' . time() . '.' . $ext;
                        
                        if (file_put_contents($cert_dir . '/' . $new_filename, $image_data)) {
                            $_POST['_lvk_cert_template_url'] = $cert_url . '/' . $new_filename; 
                        }
                    }
                }
            }
        }

        // 2. Cek Upload Mentahan Langsung (Bypass)
        if (!empty($_FILES['lvk_custom_cert_upload']['tmp_name'])) {
            $file = $_FILES['lvk_custom_cert_upload'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                if (!file_exists($cert_dir)) wp_mkdir_p($cert_dir);

                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                    $new_filename = 'cert_' . $post_id . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $cert_dir . '/' . $new_filename)) {
                        $_POST['_lvk_cert_template_url'] = $cert_url . '/' . $new_filename;
                    }
                }
            }
        }

        $fields = [
            '_lvk_cert_enabled',
            '_lvk_cert_template_url',
            '_lvk_cert_font_url',
            '_lvk_cert_font_size',
            '_lvk_cert_font_color',
            '_lvk_cert_text_align',
            '_lvk_cert_x_pos',
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
            wp_enqueue_script('jquery-ui-draggable');
            
            wp_add_inline_script('wp-color-picker', "
                jQuery(document).ready(function($){
                    $('.colorpick').wpColorPicker({
                        change: function(event, ui) {
                            $('#lvk-visual-editor-text').css('color', ui.color.toString());
                        }
                    });

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

                    // VISUAL EDITOR LOGIC
                    var canvas = $('#lvk-visual-editor-canvas');
                    var draggie = $('#lvk-visual-editor-text');
                    var imgNaturalWidth = 0;
                    var imgNaturalHeight = 0;

                    function updateTextTransform() {
                        var align = $('#_lvk_cert_text_align').val();
                        if (align === 'left') {
                            draggie.css('transform', 'translate(0%, -50%)');
                        } else if (align === 'right') {
                            draggie.css('transform', 'translate(-100%, -50%)');
                        } else {
                            draggie.css('transform', 'translate(-50%, -50%)');
                        }
                    }

                    $('#_lvk_cert_text_align').on('change', updateTextTransform);

                    $('.lvk-open-visual-editor').on('click', function(e){
                        e.preventDefault();
                        var imgUrl = $('#_lvk_cert_template_url').val();
                        if (!imgUrl) {
                            alert('Harap isi URL Template terlebih dahulu!');
                            return;
                        }

                        $('#lvk-visual-editor-container').slideDown();

                        // Load image to get natural dimensions
                        var img = new Image();
                        img.onload = function() {
                            imgNaturalWidth = this.width;
                            imgNaturalHeight = this.height;

                            var centerX = Math.round(imgNaturalWidth / 2);
                            var centerY = Math.round(imgNaturalHeight / 2);
                            $('#lvk-center-info').text('💡 Info Gambar: Resolusi Asli ' + imgNaturalWidth + 'x' + imgNaturalHeight + 'px. Titik Tengah di X=' + centerX + 'px, Y=' + centerY + 'px.');
                            
                            // Scale canvas to match image ratio
                            var canvasWidth = canvas.width();
                            var scaleFactor = canvasWidth / imgNaturalWidth;
                            var canvasHeight = imgNaturalHeight * scaleFactor;
                            
                            canvas.css({
                                'background-image': 'url(' + imgUrl + ')',
                                'height': canvasHeight + 'px'
                            });

                            // Apply styles to text
                            var fontSize = $('#_lvk_cert_font_size').val() || 42;
                            var fontColor = $('#_lvk_cert_font_color').val() || '#000000';
                            
                            // Visual scale approximation for font
                            var visualFontSize = fontSize * scaleFactor;
                            draggie.css({
                                'font-size': visualFontSize + 'px',
                                'color': fontColor
                            });

                            updateTextTransform();

                            // Set initial position based on inputs
                            var initX = $('#_lvk_cert_x_pos').val();
                            var initY = $('#_lvk_cert_y_pos').val();

                            if (initX && !isNaN(initX)) {
                                draggie.css({ left: (initX * scaleFactor) + 'px' });
                            } else {
                                draggie.css({ left: '50%' });
                            }

                            if (initY && !isNaN(initY)) {
                                draggie.css('top', (initY * scaleFactor) + 'px');
                            } else {
                                draggie.css('top', '50%');
                            }
                        };
                        img.src = imgUrl;
                    });

                    draggie.draggable({
                        containment: 'parent',
                        drag: function(event, ui) {
                            if (imgNaturalWidth === 0) return;

                            var canvasWidth = canvas.width();
                            var scaleFactor = imgNaturalWidth / canvasWidth;

                            // Calculate real coordinates
                            var realX = Math.round(ui.position.left * scaleFactor);
                            var realY = Math.round(ui.position.top * scaleFactor); // GD Y is baseline, but let\'s map approx top

                            $('#_lvk_cert_x_pos').val(realX);
                            $('#_lvk_cert_y_pos').val(realY);
                        }
                    });
                });
            ");
        }
    }
}

LVCERT_Admin_Settings::init();
