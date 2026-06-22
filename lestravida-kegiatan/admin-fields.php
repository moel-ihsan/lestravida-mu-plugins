<?php
/**
 * admin-fields.php
 *
 * Custom WooCommerce admin fields untuk produk kegiatan LVK.
 *
 * @package LVK
 */

if (!defined('ABSPATH')) exit;

final class LVK_Admin_Fields {

    /**
     * =========================================================
     * TEXTAREA FIELD IDS
     * =========================================================
     */
    const TEXTAREA_FIELDS = [
        LVK_Helper::META_DETAIL,
        LVK_Helper::META_FASILITAS,
        LVK_Helper::META_KRITERIA,
    ];

    /**
     * =========================================================
     * URL FIELD IDS
     * =========================================================
     */
    const URL_FIELDS = [
        LVK_Helper::META_DOKUMEN_URL,
        LVK_Helper::META_WA_GROUP,
        LVK_Helper::META_IG_DAERAH
    ];

    /**
     * =========================================================
     * ALL FIELD IDS
     * =========================================================
     */
    const FIELD_KEYS = [
        LVK_Helper::META_DETAIL,
        LVK_Helper::META_FASILITAS,
        LVK_Helper::META_KRITERIA,
        LVK_Helper::META_LOKASI,
        LVK_Helper::META_TANGGAL,
        LVK_Helper::META_JAM,
        LVK_Helper::META_DOKUMEN_URL,
        LVK_Helper::META_DOKUMEN_LABEL,
        LVK_Helper::META_BATCH,
        LVK_Helper::META_WA_GROUP,
        LVK_Helper::META_IG_DAERAH,
    ];

    /**
     * =========================================================
     * GENERAL TAB
     * =========================================================
     */
    public static function render_general_fields(): void {

        echo '<div class="options_group lestravida-fields">';

        woocommerce_wp_textarea_input([
            'id'          => LVK_Helper::META_DETAIL,
            'label'       => __('Detail Kegiatan', 'lestravida'),
            'desc_tip'    => true,
            'description' => __('Tulis detail kegiatan. Bullet list otomatis didukung.', 'lestravida'),
            'rows'        => 5,
        ]);

        woocommerce_wp_textarea_input([
            'id'          => LVK_Helper::META_FASILITAS,
            'label'       => __('Fasilitas', 'lestravida'),
            'desc_tip'    => true,
            'description' => __('Satu fasilitas per baris.', 'lestravida'),
            'rows'        => 4,
        ]);

        woocommerce_wp_textarea_input([
            'id'          => LVK_Helper::META_KRITERIA,
            'label'       => __('Kriteria Peserta', 'lestravida'),
            'desc_tip'    => true,
            'description' => __('Satu kriteria per baris.', 'lestravida'),
            'rows'        => 4,
        ]);

        woocommerce_wp_text_input([
            'id'          => LVK_Helper::META_LOKASI,
            'label'       => __('Lokasi Kegiatan', 'lestravida'),
            'placeholder' => 'Contoh: Yogyakarta, Indonesia',
            'desc_tip'    => true,
            'description' => __('Ditampilkan di halaman shop & single.', 'lestravida'),
        ]);

        woocommerce_wp_text_input([
            'id'                => LVK_Helper::META_BATCH,
            'label'             => __('Batch Kegiatan', 'lestravida'),
            'placeholder'       => 'Contoh: 1',
            'type'              => 'number',
            'custom_attributes' => [
                'min'  => '1',
                'step' => '1',
            ],
            'desc_tip'          => true,
            'description'       => __('Nomor batch kegiatan.', 'lestravida'),
        ]);

        woocommerce_wp_text_input([
            'id'          => LVK_Helper::META_TANGGAL,
            'label'       => __('Tanggal Kegiatan', 'lestravida'),
            'type'        => 'date',
            'desc_tip'    => true,
            'description' => __('Pendaftaran otomatis ditutup mulai H-1.', 'lestravida'),
        ]);

        woocommerce_wp_text_input([
            'id'          => LVK_Helper::META_JAM,
            'label'       => __('Jam Kegiatan', 'lestravida'),
            'type'        => 'time',
            'desc_tip'    => true,
            'description' => __('Format 24 jam.', 'lestravida'),
        ]);

        woocommerce_wp_text_input([
            'id'          => LVK_Helper::META_DOKUMEN_URL,
            'label'       => __('Link Dokumen (URL)', 'lestravida'),
            'placeholder' => 'https://contoh.com/file.pdf',
            'type'        => 'url',
            'desc_tip'    => true,
            'description' => __('Google Drive, PDF, halaman informasi, dll.', 'lestravida'),
        ]);

        woocommerce_wp_text_input([
            'id'          => LVK_Helper::META_DOKUMEN_LABEL,
            'label'       => __('Teks Tombol (Opsional)', 'lestravida'),
            'placeholder' => 'Lihat Dokumen',
            'desc_tip'    => true,
            'description' => __('Kosongkan untuk teks default.', 'lestravida'),
        ]);

        woocommerce_wp_text_input([
            'id'          => LVK_Helper::META_WA_GROUP,
            'label'       => __('Link Grup WhatsApp', 'lestravida'),
            'placeholder' => 'https://chat.whatsapp.com/xxxxx',
            'type'        => 'url',
            'desc_tip'    => true,
            'description' => __('Gunakan link resmi grup WhatsApp peserta.', 'lestravida'),
        ]);


        woocommerce_wp_text_input([
            'id'          => LVK_Helper::META_IG_DAERAH,
            'label'       => __('Link IG Event', 'lestravida'),
            'placeholder' => 'https://instagram.com/xxxxx',
            'type'        => 'url',
            'desc_tip'    => true,
            'description' => __('Gunakan link resmi IG Event Daerah', 'lestravida'),
        ]);
        
        echo '</div>';
    }

    /**
     * =========================================================
     * INVENTORY TAB HELP
     * =========================================================
     */
    public static function inventory_helper(): void {

        echo '
        <div class="options_group lestravida-inventory-helper">
            <p style="padding:10px 12px;margin:0;color:#555;">
                Gunakan inventory WooCommerce sebagai:
                <br><br>
                • <strong>Track stock quantity</strong> → Aktifkan Kuota Peserta
                <br>
                • <strong>Quantity</strong> → Jumlah Kuota Peserta
                <br>
                • <strong>Limit purchases to 1 item per order</strong> → Batasi 1 slot per orang
            </p>
        </div>';
    }

    /**
     * =========================================================
     * SAVE FIELDS
     * =========================================================
     */
    public static function save_fields($post_id): void {

        /**
         * =====================================================
         * BASIC GUARDS
         * =====================================================
         */
        if (
            defined('DOING_AUTOSAVE')
            && DOING_AUTOSAVE
        ) {
            return;
        }

        if (wp_is_post_revision($post_id)) {
            return;
        }

        /**
         * =====================================================
         * CAPABILITY CHECK
         * =====================================================
         */
        if (!current_user_can('edit_product', $post_id)) {
            return;
        }

        /**
         * =====================================================
         * NONCE CHECK
         * =====================================================
         */
        if (
            !isset($_POST['woocommerce_meta_nonce'])
            || !wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash($_POST['woocommerce_meta_nonce'])
                ),
                'woocommerce_save_data'
            )
        ) {
            return;
        }

        foreach (self::FIELD_KEYS as $key) {

            if (!isset($_POST[$key])) {
                continue;
            }

            $raw = wp_unslash($_POST[$key]);

            /**
             * =================================================
             * TEXTAREA
             * =================================================
             */
            if (in_array($key, self::TEXTAREA_FIELDS, true)) {

                $val = sanitize_textarea_field($raw);

            /**
             * =================================================
             * INTEGER
             * =================================================
             */
            } elseif ($key === LVK_Helper::META_BATCH) {
            
                $raw = trim((string) $raw);
                $val = ($raw === '') ? '' : max(0, (int) $raw);

            /**
             * =================================================
             * URL
             * =================================================
             */
            } elseif (in_array($key, self::URL_FIELDS, true)) {

                $val = esc_url_raw($raw);

            /**
             * =================================================
             * NORMAL TEXT
             * =================================================
             */
            } else {

                $val = sanitize_text_field($raw);
            }

            if ($val === '' || $val === null) {
                delete_post_meta($post_id, $key);
                continue;
            }
            
            $old = get_post_meta($post_id, $key, true);
            
            if ((string) $old !== (string) $val) {
                update_post_meta($post_id, $key, $val);
            }
        }
    }

    /**
     * =========================================================
     * ADMIN UI CUSTOMIZATION
     * =========================================================
     */
    public static function enqueue_admin_assets(): void {

        $screen = function_exists('get_current_screen')
            ? get_current_screen()
            : null;

        if (
            !$screen
            || $screen->id !== 'product'
        ) {
            return;
        }

        /**
         * =====================================================
         * CSS
         * =====================================================
         */
        $css_path = LVK_DIR . '/assets/admin/css/admin-fields.css';

        $css_version = file_exists($css_path)
            ? filemtime($css_path)
            : '1.0.0';

        wp_enqueue_style(
            'lvk-admin-fields',
            LVK_URL . 'assets/admin/css/admin-fields.css',
            [],
            $css_version
        );

        /**
         * =====================================================
         * JS
         * =====================================================
         */
        $js_path = LVK_DIR . '/assets/admin/js/admin-fields.js';

        $js_version = file_exists($js_path)
            ? filemtime($js_path)
            : '1.0.0';

        wp_enqueue_script(
            'lvk-admin-fields',
            LVK_URL . 'assets/admin/js/admin-fields.js',
            ['jquery'],
            $js_version,
            true
        );
    }

    /**
     * =========================================================
     * AUTO VIRTUAL PRODUCT
     * =========================================================
     */
    public static function auto_virtual_product($product): void {

        if (!$product instanceof WC_Product) {
            return;
        }

        $product->set_virtual(true);
    }

    /**
     * =========================================================
     * HOOKS
     * =========================================================
     */
    public static function hooks(): void {
        if (!class_exists('WooCommerce')) return;

        add_action(
            'woocommerce_product_options_general_product_data',
            [__CLASS__, 'render_general_fields']
        );

        add_action(
            'woocommerce_product_options_inventory_product_data',
            [__CLASS__, 'inventory_helper'],
            1
        );

        add_action(
            'save_post_product',
            [__CLASS__, 'save_fields'],
            20
        );

        add_action(
            'woocommerce_admin_process_product_object',
            [__CLASS__, 'auto_virtual_product']
        );

        add_action(
            'admin_enqueue_scripts',
            [__CLASS__, 'enqueue_admin_assets']
        );
    }
}

add_action('init', ['LVK_Admin_Fields', 'hooks']);