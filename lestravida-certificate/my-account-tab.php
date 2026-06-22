<?php

if (!defined('ABSPATH')) exit;

class LVCERT_Frontend_Account {
    public static function init() {
        // Daftarkan endpoint custom URL
        add_action('init', [__CLASS__, 'add_endpoints']);
        
        // Daftarkan query vars
        add_filter('query_vars', [__CLASS__, 'add_query_vars'], 0);

        // Sisipkan item menu di My Account
        add_filter('woocommerce_account_menu_items', [__CLASS__, 'add_menu_item']);

        // Tampilkan konten di endpoint
        add_action('woocommerce_account_sertifikat_endpoint', [__CLASS__, 'render_endpoint_content']);
    }

    public static function add_endpoints() {
        add_rewrite_endpoint('sertifikat', EP_ROOT | EP_PAGES);
    }

    public static function add_query_vars($vars) {
        $vars[] = 'sertifikat';
        return $vars;
    }

    public static function add_menu_item($items) {
        // Kita ingin menyisipkan menu 'Sertifikat' setelah 'Orders'
        $new_items = [];
        foreach ($items as $key => $value) {
            $new_items[$key] = $value;
            if ($key === 'orders') {
                $new_items['sertifikat'] = __('Sertifikat', 'lestravida');
            }
        }
        return $new_items;
    }

    public static function render_endpoint_content() {
        $user_id = get_current_user_id();
        if (!$user_id) return;

        // Ambil semua order user yang komplit
        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'status'      => 'completed',
            'limit'       => -1,
        ]);

        $certificates_found = false;

        echo '<h3>Sertifikat Kegiatan Anda</h3>';
        echo '<p>Berikut adalah daftar sertifikat dari kegiatan yang telah Anda selesaikan.</p>';

        if (!empty($orders)) {
            echo '<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">';
            echo '<thead><tr>';
            echo '<th class="woocommerce-orders-table__header"><span class="nobr">Nama Kegiatan</span></th>';
            echo '<th class="woocommerce-orders-table__header"><span class="nobr">Selesai</span></th>';
            echo '<th class="woocommerce-orders-table__header"><span class="nobr">Tindakan</span></th>';
            echo '</tr></thead>';
            echo '<tbody>';

            foreach ($orders as $order) {
                foreach ($order->get_items() as $item) {
                    $product = $item->get_product();
                    if ($product) {
                        $is_cert_enabled = get_post_meta($product->get_id(), '_lvk_cert_enabled', true);
                        if ($is_cert_enabled === 'yes') {
                            $certificates_found = true;
                            $download_url = add_query_arg([
                                'lvk_download_cert' => $order->get_id(),
                                'item_id' => $item->get_id(),
                                '_nonce' => wp_create_nonce('download_cert_' . $order->get_id())
                            ], home_url('/'));
                            
                            echo '<tr class="woocommerce-orders-table__row">';
                            echo '<td class="woocommerce-orders-table__cell" data-title="Kegiatan">' . esc_html($product->get_name()) . '</td>';
                            echo '<td class="woocommerce-orders-table__cell" data-title="Selesai">' . esc_html(wc_format_datetime($order->get_date_completed())) . '</td>';
                            echo '<td class="woocommerce-orders-table__cell" data-title="Tindakan">';
                            echo '<a href="' . esc_url($download_url) . '" class="woocommerce-button button view" target="_blank" style="background-color: #22c55e; color: #fff; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 500;">Unduh PDF/Gambar</a>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    }
                }
            }

            echo '</tbody></table>';
        }

        if (!$certificates_found) {
            echo '<div class="woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">';
            echo 'Anda belum memiliki sertifikat yang tersedia untuk diunduh.';
            echo '</div>';
        }
    }
}

LVCERT_Frontend_Account::init();
