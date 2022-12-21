<?php

defined('ABSPATH') ?: exit();

if (!trait_exists('Add_URL_Order_Note')) :

    trait Add_URL_Order_Note {

        /**
         * Add order URL to order notes
         *
         * @param int $order_id
         * @return void
         */
        public static function add_referer_url_order_note($order_id) {
            $order = wc_get_order($order_id);
            $order->add_order_note('Checkout url: ' . $_SERVER['HTTP_REFERER']);
        }
    }

endif;
