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
        return apply_filters('lvk_tshirt_options', [
            'S'    => ['label' => 'S', 'price' => 88000, 'display_price' => '+88Rb'],
            'M'    => ['label' => 'M', 'price' => 88000, 'display_price' => '+88Rb'],
            'L'    => ['label' => 'L', 'price' => 88000, 'display_price' => '+88Rb'],
            '> XL' => ['label' => '> XL', 'price' => 98000, 'display_price' => '+98Rb'],
        ]);
    }

    /**
     * Tampilkan UI modern (Pills & Size Chart)
     */
    public static function render_tshirt_ui($product): void {
        if (!$product || !class_exists('LVK_Helper') || !LVK_Helper::is_event_product($product)) {
            return;
        }

        $options = self::get_tshirt_options();
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
                
                <div id="lvk-tshirt-validation-msg" style="display: none; color: #dc2626; font-size: 13px; margin-top: 6px; margin-bottom: 10px; font-weight: 500;">
                    ⚠️ Wajib pilih salah satu opsi di atas sebelum mendaftar.
                </div>

                <a href="#" id="lvk-show-size-chart" class="lvk-show-size-chart-link">Lihat Desain & Size Chart</a>

                <div id="lvk-size-chart-container" class="lvk-size-chart-wrapper">
                    <p class="lvk-size-chart-title">Desain & Size Chart (Placeholder)</p>
                    <img src="https://placehold.co/600x400/eeeeee/333333?text=Gambar+Baju+%26+Size+Chart" alt="Size Chart" />
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
        // Input wajib diisi sebelum add to cart
        echo '<input type="hidden" name="lvk_tshirt_size" id="lvk_tshirt_size_hidden" value="">';
    }

    /**
     * Validasi wajib pilih opsi baju sebelum pendaftaran (add to cart)
     */
    public static function validate_add_to_cart($passed, $product_id, $quantity) {
        $product = wc_get_product($product_id);
        
        if ($product && class_exists('LVK_Helper') && LVK_Helper::is_event_product($product)) {
            if (!isset($_POST['lvk_tshirt_size']) || $_POST['lvk_tshirt_size'] === '') {
                wc_add_notice('Silakan pilih opsi Order Baju (atau klik "Tidak Pesan") sebelum mendaftar.', 'error');
                return false;
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
            
            // Jika memilih Tidak Beli, tidak perlu masuk fee atau meta
            if ($size === 'Tidak Beli') {
                return $cart_item_data;
            }
            
            $options = self::get_tshirt_options();
            
            // Validasi apakah ukuran yang dikirim valid
            if (isset($options[$size])) {
                $cart_item_data['lvk_tshirt_size'] = $size;
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

LVK_Tshirt_Order::hooks();
