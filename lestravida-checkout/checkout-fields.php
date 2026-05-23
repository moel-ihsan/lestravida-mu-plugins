<?php
/**
 * checkout-fields.php
 */

if (!defined('ABSPATH')) exit;

final class LVC_Checkout_Fields {

    private const MIN_EVENT_GAP_DAYS = 7;

    public static function customize_fields($fields) {
        unset($fields['billing']['billing_company']);
        unset($fields['billing']['billing_address_1']);
        unset($fields['billing']['billing_address_2']);
        unset($fields['billing']['billing_city']);
        unset($fields['billing']['billing_state']);
        unset($fields['billing']['billing_postcode']);
        unset($fields['billing']['billing_country']);
        unset($fields['order']['order_comments']);

        $fields['billing']['billing_first_name']['label'] = 'Nama Depan';
        $fields['billing']['billing_last_name']['label']  = 'Nama Belakang';
        $fields['billing']['billing_email']['label']      = 'Email';
        $fields['billing']['billing_phone']['label']      = 'WhatsApp';

        $fields['billing']['billing_school'] = [
            'type' => 'text',
            'label' => 'Sekolah/ Universitas/ Tempat Kerja (Jika sedang gap year/baru lulus, silahkan tulis pendidikan terakhir)',
            'required' => true,
            'priority' => 45,
            'class' => ['form-row-wide'],
        ];

        $fields['billing']['billing_age'] = [
            'type' => 'number',
            'label' => 'Umur',
            'required' => true,
            'priority' => 46,
            'class' => ['form-row-wide'],
        ];

        $fields['billing']['billing_instagram'] = [
            'type' => 'text',
            'label' => 'Instagram',
            'required' => true,
            'priority' => 47,
            'class' => ['form-row-wide'],
        ];

        $fields['billing']['billing_source_info'] = [
            'type' => 'text',
            'label' => 'Tau informasi kegiatan ini dari mana?',
            'required' => true,
            'priority' => 48,
            'class' => ['form-row-wide'],
        ];

        return $fields;
    }

    public static function render_upload_fields($checkout): void {
        if (!WC()->cart) return;

        echo '<div class="lvc-upload-fields">';
        echo '<h3>Bukti Persyaratan</h3>';

        foreach (WC()->cart->get_cart() as $cart_item) {
            $product_id = (int) ($cart_item['product_id'] ?? 0);
            $product = wc_get_product($product_id);

            if (!$product_id || !$product) continue;

            $ig_daerah = self::get_ig_daerah_label($product_id);

            echo '<div class="lvc-upload-event">';
            echo '<h4>' . esc_html($product->get_name()) . '</h4>';

            self::print_upload_field($product_id, 'bukti_lestravida_token', 'Bukti follow Instagram/TikTok @lestravida');
            self::print_upload_field($product_id, 'bukti_lvdaerah_token', 'Bukti follow Instagram ' . $ig_daerah);
            self::print_upload_field($product_id, 'bukti_share_token', 'Bukti share poster/repost TikTok');

            echo '</div>';
        }

        echo '</div>';
    }

    private static function print_upload_field(int $product_id, string $key, string $label): void {
        $target_id = 'lvc_upload_' . $product_id . '_' . $key;
        ?>
        <p class="form-row form-row-wide">
            <label>
                <?php echo esc_html($label); ?>
                <abbr class="required">*</abbr>
            </label>

            <input
                type="file"
                class="lvc-upload-input"
                data-target="<?php echo esc_attr($target_id); ?>"
                accept="image/jpeg,image/png"
                required
            >

            <span class="lvc-upload-status"></span>

            <input
                type="hidden"
                name="lvc_uploads[<?php echo esc_attr($product_id); ?>][<?php echo esc_attr($key); ?>]"
                id="<?php echo esc_attr($target_id); ?>"
            >
        </p>
        <?php
    }

    private static function optimize_uploaded_image(array $uploaded): array {
        if (empty($uploaded['file']) || !file_exists($uploaded['file'])) return $uploaded;

        $mime = wp_check_filetype($uploaded['file'])['type'] ?? '';

        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) return $uploaded;

        require_once ABSPATH . 'wp-admin/includes/image.php';

        $editor = wp_get_image_editor($uploaded['file']);

        if (is_wp_error($editor)) return $uploaded;

        $size = $editor->get_size();

        if (empty($size['width']) || empty($size['height'])) return $uploaded;

        if ($size['width'] > 1280 || $size['height'] > 1280) {
            $editor->resize(1280, 1280, false);
        }

        if (method_exists($editor, 'set_quality')) {
            $editor->set_quality(75);
        }

        $saved = $editor->save($uploaded['file']);

        if (!is_wp_error($saved) && !empty($saved['path'])) {
            $uploaded['file'] = $saved['path'];
            $uploaded['size'] = filesize($saved['path']);
            $uploaded['url']  = str_replace(wp_get_upload_dir()['basedir'], wp_get_upload_dir()['baseurl'], $saved['path']);
        }

        return $uploaded;
    }

    public static function handle_temp_upload(): void {
        if (
            empty($_FILES['file'])
            || empty($_POST['nonce'])
            || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'lvc_upload_nonce')
        ) {
            wp_send_json_error(['message' => 'Upload tidak valid.']);
        }
    
        require_once ABSPATH . 'wp-admin/includes/file.php';
        
        $allowed_mimes = [
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png'          => 'image/png',
        ];
        
        $max_size = 5 * 1024 * 1024;
        
        if ($_FILES['file']['size'] > $max_size) {
            wp_send_json_error([
                'message' => 'Ukuran file maksimal 5MB.'
            ]);
        }
        
        $filetype = wp_check_filetype_and_ext(
            $_FILES['file']['tmp_name'],
            $_FILES['file']['name'],
            $allowed_mimes
        );
        
        if (
            empty($filetype['ext'])
            || empty($filetype['type'])
        ) {
            wp_send_json_error([
                'message' => 'Format file hanya JPG dan PNG.'
            ]);
        }
        
        $uploaded = wp_handle_upload(
            $_FILES['file'],
            [
                'test_form' => false,
                'mimes'     => $allowed_mimes,
            ]
        );
    
        if (empty($uploaded['file']) || empty($uploaded['url'])) {
            wp_send_json_error(['message' => 'Upload gagal.']);
        }
    
        $uploaded = self::optimize_uploaded_image($uploaded);
        $token = wp_generate_uuid4();
    
        set_transient('lvc_upload_' . $token, $uploaded, HOUR_IN_SECONDS);
    
        if (WC()->session) {
            WC()->session->set('lvc_upload_' . $token, $uploaded);
        }
    
        wp_send_json_success([
            'token' => $token,
            'url'   => $uploaded['url'],
        ]);
    }

    public static function validate_uploads($data, $errors): void {
        self::validate_event_gap($errors);

        $uploads = isset($_POST['lvc_uploads']) && is_array($_POST['lvc_uploads'])
            ? wp_unslash($_POST['lvc_uploads'])
            : [];

        if (!WC()->cart) return;

        foreach (WC()->cart->get_cart() as $cart_item) {
            $product_id = (int) ($cart_item['product_id'] ?? 0);
            $product = wc_get_product($product_id);
            $event_name = $product ? $product->get_name() : 'Event';

            foreach (['bukti_lestravida_token', 'bukti_lvdaerah_token', 'bukti_share_token'] as $key) {
                if (empty($uploads[$product_id][$key])) {
                    $errors->add(
                        'lvc_upload_' . $product_id . '_' . $key,
                        sprintf('Bukti persyaratan untuk %s belum lengkap.', $event_name)
                    );
                }
            }
        }
    }

    private static function validate_event_gap($errors): void {
        $events = self::cart_events_with_dates();

        for ($i = 0; $i < count($events); $i++) {
            for ($j = $i + 1; $j < count($events); $j++) {
                $diff = abs($events[$i]['timestamp'] - $events[$j]['timestamp']) / DAY_IN_SECONDS;

                if ($diff < self::MIN_EVENT_GAP_DAYS) {
                    $errors->add(
                        'lvc_event_gap',
                        'Kamu tidak bisa membeli dua event yang jadwalnya terlalu dekat. Minimal jarak antar kegiatan adalah 7 hari.'
                    );
                    return;
                }
            }
        }
    }

    private static function cart_events_with_dates(): array {
        $events = [];

        if (!WC()->cart || !class_exists('LVK_Helper')) return $events;

        foreach (WC()->cart->get_cart() as $cart_item) {
            $product_id = (int) ($cart_item['product_id'] ?? 0);
            $tanggal = get_post_meta($product_id, LVK_Helper::META_TANGGAL, true);

            if (!$product_id || !$tanggal) continue;

            try {
                $dt = new DateTimeImmutable($tanggal, wp_timezone());
                $events[] = [
                    'product_id' => $product_id,
                    'timestamp'  => $dt->getTimestamp(),
                ];
            } catch (Exception $e) {}
        }

        return $events;
    }

    public static function save_fields($order, $data): void {
        foreach ([
            'billing_school',
            'billing_age',
            'billing_instagram',
            'billing_source_info',
        ] as $key) {
            if (!empty($_POST[$key])) {
                $order->update_meta_data(
                    '_' . $key,
                    sanitize_text_field(wp_unslash($_POST[$key]))
                );
            }
        }

        $uploads = isset($_POST['lvc_uploads']) && is_array($_POST['lvc_uploads'])
            ? wp_unslash($_POST['lvc_uploads'])
            : [];

        $clean = [];

        foreach ($uploads as $product_id => $tokens) {
            $product_id = absint($product_id);

            if (!$product_id || !is_array($tokens)) continue;

            $clean[$product_id] = [
                'bukti_lestravida_token' => sanitize_text_field($tokens['bukti_lestravida_token'] ?? ''),
                'bukti_lvdaerah_token'   => sanitize_text_field($tokens['bukti_lvdaerah_token'] ?? ''),
                'bukti_share_token'      => sanitize_text_field($tokens['bukti_share_token'] ?? ''),
            ];
        }

        if ($clean) {
            $order->update_meta_data('_lvc_event_upload_tokens', $clean);
        }
    }

    public static function save_upload_file_meta($order_id): void {
        $order = wc_get_order($order_id);

        if (!$order instanceof WC_Order) return;

        $tokens = $order->get_meta('_lvc_event_upload_tokens');

        if (!is_array($tokens)) return;

        $files = [];

        foreach ($tokens as $product_id => $set) {
            $product_id = absint($product_id);

            foreach ($set as $key => $token) {
                if (!$token) continue;

                $upload = get_transient('lvc_upload_' . $token);

                if (!$upload && WC()->session) {
                    $upload = WC()->session->get('lvc_upload_' . $token);
                }

                if (!$upload) continue;

                $files[$product_id][$key] = [
                    'token' => $token,
                    'file'  => $upload['file'] ?? '',
                    'url'   => $upload['url'] ?? '',
                ];
            }
        }

        if ($files) {
            $order->update_meta_data('_lvc_event_upload_files', $files);
            $order->save();
        }
    }

    private static function get_ig_daerah_label(int $product_id): string {
        $default = '@estravidaaceh';

        if (!class_exists('LVK_Helper') || !defined('LVK_Helper::META_IG_DAERAH')) {
            return $default;
        }

        $url = trim((string) get_post_meta($product_id, LVK_Helper::META_IG_DAERAH, true));

        if ($url === '') return $default;

        $path = trim((string) wp_parse_url($url, PHP_URL_PATH), '/');

        if ($path === '') return $default;

        return '@' . sanitize_text_field(explode('/', $path)[0]);
    }

    public static function enqueue_checkout_assets(): void {
        if (!is_checkout()) return;
    
        /**
         * CSS
         */
        $css_path = LVC_DIR . '/assets/css/checkout.css';
    
        $css_version = file_exists($css_path)
            ? filemtime($css_path)
            : null;
    
        wp_enqueue_style(
            'lvc-checkout',
            LVC_URL . 'assets/css/checkout.css',
            [],
            $css_version
        );
    
        /**
         * JS
         */
        $js_path = LVC_DIR . '/assets/js/checkout-upload.js';
    
        $js_version = file_exists($js_path)
            ? filemtime($js_path)
            : null;
    
        wp_enqueue_script(
            'lvc-checkout-upload',
            LVC_URL . 'assets/js/checkout-upload.js',
            ['jquery'],
            $js_version,
            true
        );
    
        wp_localize_script(
            'lvc-checkout-upload',
            'lvcUpload',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('lvc_upload_nonce'),
            ]
        );
    }

    public static function hooks(): void {
        add_filter('woocommerce_checkout_fields', [__CLASS__, 'customize_fields']);
        add_action('woocommerce_after_order_notes', [__CLASS__, 'render_upload_fields']);
        add_action('woocommerce_after_checkout_validation', [__CLASS__, 'validate_uploads'], 10, 2);
        add_action('woocommerce_checkout_create_order', [__CLASS__, 'save_fields'], 20, 2);
        add_action('woocommerce_checkout_order_processed', [__CLASS__, 'save_upload_file_meta'], 5);
        add_action('wp_ajax_lvc_temp_upload', [__CLASS__, 'handle_temp_upload']);
        add_action('wp_ajax_nopriv_lvc_temp_upload', [__CLASS__, 'handle_temp_upload']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_checkout_assets']);
    }
}

add_action('init', ['LVC_Checkout_Fields', 'hooks']);