<?php
/**
 * google-drive.php
 */

if (!defined('ABSPATH')) exit;

final class LVC_Google_Drive {

    const CREDENTIAL_FILE = 'google-oauth.json';

    public static function log_error($message): void {
        error_log('LestraVida Google Drive Error: ' . $message);
        
        $email = get_option('lvc_google_drive_alert_email', '');
        if (is_email($email)) {
            wp_mail($email, 'LestraVida Google Drive Alert', $message);
        }
    }

    private static function root_folder_id(): string {
        return function_exists('lvc_config')
            ? (string) lvc_config('google_drive.root_folder_id', '')
            : '';
    }

    private static $last_error = '';
    private static $token = null;
    private static $folder_cache = [];

    private static function credential_path(): string {
        return dirname(ABSPATH) . '/private/' . self::CREDENTIAL_FILE;
    }
    
    private static function oauth_config(): array {
        if (!file_exists(self::credential_path())) {
            return [];
        }

        $json = json_decode(file_get_contents(self::credential_path()), true);

        return $json['web'] ?? $json['installed'] ?? [];
    }

    private static function redirect_uri(): string {
        return admin_url('admin.php?page=lvc-google-drive');
    }

    private static function access_token(): string {
        if (self::$token !== null) {
            return self::$token;
        }

        $refresh_token = get_option('lvc_google_refresh_token');

        if (!$refresh_token) {
            return '';
        }

        $config = self::oauth_config();

        if (empty($config['client_id']) || empty($config['client_secret'])) {
            return '';
        }

        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'body' => [
                'client_id'     => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'refresh_token' => $refresh_token,
                'grant_type'    => 'refresh_token',
            ],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            self::$last_error = $response->get_error_message();
            self::log_error("Request Token Error: " . self::$last_error);
            return '';
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['access_token'])) {
            self::$last_error = "Invalid token response.";
            self::log_error("Token Error Response: " . print_r($body, true));
            return '';
        }

        self::$token = $body['access_token'] ?? '';

        return self::$token;
    }

    public static function admin_menu(): void {
        add_submenu_page(
            'lestravida',
            'Google Drive',
            'Google Drive',
            'manage_woocommerce',
            'lvc-google-drive',
            [__CLASS__, 'admin_page']
        );
    }

    public static function admin_page(): void {
        $config = self::oauth_config();

        if (empty($config['client_id']) || empty($config['client_secret'])) {
            echo '<div class="wrap">';
            echo '<h1>LVC Google Drive</h1>';
            echo '<div class="notice notice-error"><p>File google-oauth.json tidak ditemukan atau tidak valid.</p></div>';
            echo '</div>';
            return;
        }

        if (isset($_GET['code'])) {
            $response = wp_remote_post('https://oauth2.googleapis.com/token', [
                'body' => [
                    'code'          => sanitize_text_field(wp_unslash($_GET['code'])),
                    'client_id'     => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'redirect_uri'  => self::redirect_uri(),
                    'grant_type'    => 'authorization_code',
                ],
                'timeout' => 30,
            ]);

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (!empty($body['refresh_token'])) {
                update_option('lvc_google_refresh_token', $body['refresh_token'], false);
                echo '<div class="notice notice-success"><p>Google Drive connected.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Gagal connect Google Drive.</p></div>';
                echo '<pre>' . esc_html(print_r($body, true)) . '</pre>';
            }
        }

        $auth_url = add_query_arg([
            'client_id'     => $config['client_id'] ?? '',
            'redirect_uri'  => self::redirect_uri(),
            'response_type' => 'code',
            'scope'         => 'https://www.googleapis.com/auth/drive',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
        ], 'https://accounts.google.com/o/oauth2/v2/auth');

        echo '<div class="wrap">';
        echo '<h1>LVC Google Drive</h1>';

        echo get_option('lvc_google_refresh_token')
            ? '<p><strong>Status:</strong> Connected</p>'
            : '<p><strong>Status:</strong> Not connected</p>';

        echo '<p><a class="button button-primary" href="' . esc_url($auth_url) . '">Connect Google Drive</a></p>';
        echo '</div>';
    }

    private static function headers($extra = []): array {
        return array_merge([
            'Authorization' => 'Bearer ' . self::access_token(),
        ], $extra);
    }

    private static function clean($name): string {
        $name = wp_strip_all_tags((string) $name);
        $name = preg_replace('/[\/\\\\:*?"<>|]/', '-', $name);
        $name = trim($name);

        return $name !== '' ? $name : 'Unknown';
    }

    private static function drive_query_escape($value): string {
        return str_replace("'", "\\'", $value);
    }

    private static function get_or_create_folder($name, $parent_id): string {
        $name = self::clean($name);
        $key  = $parent_id . '|' . $name;

        if (isset(self::$folder_cache[$key])) {
            return self::$folder_cache[$key];
        }

        $query = sprintf(
            "name = '%s' and mimeType = 'application/vnd.google-apps.folder' and '%s' in parents and trashed = false",
            self::drive_query_escape($name),
            self::drive_query_escape($parent_id)
        );

        $list_url = add_query_arg([
            'q'      => $query,
            'fields' => 'files(id,name)',
            'spaces' => 'drive',
        ], 'https://www.googleapis.com/drive/v3/files');

        $list = wp_remote_get($list_url, [
            'headers' => self::headers(),
            'timeout' => 30,
        ]);

        if (!is_wp_error($list)) {
            $body = json_decode(wp_remote_retrieve_body($list), true);

            if (!empty($body['files'][0]['id'])) {
                self::$folder_cache[$key] = $body['files'][0]['id'];
                return self::$folder_cache[$key];
            }
        }

        $created = wp_remote_post('https://www.googleapis.com/drive/v3/files', [
            'headers' => self::headers([
                'Content-Type' => 'application/json',
            ]),
            'body' => wp_json_encode([
                'name'     => $name,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents'  => [$parent_id],
            ]),
            'timeout' => 30,
        ]);

        if (is_wp_error($created)) {
            self::$last_error = $created->get_error_message();
            return '';
        }

        $body = json_decode(wp_remote_retrieve_body($created), true);

        self::$folder_cache[$key] = $body['id'] ?? '';

        return self::$folder_cache[$key];
    }

    private static function get_event_context(int $product_id): array {
        $product = wc_get_product($product_id);
    
        $term_product_id = $product_id;
    
        if ($product && $product->is_type('variation')) {
            $term_product_id = $product->get_parent_id();
        }
    
        $event   = 'Unknown Event';
        $chapter = '';
    
        $terms = get_the_terms($term_product_id, 'product_cat');
    
        if ($terms && !is_wp_error($terms)) {
            $top_term = null;
            $child_term = null;
            
            foreach ($terms as $term) {
                if ($term->parent == 0) {
                    $top_term = $term;
                } else {
                    $child_term = $term;
                }
            }
            
            // Jika admin hanya centang subkategori, cari parent tertingginya
            if (!$top_term && $child_term) {
                $ancestors = get_ancestors($child_term->term_id, 'product_cat');
                if (!empty($ancestors)) {
                    $top_term = get_term(end($ancestors), 'product_cat');
                } else {
                    $top_term = $child_term;
                    $child_term = null;
                }
            }
            
            if ($top_term && !is_wp_error($top_term)) {
                $event = $top_term->name;
            }
            
            if ($child_term && !is_wp_error($child_term)) {
                $chapter = 'Chapter ' . $child_term->name;
            }
        }
    
        $batch = 0;
    
        if (class_exists('LVK_Helper') && defined('LVK_Helper::META_BATCH')) {
            $batch = (int) get_post_meta($term_product_id, LVK_Helper::META_BATCH, true);
        }
    
        return [
            'event'   => $event,
            'chapter' => $chapter,
            'batch'   => 'Batch ' . max(0, $batch),
        ];
    }

    private static function upload_labels(): array {
        return [
            'bukti_lestravida_token' => [
                'label' => 'Bukti Follow Lestravida',
                'name'  => 'bukti-follow-lestravida',
                'legacy_meta' => '_lvc_drive_bukti_lestravida',
            ],
            'bukti_lvdaerah_token' => [
                'label' => 'Bukti Follow Lestravida Daerah',
                'name'  => 'bukti-follow-lestravida-daerah',
                'legacy_meta' => '_lvc_drive_bukti_lvdaerah',
            ],
            'bukti_share_token' => [
                'label' => 'Bukti Share Poster',
                'name'  => 'bukti-share-story',
                'legacy_meta' => '_lvc_drive_bukti_share',
            ],
        ];
    }

    private static function collect_uploads(WC_Order $order): array {
        $event_files = $order->get_meta('_lvc_event_upload_files');

        if (!is_array($event_files)) {
            return [];
        }

        $labels  = self::upload_labels();
        $uploads = [];

        foreach ($event_files as $product_id => $files) {
            $product_id = absint($product_id);

            if (!$product_id || !is_array($files)) {
                continue;
            }

            foreach ($labels as $key => $info) {
                if (empty($files[$key]['file'])) {
                    continue;
                }

                $file = $files[$key]['file'];

                if (!file_exists($file)) {
                    $order->add_order_note('LVC Debug: file tidak ditemukan untuk product #' . $product_id . ' - ' . $info['label'] . ' => ' . $file);
                    continue;
                }

                $uploads[] = [
                    'product_id' => $product_id,
                    'key'        => $key,
                    'file'       => $file,
                    'label'      => $info['label'],
                    'name'       => $info['name'],
                    'token'      => $files[$key]['token'] ?? '',
                    'legacy_meta'=> $info['legacy_meta'],
                ];
            }
        }

        return $uploads;
    }

    private static function upload_file($filepath, $filename, $folder_id): string {
        self::$last_error = '';

        if (!file_exists($filepath)) {
            self::$last_error = 'File lokal tidak ditemukan: ' . $filepath;
            return '';
        }

        $boundary = 'lvc_' . wp_generate_password(24, false);

        $metadata = [
            'name'    => self::clean($filename),
            'parents' => [$folder_id],
        ];

        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
        $body .= wp_json_encode($metadata) . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: " . mime_content_type($filepath) . "\r\n\r\n";
        $body .= file_get_contents($filepath) . "\r\n";
        $body .= "--{$boundary}--\r\n";

        $response = wp_remote_post(
            'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,webViewLink',
            [
                'headers' => self::headers([
                    'Content-Type' => 'multipart/related; boundary=' . $boundary,
                ]),
                'body'    => $body,
                'timeout' => 120,
            ]
        );

        if (is_wp_error($response)) {
            self::$last_error = $response->get_error_message();
            return '';
        }

        $code = wp_remote_retrieve_response_code($response);
        $raw  = wp_remote_retrieve_body($response);
        $result = json_decode($raw, true);

        if ($code < 200 || $code >= 300 || empty($result['id'])) {
            self::$last_error = 'HTTP ' . $code . ' - ' . $raw;
            return '';
        }

        return !empty($result['webViewLink'])
            ? $result['webViewLink']
            : 'https://drive.google.com/file/d/' . $result['id'] . '/view';
    }

    private static function order_folder_name(WC_Order $order): string {
        return 'Order-' . $order->get_id() . ' - ' . $order->get_formatted_billing_full_name();
    }

    private static function get_product_order_folder(WC_Order $order, int $product_id): string {
        $ctx = self::get_event_context($product_id);

        $root_folder_id = self::root_folder_id();

        if ($root_folder_id === '') {
            self::$last_error = 'Google Drive root_folder_id belum diatur di base_config.json.';
            return '';
        }

        $event_folder = self::get_or_create_folder($ctx['event'], $root_folder_id);

        if (!$event_folder) {
            return '';
        }

        $parent = $event_folder;

        if ($ctx['chapter'] !== '') {
            $parent = self::get_or_create_folder($ctx['chapter'], $parent);

            if (!$parent) {
                return '';
            }
        }

        $batch_folder = self::get_or_create_folder($ctx['batch'], $parent);

        if (!$batch_folder) {
            return '';
        }

        return self::get_or_create_folder(self::order_folder_name($order), $batch_folder);
    }

    public static function upload_order_files($order_id): void {
        $order = wc_get_order($order_id);

        if (!$order instanceof WC_Order) {
            return;
        }

        if ($order->get_meta('_lvc_drive_uploaded') === 'yes') {
            return;
        }

        if (!self::access_token()) {
            $order->add_order_note('LVC Google Drive: gagal membuat access token.');
            return;
        }

        $uploads = self::collect_uploads($order);

        $order->add_order_note('LVC Debug: jumlah upload ditemukan = ' . count($uploads));

        if (empty($uploads)) {
            $order->add_order_note('LVC Google Drive: tidak ada file temporary yang ditemukan.');
            $order->save();
            return;
        }

        $drive_files = $order->get_meta('_lvc_event_drive_files');

        if (!is_array($drive_files)) {
            $drive_files = [];
        }

        $success_count = 0;

        foreach ($uploads as $upload) {
            $folder_id = self::get_product_order_folder($order, (int) $upload['product_id']);

            if (!$folder_id) {
                $order->add_order_note('LVC Google Drive: gagal membuat folder untuk product #' . $upload['product_id']);
                continue;
            }

            $ext = pathinfo($upload['file'], PATHINFO_EXTENSION);
            $filename = $upload['name'] . ($ext ? '.' . $ext : '');

            $order->add_order_note(
                'LVC Debug: mencoba upload product #' . $upload['product_id'] . ' - ' . $filename
            );

            $url = self::upload_file($upload['file'], $filename, $folder_id);

            if ($url) {
                $product_id = (int) $upload['product_id'];
                $key = $upload['key'];

                $drive_files[$product_id][$key] = esc_url_raw($url);

                $order->add_order_note($upload['label'] . ' product #' . $product_id . ' berhasil diupload ke Google Drive.');

                $success_count++;

                @unlink($upload['file']);

                if (!empty($upload['token'])) {
                    delete_transient('lvc_upload_' . $upload['token']);

                    if (WC()->session) {
                        WC()->session->__unset('lvc_upload_' . $upload['token']);
                    }
                }
            } else {
                $order->update_meta_data('_lvc_drive_last_error', self::$last_error);
                $order->add_order_note($upload['label'] . ' gagal upload. Error: ' . self::$last_error);
            }
        }

        if ($drive_files) {
            $order->update_meta_data('_lvc_event_drive_files', $drive_files);

            if (count($drive_files) === 1) {
                $first = reset($drive_files);
                $labels = self::upload_labels();

                foreach ($labels as $key => $info) {
                    if (!empty($first[$key])) {
                        $order->update_meta_data($info['legacy_meta'], esc_url_raw($first[$key]));
                    }
                }
            }
        }

        $order->update_meta_data(
            '_lvc_drive_uploaded',
            $success_count >= count($uploads) ? 'yes' : 'partial'
        );

        $order->save();
    }

    public static function schedule_upload($order_id): void {
        if (!function_exists('as_enqueue_async_action')) {
            self::upload_order_files($order_id);
            return;
        }

        as_enqueue_async_action(
            'lvc_upload_order_files_to_drive',
            ['order_id' => $order_id],
            'lestravida-checkout'
        );
    }

    public static function hooks(): void {
        if (!class_exists('WooCommerce')) return;
        add_action(
            'woocommerce_checkout_order_processed',
            [__CLASS__, 'schedule_upload'],
            30
        );

        add_action(
            'lvc_upload_order_files_to_drive',
            function ($order_id) {
                self::upload_order_files($order_id);
            },
            10,
            1
        );

        add_action(
            'admin_menu',
            [__CLASS__, 'admin_menu']
        );
    }
}

LVC_Google_Drive::hooks();