<?php
defined('ABSPATH') ?: exit();

if (!trait_exists('Load_Resources')) :

    trait Load_Resources {

        /**
         * Enqueue CSS, JS et al
         *
         * @return void
         */
        public static function load_resources() {

            $req = array('jquery');

            wp_enqueue_style('bd_common_style', BD_PLUGIN_URL . 'resources/style/common.css', array(), BDVersion, 'all');
            wp_enqueue_style('bd_style', BD_PLUGIN_URL . 'resources/style/front_style.css', array(), BDVersion . time(), 'all');

            wp_enqueue_script('bd_front_script_js', BD_PLUGIN_URL . 'resources/js/front_js.js', $req, time(), true);

            global $woocommerce;
            $cart_url = '/cart/';
            $checkout_url = '/checkout/';
            if (!empty($woocommerce)) {
                $cart_url = wc_get_cart_url();
                $checkout_url = wc_get_checkout_url();
            }

            wp_localize_script(
                'bd_front_script_js',
                'bd_infos',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'home_url' => home_url(),
                    'cart_url' => $cart_url,
                    'checkout_url' => $checkout_url
                )
            );
        }
    }

endif;
