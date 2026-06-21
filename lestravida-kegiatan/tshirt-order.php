<?php
/**
 * tshirt-order.php
 *
 * Mengelola fitur opsional pemesanan baju di halaman produk event.
 */

if (!defined('ABSPATH')) exit;

final class LVK_Tshirt_Order {

    /**
     * Hook utama
     */
    public static function hooks(): void {
        // Tampilkan opsi baju di halaman produk
        add_action('woocommerce_before_add_to_cart_button', [__CLASS__, 'render_tshirt_field']);

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
     * Tampilkan field dropdown ukuran baju
     */
    public static function render_tshirt_field(): void {
        global $product;

        if (!$product || !class_exists('LVK_Helper') || !LVK_Helper::is_event_product($product)) {
            return;
        }

        ?>
        <div class="lvk-tshirt-wrapper" style="margin-bottom: 20px; border-top: 1px solid #eaeaea; padding-top: 15px;">
            <label for="lvk_tshirt_size" style="display: block; font-weight: bold; margin-bottom: 8px;">👕 Order Baju (Opsional)</label>
            <select name="lvk_tshirt_size" id="lvk_tshirt_size" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; max-width: 350px;">
                <option value="">Tidak Pesan Baju</option>
                <option value="S">Ukuran S (+ Rp 88.000)</option>
                <option value="M">Ukuran M (+ Rp 88.000)</option>
                <option value="L">Ukuran L (+ Rp 88.000)</option>
                <option value="> XL">Ukuran Besar (&gt; XL) (+ Rp 98.000)</option>
            </select>
            
            <div style="margin-top: 8px;">
                <a href="#" id="lvk-show-size-chart" style="font-size: 13px; text-decoration: underline; color: #007cba;">Lihat Desain & Size Chart</a>
            </div>

            <!-- Modal / Image Placeholder (Tersembunyi by default) -->
            <div id="lvk-size-chart-container" style="display: none; margin-top: 15px; border: 1px solid #ddd; padding: 10px; border-radius: 8px; background: #fafafa;">
                <p style="font-size: 12px; margin-bottom: 10px; font-weight: bold; color: #555;">Desain & Size Chart (Placeholder)</p>
                <img src="https://placehold.co/600x400/eeeeee/333333?text=Gambar+Baju+%26+Size+Chart" alt="Size Chart" style="max-width: 100%; height: auto; border-radius: 4px;" />
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var btn = document.getElementById('lvk-show-size-chart');
                var container = document.getElementById('lvk-size-chart-container');
                
                if(btn && container) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        if (container.style.display === 'none') {
                            container.style.display = 'block';
                            btn.innerText = 'Sembunyikan Desain & Size Chart';
                        } else {
                            container.style.display = 'none';
                            btn.innerText = 'Lihat Desain & Size Chart';
                        }
                    });
                }
            });
        </script>
        <?php
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
