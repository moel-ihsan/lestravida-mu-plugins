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
        // Tampilkan desain UI baju di bawah Waktu Kegiatan
        add_action('lvk_after_waktu_kegiatan', [__CLASS__, 'render_tshirt_ui']);

        // Sisipkan hidden input di dalam form keranjang
        add_action('woocommerce_before_add_to_cart_button', [__CLASS__, 'render_hidden_input']);

        // Simpan opsi baju ke dalam item cart
        add_filter('woocommerce_add_cart_item_data', [__CLASS__, 'add_cart_item_data'], 10, 2);

        // Tampilkan opsi baju di halaman cart/checkout
        add_filter('woocommerce_get_item_data', [__CLASS__, 'get_item_data'], 10, 2);

        // Simpan opsi baju ke order meta (nota/struk admin)
        add_action('woocommerce_checkout_create_order_line_item', [__CLASS__, 'add_order_item_meta'], 10, 4);

        // Tambahkan harga ekstra ke total cart sebagai Fee
        add_action('woocommerce_cart_calculate_fees', [__CLASS__, 'calculate_fees'], 10, 1);
    }

    /**
     * Tampilkan UI modern (Pills & Size Chart)
     */
    public static function render_tshirt_ui($product): void {
        if (!$product || !class_exists('LVK_Helper') || !LVK_Helper::is_event_product($product)) {
            return;
        }

        ?>
        <div class="lv-meta-item lvk-tshirt-ui-container" style="border-top: 1px dashed #eaeaea; margin-top: 15px; padding-top: 15px;">
            <span class="lv-meta-label" style="display: block; margin-bottom: 8px;">👕 Order Baju Kepanitiaan (Opsional)</span>
            <div class="lv-meta-value">
                <style>
                    .lvk-tshirt-options {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 8px;
                        margin-bottom: 10px;
                    }
                    .lvk-tshirt-pill {
                        padding: 8px 14px;
                        border: 2px solid #ddd;
                        border-radius: 8px;
                        cursor: pointer;
                        font-size: 13px;
                        font-weight: 600;
                        color: #666;
                        background: #fff;
                        transition: all 0.2s ease;
                        user-select: none;
                    }
                    .lvk-tshirt-pill:hover {
                        border-color: #007cba;
                        color: #007cba;
                    }
                    .lvk-tshirt-pill.active {
                        border-color: #007cba;
                        background: #007cba;
                        color: #fff;
                    }
                    .lvk-tshirt-pill small {
                        display: block;
                        font-size: 11px;
                        font-weight: normal;
                        opacity: 0.85;
                        margin-top: 2px;
                    }
                </style>

                <div class="lvk-tshirt-options" id="lvk-tshirt-pills-container">
                    <div class="lvk-tshirt-pill active" data-value="">
                        Tidak Pesan
                    </div>
                    <div class="lvk-tshirt-pill" data-value="S">
                        S <small>+88Rb</small>
                    </div>
                    <div class="lvk-tshirt-pill" data-value="M">
                        M <small>+88Rb</small>
                    </div>
                    <div class="lvk-tshirt-pill" data-value="L">
                        L <small>+88Rb</small>
                    </div>
                    <div class="lvk-tshirt-pill" data-value="> XL">
                        > XL <small>+98Rb</small>
                    </div>
                </div>

                <a href="#" id="lvk-show-size-chart" style="font-size: 13px; text-decoration: underline; color: #007cba; display: inline-block; margin-top: 4px;">Lihat Desain & Size Chart</a>

                <!-- Modal / Image Placeholder (Tersembunyi by default) -->
                <div id="lvk-size-chart-container" style="display: none; margin-top: 15px; border: 1px solid #ddd; padding: 10px; border-radius: 8px; background: #fafafa;">
                    <img src="https://placehold.co/600x400/eeeeee/333333?text=Gambar+Baju+%26+Size+Chart" alt="Size Chart" style="max-width: 100%; height: auto; border-radius: 4px; display: block;" />
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var pills = document.querySelectorAll('.lvk-tshirt-pill');
                var hiddenInput = document.getElementById('lvk_tshirt_size_hidden');
                
                // Pill Click Logic
                pills.forEach(function(pill) {
                    pill.addEventListener('click', function() {
                        // Hapus active class dari semua pill
                        pills.forEach(p => p.classList.remove('active'));
                        // Tambahkan active class ke pill yang diklik
                        this.classList.add('active');
                        
                        // Sinkronisasi ke hidden input jika ada
                        if (hiddenInput) {
                            hiddenInput.value = this.getAttribute('data-value');
                        }
                    });
                });

                // Size Chart Toggle Logic
                var btnChart = document.getElementById('lvk-show-size-chart');
                var chartContainer = document.getElementById('lvk-size-chart-container');
                
                if (btnChart && chartContainer) {
                    btnChart.addEventListener('click', function(e) {
                        e.preventDefault();
                        if (chartContainer.style.display === 'none') {
                            chartContainer.style.display = 'block';
                            btnChart.innerText = 'Tutup Desain & Size Chart';
                        } else {
                            chartContainer.style.display = 'none';
                            btnChart.innerText = 'Lihat Desain & Size Chart';
                        }
                    });
                }
            });
        </script>
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
        // Input ini akan dikirim saat klik "Daftar" (Add to Cart)
        echo '<input type="hidden" name="lvk_tshirt_size" id="lvk_tshirt_size_hidden" value="">';
    }

    /**
     * Menyimpan input ukuran baju ke cart data
     */
    public static function add_cart_item_data($cart_item_data, $product_id) {
        if (isset($_POST['lvk_tshirt_size']) && !empty($_POST['lvk_tshirt_size'])) {
            $size = sanitize_text_field(wp_unslash($_POST['lvk_tshirt_size']));
            $cart_item_data['lvk_tshirt_size'] = $size;
            
            // Tentukan tambahan harga berdasarkan ukuran
            $extra_fee = 88000;
            if ($size === '> XL') {
                $extra_fee = 98000;
            }
            $cart_item_data['lvk_tshirt_fee'] = $extra_fee;
            
            // Buat kunci unik agar item di cart tidak digabung jika pesan ukuran beda
            $cart_item_data['unique_key'] = md5(microtime() . rand());
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
     * Tambahkan total harga item baju sebagai Fee ke cart
     */
    public static function calculate_fees($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        $total_tshirt_fee = 0;

        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['lvk_tshirt_fee'])) {
                $qty = (int) $cart_item['quantity'];
                $total_tshirt_fee += ($cart_item['lvk_tshirt_fee'] * $qty);
            }
        }

        if ($total_tshirt_fee > 0) {
            $cart->add_fee('Tambahan Order Baju', $total_tshirt_fee, true);
        }
    }
}

LVK_Tshirt_Order::hooks();
