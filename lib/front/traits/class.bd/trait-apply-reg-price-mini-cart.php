<?php
defined('ABSPATH') ?: exit();

if (!trait_exists('Apply_Regular_Price_Mini_Cart')) :

    trait Apply_Regular_Price_Mini_Cart {

        /**
         * Applies bundle item regular pricing to mini cart
         *
         * @param string $price_html
         * @param array $cart_item
         * @param string $cart_item_key
         * @return void
         */
        public static function apply_regular_price_mini_cart($price_html, $cart_item, $cart_item_key) {

            if (isset($cart_item['bundle_dropdown'])) :

                $product = wc_get_product($cart_item['product_id']);

                // variable prods
                if (isset($cart_item['variation_id']) && $product->is_type('variable')) :

                    $vars = $product->get_available_variations();

                    foreach ($vars as $var_data) :
                        if ($var_data['variation_id'] == $cart_item['variation_id']) :
                            $cart_item['data']->set_price($var_data['display_regular_price']);
                            return wc_price($var_data['display_regular_price']);
                        endif;
                    endforeach;

                // simple prods
                else :
                    $product = wc_get_product($cart_item['product_id']);
                    $cart_item['data']->set_price($product->get_regular_price());
                    return wc_price($product->get_regular_price());
                endif;

            endif;

            // default
            return $price_html;
        }
    }

endif;
