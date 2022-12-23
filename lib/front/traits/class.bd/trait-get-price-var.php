<?php
defined('ABSPATH') ?: exit();

if (!trait_exists('Get_Price_Variations')) :

    trait Get_Price_Variations {

        /**
         * Function to retrieve variation price via AJAX
         *
         * @return void
         */
        public static function bd_get_price_variation_product() {

            // bail if not valid request
            if (!(isset($_REQUEST['action']) || 'bd_get_price_variation_product' != $_GET['action'])) :
                return;
            endif;

            // print_r($_GET);

            // wp_die();

            // setup default response
            $return = array(
                'status' => false,
                'html'   => 'no variation data!!!'
            );

            // retrieve price list and coupon
            $price_list = isset($_GET['price_list']) ? $_GET['price_list'] : null;
            $coupon     = $_GET['coupon'];

            // if $price_list no null
            if (!is_null($price_list)) {

                $total_price = 0;

                foreach ($price_list as $key => $value) {

                    // print_r($value);
                    $total_price += floatval($value['price']);
                }

                // wp_die();
                // calc discounted price
                $discounted_price = $total_price;

                if ($coupon > 0) {
                    $discounted_price = $total_price - ($total_price * $coupon) / 100;
                }

                // data to return
                $return = array(
                    'status'            => true,
                    'total_price'       => $discounted_price,
                    'total_price_html'  => wc_price($discounted_price),
                    'single_price'      => ($discounted_price / count($price_list)),
                    'single_price_html' => wc_price($discounted_price / count($price_list))
                );
            }

            wp_send_json($return);
            wp_die();
        }
    }

endif;
