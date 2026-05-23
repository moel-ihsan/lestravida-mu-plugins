<?php
/**
 * checkout-fee.php
 *
 * Auto markup harga produk LVK.
 */

if (!defined('ABSPATH')) exit;

final class LVK_Checkout_Fee {

    /**
     * =========================================================
     * CONFIG
     * =========================================================
     */
    const EXTRA_AMOUNT = 4000;

    /**
     * =========================================================
     * MODIFY PRICE
     * =========================================================
     */
    public static function modify_price($price, $product) {

        if (!$product instanceof WC_Product) {
            return $price;
        }

        /**
         * Hindari admin edit product
         */
        if (
            is_admin()
            && !wp_doing_ajax()
        ) {
            return $price;
        }

        $price = (float) $price;

        if ($price <= 0) {
            return $price;
        }

        return $price + self::EXTRA_AMOUNT;
    }

    /**
     * =========================================================
     * HOOKS
     * =========================================================
     */
    public static function hooks(): void {

        add_filter(
            'woocommerce_product_get_price',
            [__CLASS__, 'modify_price'],
            10,
            2
        );

        add_filter(
            'woocommerce_product_get_regular_price',
            [__CLASS__, 'modify_price'],
            10,
            2
        );

        add_filter(
            'woocommerce_product_variation_get_price',
            [__CLASS__, 'modify_price'],
            10,
            2
        );

        add_filter(
            'woocommerce_product_variation_get_regular_price',
            [__CLASS__, 'modify_price'],
            10,
            2
        );
    }
}

add_action('init', ['LVK_Checkout_Fee', 'hooks']);