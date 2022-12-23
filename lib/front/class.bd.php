<?php

defined('ABSPATH') ?: exit();

if (!class_exists('BD')) :

    // include traits
    require_once __DIR__ . '/traits/class.bd/trait-add-url-order-note.php';
    require_once __DIR__ . '/traits/class.bd/trait-atc-multiple.php';
    require_once __DIR__ . '/traits/class.bd/trait-calc-cart-fees.php';
    require_once __DIR__ . '/traits/class.bd/trait-cart-updated.php';
    require_once __DIR__ . '/traits/class.bd/trait-get-price-pkg.php';
    require_once __DIR__ . '/traits/class.bd/trait-get-price-var.php';
    require_once __DIR__ . '/traits/class.bd/trait-load-resources.php';
    require_once __DIR__ . '/traits/class.bd/trait-return-linked-prod-dd.php';
    require_once __DIR__ . '/traits/class.bd/trait-return-opc-var-dd.php';
    require_once __DIR__ . '/traits/class.bd/trait-return-wc-var-attrib-dd.php';
    require_once __DIR__ . '/traits/class.bd/trait-apply-reg-price-cart.php';
    require_once __DIR__ . '/traits/class.bd/trait-apply-reg-price-mini-cart.php';

    class BD {

        // Traits
        use Add_URL_Order_Note,
            Add_To_Cart_Multiple,
            Calculate_Cart_Fees,
            Cart_Updated,
            Get_Package_Price,
            Get_Price_Variations,
            Load_Resources,
            Return_Linked_Product_Dropdown,
            Return_OPC_Variations_Dropdown,
            Return_WC_Variation_Attrib_Dropdown,
            Apply_Regular_Price_Cart,
            Apply_Regular_Price_Mini_Cart;

        // Properties
        private static $initiated = false;
        public static $bd_products_variations = array();
        public static $bd_products_variations_prices = array();
        public static $bd_product_variations = array();

        /**
         * Class init
         *
         * @return void
         */
        public static function init() {
            if (!self::$initiated) {
                self::init_hooks();
            }
        }

        /**
         * Initializes WordPress hooks
         */
        private static function init_hooks() {
            self::$initiated = true;

            add_action('wp_enqueue_scripts', array(__CLASS__, 'load_resources'));

            // action ajax add products to cart
            add_action('wp_ajax_bd_add_to_cart_multiple', array(__CLASS__, 'bd_add_to_cart_multiple'));
            add_action('wp_ajax_nopriv_bd_add_to_cart_multiple', array(__CLASS__, 'bd_add_to_cart_multiple'));

            // action get price variation product bd
            add_action('wp_ajax_bd_get_price_variation_product', array(__CLASS__, 'bd_get_price_variation_product'));
            add_action('wp_ajax_nopriv_bd_get_price_variation_product', array(__CLASS__, 'bd_get_price_variation_product'));

            // action get price bd package
            add_action('wp_ajax_bd_get_price_package', array(__CLASS__, 'bd_get_price_package'));
            add_action('wp_ajax_nopriv_bd_get_price_package', array(__CLASS__, 'bd_get_price_package'));

            // apply regular pricing to cart
            add_action('woocommerce_before_calculate_totals', array(__CLASS__, 'apply_regular_price_cart'));

            // apply regular pricing to mini cart
            add_filter('woocommerce_cart_item_price', array(__CLASS__, 'apply_regular_price_mini_cart'), 30, 3);

            // remove discount if cart qtys updated
            add_action('woocommerce_update_cart_action_cart_updated', array(__CLASS__, 'on_action_cart_updated'), 20, 1);

            // calculate discount fees and apply to cart
            add_action('woocommerce_cart_calculate_fees', array(__CLASS__, 'add_calculate_bundle_fee'), PHP_INT_MAX);

            // action add referer to order note
            add_action('woocommerce_order_status_processing', array(__CLASS__, 'add_referer_url_order_note'), 10, 1);
        }
    }
endif;
