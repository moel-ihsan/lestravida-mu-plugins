<?php
/**
 * tshirt-order.php
 *
 * Mengelola fitur opsional pemesanan baju di halaman produk event.
 * Menggunakan pendekatan UI/UX modern (Pills & Hidden Input Sync).
 */

if (!defined('ABSPATH')) exit;

final class LVK_Tshirt_Order {

    /**
     * Hook utama
     */
    public static function hooks(): void {
        if (!class_exists('WooCommerce')) return;
        // Enqueue Assets
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);

        // Tampilkan desain UI baju di bawah Waktu Kegiatan
        add_action('lvk_after_waktu_kegiatan', [__CLASS__, 'render_tshirt_ui']);

        // Sisipkan hidden input di dalam form keranjang
        add_action('woocommerce_before_add_to_cart_button', [__CLASS__, 'render_hidden_input']);

        // Validasi agar user wajib memilih (mencegah klik daftar jika kosong)
        add_filter('woocommerce_add_to_cart_validation', [__CLASS__, 'validate_add_to_cart'], 10, 3);

        // Simpan opsi baju ke dalam item cart
        add_filter('woocommerce_add_cart_item_data', [__CLASS__, 'add_cart_item_data'], 10, 2);

        // Tampilkan opsi baju di halaman cart/checkout
        add_filter('woocommerce_get_item_data', [__CLASS__, 'get_item_data'], 10, 2);

        // Simpan opsi baju ke order meta (nota/struk admin)
        add_action('woocommerce_checkout_create_order_line_item', [__CLASS__, 'add_order_item_meta'], 10, 4);

        // Gabungkan harga baju ke harga item produk (menggantikan sistem Fee terpisah)
        add_action('woocommerce_before_calculate_totals', [__CLASS__, 'adjust_cart_item_price'], 10, 1);

        // Admin Metabox (Checkbox untuk mengaktifkan fitur baju per produk)
        add_action('woocommerce_product_options_general_product_data', [__CLASS__, 'add_admin_tshirt_checkbox']);
        add_action('woocommerce_process_product_meta', [__CLASS__, 'save_admin_tshirt_checkbox']);

        // Filter tombol "Gabung Sekarang" di halaman katalog (Loop)
        add_filter('woocommerce_loop_add_to_cart_link', [__CLASS__, 'modify_loop_button_link'], 10, 3);
    }

    /**
     * Tampilkan checkbox di wp-admin > Product Data > General
     */
    public static function add_admin_tshirt_checkbox() {
        woocommerce_wp_checkbox([
            'id'            => '_lvk_offer_tshirt',
            'wrapper_class' => 'show_if_simple',
            'label'         => __('Tawarkan Pembelian Baju (Opsional)', 'woocommerce'),
            'description'   => __('Jika dicentang, form pemesanan baju akan muncul di halaman kegiatan ini.', 'woocommerce'),
            'desc_tip'      => true,
        ]);
    }

    /**
     * Simpan nilai checkbox dari wp-admin
     */
    public static function save_admin_tshirt_checkbox($post_id) {
        $offer_tshirt = isset($_POST['_lvk_offer_tshirt']) ? 'yes' : 'no';
        update_post_meta($post_id, '_lvk_offer_tshirt', $offer_tshirt);
    }

    /**
     * Ubah fungsi tombol "Gabung Sekarang" di halaman Loop menjadi "Lihat Detail"
     * Untuk produk yang mewajibkan input opsi baju
     */
    public static function modify_loop_button_link($link, $product, $args) {
        if ($product && get_post_meta($product->get_id(), '_lvk_offer_tshirt', true) === 'yes') {
            $class = isset($args['class']) ? str_replace('ajax_add_to_cart', '', $args['class']) : 'button';
            
            return sprintf(
                '<a href="%s" data-quantity="%s" class="%s" %s>%s</a>',
                esc_url($product->get_permalink()),
                esc_attr(isset($args['quantity']) ? $args['quantity'] : 1),
                esc_attr($class),
                isset($args['attributes']) ? wc_implode_html_attributes($args['attributes']) : '',
                esc_html('Lihat Detail')
            );
        }
        return $link;
    }

    /**
     * Enqueue CSS & JS
     */
    public static function enqueue_assets(): void {
        if (!is_product()) {
            return;
        }

        $product_id = get_the_ID();
        $product = wc_get_product($product_id);

        if (!$product || !class_exists('LVK_Helper') || !LVK_Helper::is_event_product($product)) {
            return;
        }

        wp_enqueue_style(
            'lvk-tshirt-css',
            LVK_URL . 'assets/css/tshirt-order.css',
            [],
            filemtime(LVK_DIR . '/assets/css/tshirt-order.css')
        );

        wp_enqueue_script(
            'lvk-tshirt-js',
            LVK_URL . 'assets/js/tshirt-order.js',
            [],
            filemtime(LVK_DIR . '/assets/js/tshirt-order.js'),
            true
        );
    }

    /**
     * Data master opsi baju (agar tidak hardcode)
     */
    public static function get_tshirt_options(): array {
        $price_normal = (int) get_option('lvc_tshirt_price_normal', 88000);
        $price_extra  = (int) get_option('lvc_tshirt_price_extra', 98000);

        $display_normal = '+' . ($price_normal / 1000) . 'K';
        $display_extra  = '+' . ($price_extra / 1000) . 'K';

        return apply_filters('lvk_tshirt_options', [
            'S'    => ['label' => 'S', 'price' => $price_normal, 'display_price' => $display_normal],
            'M'    => ['label' => 'M', 'price' => $price_normal, 'display_price' => $display_normal],
            'L'    => ['label' => 'L', 'price' => $price_normal, 'display_price' => $display_normal],
            '> XL' => ['label' => '> XL', 'price' => $price_extra, 'display_price' => $display_extra],
        ]);
    }

    /**
     * Tampilkan UI modern (Pills & Size Chart)
     */
    public static function render_tshirt_ui($product): void {
        if (!$product || !class_exists('LVK_Helper') || !LVK_Helper::is_event_product($product)) {
            return;
        }

        // Cek apakah produk ini mengaktifkan penawaran baju
        if (get_post_meta($product->get_id(), '_lvk_offer_tshirt', true) !== 'yes') {
            return;
        }

        $options = self::get_tshirt_options();
        $image_url = get_option('lvc_tshirt_image_url', 'https://placehold.co/600x400/eeeeee/333333?text=Gambar+Baju+%26+Size+Chart');
        ?>
        <div class="lv-meta-item lvk-tshirt-ui-container">
            <span class="lv-meta-label">Order Baju Kegiatan</span>
            <div class="lv-meta-value">
                <div class="lvk-tshirt-options" id="lvk-tshirt-pills-container">
                    <!-- Default tidak ada yang active -->
                    <div class="lvk-tshirt-pill" data-value="Tidak Beli">
                        Tidak Pesan
                    </div>
                    <?php foreach ($options as $key => $data): ?>
                        <div class="lvk-tshirt-pill" data-value="<?php echo esc_attr($key); ?>">
                            <?php echo esc_html($data['label']); ?> <small><?php echo esc_html($data['display_price']); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div id="lvk-tshirt-custom-size-container" style="display: none; margin-top: 12px; margin-bottom: 12px;">
                    <input type="text" id="lvk-tshirt-custom-size-input" class="lvk-tshirt-custom-input" placeholder="Sebutkan ukuran spesifik (cth: XXL atau XXXL)">
                </div>
                
                <div id="lvk-tshirt-validation-msg" style="display: none; color: #dc2626; font-size: 13px; margin-top: 6px; margin-bottom: 10px; font-weight: 500;">
                    Mohon pilih preferensi Baju Kegiatan terlebih dahulu sebelum melanjutkan ke pendaftaran.
                </div>

                <a href="#" id="lvk-show-size-chart" class="lvk-show-size-chart-link">Lihat Desain & Size Chart</a>

                <div id="lvk-size-chart-container" class="lvk-size-chart-wrapper">
                    <img src="<?php echo esc_url($image_url); ?>" alt="Size Chart" />
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Sisipkan hidden input ke dalam form keranjang WooCommerce
     */
    public static function render_hidden_input(): void {
        global $product;
        if (!$product || !class_exists('LVK_Helper') || !LVK_Helper::is_event_product($product)) {
            return;
        }

        // Cek apakah produk ini mengaktifkan penawaran baju
        if (get_post_meta($product->get_id(), '_lvk_offer_tshirt', true) !== 'yes') {
            return;
        }

        // Input wajib diisi sebelum add to cart
        echo '<input type="hidden" name="lvk_tshirt_size" id="lvk_tshirt_size_hidden" value="">';
        echo '<input type="hidden" name="lvk_tshirt_custom_size" id="lvk_tshirt_custom_size_hidden" value="">';
    }

    /**
     * Validasi wajib pilih opsi baju sebelum pendaftaran (add to cart)
     */
    public static function validate_add_to_cart($passed, $product_id, $quantity) {
        $product = wc_get_product($product_id);
        
        if ($product && class_exists('LVK_Helper') && LVK_Helper::is_event_product($product)) {
            // Hanya validasi jika produk ini menawarkan baju
            if (get_post_meta($product->get_id(), '_lvk_offer_tshirt', true) === 'yes') {
                if (!isset($_POST['lvk_tshirt_size']) || $_POST['lvk_tshirt_size'] === '') {
                    wc_add_notice('Mohon pilih preferensi Baju Kegiatan terlebih dahulu sebelum melanjutkan ke pendaftaran.', 'error');
                    return false;
                }
                if ($_POST['lvk_tshirt_size'] === '> XL') {
                    if (!isset($_POST['lvk_tshirt_custom_size']) || trim($_POST['lvk_tshirt_custom_size']) === '') {
                        wc_add_notice('Mohon sebutkan ukuran spesifik Anda (contoh: XXL) untuk pilihan > XL.', 'error');
                        return false;
                    }
                }
            }
        }
        return $passed;
    }

    /**
     * Menyimpan input ukuran baju ke cart data
     */
    public static function add_cart_item_data($cart_item_data, $product_id) {
        if (isset($_POST['lvk_tshirt_size']) && !empty($_POST['lvk_tshirt_size'])) {
            $size = sanitize_text_field(wp_unslash($_POST['lvk_tshirt_size']));
            $custom_size = isset($_POST['lvk_tshirt_custom_size']) ? sanitize_text_field(wp_unslash($_POST['lvk_tshirt_custom_size'])) : '';
            
            // Jika memilih Tidak Beli, tidak perlu masuk fee atau meta
            if ($size === 'Tidak Beli') {
                return $cart_item_data;
            }
            
            $options = self::get_tshirt_options();
            
            // Validasi apakah ukuran yang dikirim valid
            if (isset($options[$size])) {
                $actual_size = ($size === '> XL' && !empty(trim($custom_size))) ? strtoupper(trim($custom_size)) : $size;
                $cart_item_data['lvk_tshirt_size'] = $actual_size;
                $cart_item_data['lvk_tshirt_fee'] = $options[$size]['price'];
                
                // Buat kunci unik agar item di cart tidak digabung jika pesan ukuran beda
                $cart_item_data['unique_key'] = md5(microtime() . rand());
            }
        }
        return $cart_item_data;
    }

    /**
     * Tampilkan data baju di halaman cart
     */
    public static function get_item_data($item_data, $cart_item_data) {
        if (isset($cart_item_data['lvk_tshirt_size'])) {
            $item_data[] = [
                'key'   => 'Tambahan Baju',
                'value' => 'Ukuran ' . esc_html($cart_item_data['lvk_tshirt_size']) . ' (+ Rp ' . number_format($cart_item_data['lvk_tshirt_fee'], 0, ',', '.') . ')'
            ];
        }
        return $item_data;
    }

    /**
     * Simpan data baju ke Order Item Meta (agar admin bisa lihat struk)
     */
    public static function add_order_item_meta($item, $cart_item_key, $values, $order) {
        if (isset($values['lvk_tshirt_size'])) {
            $item->add_meta_data('Tambahan Baju', 'Ukuran ' . $values['lvk_tshirt_size']);
        }
    }

    /**
     * Gabungkan total harga baju ke harga produk di cart.
     * Ini mengatasi bug di mini-cart di mana fee terpisah tidak dihitung ke subtotal.
     */
    public static function adjust_cart_item_price($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        // Hindari infinite loop atau kalkulasi ganda
        if (did_action('woocommerce_before_calculate_totals') >= 2) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['lvk_tshirt_fee'])) {
                // Ambil harga dasar asli dari produk, bukan dari objek yang mungkin sudah termanipulasi
                $original_product = wc_get_product($cart_item['product_id']);
                if ($original_product) {
                    $base_price = (float) $original_product->get_price();
                    $new_price = $base_price + $cart_item['lvk_tshirt_fee'];
                    $cart_item['data']->set_price($new_price);
                }
            }
        }
    }
}

add_action('plugins_loaded', ['LVK_Tshirt_Order', 'hooks']);
