<?php
defined('ABSPATH') ?: exit();

if (!trait_exists('Apply_Regular_Price_Cart')) :

    trait Apply_Regular_Price_Cart {

        /**
         * Sets regular price for all items in the cart
         *
         * @param object $cart
         * @return void
         */
        public static function apply_regular_price_cart($cart) {

            if (is_admin() && !defined('DOING_AJAX')) :
                return;
            endif;

            foreach ($cart->get_cart() as $cart_item) :

                if (isset($cart_item['bundle_dropdown'])) :

                    $product = wc_get_product($cart_item['product_id']);

                    // variable prods
                    if (isset($cart_item['variation_id']) && $product->is_type('variable')) :

                        $vars = $product->get_available_variations();

                        foreach ($vars as $var_data) :
                            if ($var_data['variation_id'] == $cart_item['variation_id']) :
                                $cart_item['data']->set_price($var_data['display_regular_price']);
                            endif;
                        endforeach;

                    // simple prods
                    else :
                        $product = wc_get_product($cart_item['product_id']);
                        $cart_item['data']->set_price($product->get_regular_price());
                    endif;

                endif;
            endforeach;
        }
    }

endif;
