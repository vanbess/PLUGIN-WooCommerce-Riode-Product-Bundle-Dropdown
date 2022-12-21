<?php
defined('ABSPATH') ?: exit();

if (!trait_exists('Get_Package_Price')) :

    trait Get_Package_Price {

        /**
         * Calc and return package price via AJAX
         *
         * @return void
         */
        public static function bd_get_price_package() {

            // bail if not valid request
            if (!(isset($_REQUEST['action']) || 'bd_get_price_package' != $_GET['action'])) :
                return;
            endif;

            // setup default array
            $return = array(
                'status' => false,
                'html'   => 'no package data!!!'
            );

            // retrieve discount and product ids
            $arr_discount    = $_GET['discount'];
            $arr_product_ids = $_GET['product_ids'];

            // calc and return pricing
            if (!empty($arr_product_ids) && !empty($arr_discount['type']) && isset($arr_discount['qty']) && isset($arr_discount['value'])) {

                // get total price
                $loop_prod   = array();
                $total_price = 0;
                $old_price   = 0;

                // loop
                foreach ($arr_product_ids as $key => $prod_id) {

                    // get product data
                    if ($loop_prod[$prod_id]) {
                        $product = $loop_prod[$prod_id];
                    } else {
                        $product = wc_get_product($prod_id);
                        $loop_prod[$prod_id] = $product;
                    }

                    $total_price += $product->get_regular_price();
                    $old_price += $product->get_regular_price();
                }

                // get discount
                if ($arr_discount['type'] == 'percentage') {
                    $total_price -= ($total_price * $arr_discount['value']) / 100;
                } elseif ($arr_discount['type'] == 'free' && in_array($arr_discount['value'], $arr_product_ids)) {
                    $free_price = wc_get_product($arr_discount['value'])->get_regular_price();
                    $total_price -= $free_price;
                }

                // setup return array
                $return = array(
                    'status'           => true,
                    'total_price'      => $total_price,
                    'total_price_html' => wc_price($total_price),
                    'old_price'        => $old_price,
                    'old_price_html'   => wc_price($old_price),
                    'each_price'       => ($total_price / count($arr_product_ids)),
                    'each_price_html'  => wc_price(($total_price / count($arr_product_ids)))
                );
            }

            // return and bail
            wp_send_json($return);
            wp_die();
        }
    }

endif;
