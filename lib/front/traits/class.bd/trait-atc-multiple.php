<?php

defined('ABSPATH') ?: exit();

if (!trait_exists('Add_To_Cart_Multiple')) :

    trait Add_To_Cart_Multiple {

        /**
         * Add product sto cart AJAX action
         *
         * @return void
         * @uses bd_add_to_cart
         */
        public static function bd_add_to_cart_multiple() {

            // bail if not a valid request
            if (!(isset($_REQUEST['action']) || 'bd_add_to_cart_multiple' != $_POST['action'])){
                return;
            }

            // setup default return data array
            $return = array(
                'status' => false,
                'html'   => '<h3> There is no any Product request!!! </h3>'
            );
            
            // retrieve subbed products
            $bd_products = $_POST['add_to_cart_items_data']['products'];

            // if subbed products, add to cart
            if ($bd_products) {

                if (!session_id()) {
                    session_start();
                }

                // add bd_bundle products to cart
                $bd_bundle_var_data = ['bundle_dropdown' => 'true', 'bundle_id' => $_POST['bundle_id']];
                self::bd_add_to_cart($bd_products, $bd_bundle_var_data);

                $return = array(
                    'status'      => true,
                    'html'        => '<h3>Product added!!! </h3>',
                );
            }

            wp_send_json($return);
            wp_die();
        }

        /**
         * Does the actual adding to cart of products
         *
         * @param array $products
         * @param array $bundle_selection_data
         * @return void
         * @uses bd_find_matching_product_variation_id
         * @todo Need to rework this so that correct discount is added/applied to cart vis a vis free products
         */
        private static function bd_add_to_cart($bd_products, $bd_bundle_var_data) {

            foreach ($bd_products as $product_data):

                $product_id      = $product_data['product_id'];
                $variation_id    = $product_data['variation_id'];
                $variations_vals = $product_data['i_product_attribute'];
                $c_product       = wc_get_product($product_id);

                if ($c_product->is_type('variable')) {

                    if (empty($variations_vals)){
                        $variations_vals = array();
                    }

                    $product = new WC_Product_Variable($product_id);

                    if ($product_data['qty'] > 1) {
                        setcookie("woocommerce_want_multiple", "yes", time() +  DAY_IN_SECONDS, "/", COOKIE_DOMAIN);
                    }

                    $variations = $product->get_available_variations();
                    foreach ($variations as $variation) {
                        if (!array_diff($variations_vals, $variation['attributes'])) {
                            $variation_id = $variation['variation_id'];
                            $variations_vals = $variation['attributes'];
                        }
                    }
                }

                // add variation id
                if ($product_data['variation_id']) {
                    $bd_bundle_var_data['bd_prod_post_id'] = $product_data['variation_id'];
                } else {
                    $bd_bundle_var_data['bd_prod_post_id'] = $product_data['product_id'];
                }

                $variation_val = ($variations_vals) ? $variations_vals : '';

                if (!WC()->session->has_session()) {
                    WC()->session->set_customer_session_cookie(true);
                }

                // check linked variations
                if (isset($product_data['linked_product']['id']) && isset($product_data['linked_product']['attributes'])) {
                    if ($product_data['linked_product']['id'] != $product_id) {

                        $p_links_attrs = array_merge($variations_vals, $product_data['linked_product']['attributes']);

                        $linked_var_id = self::bd_find_matching_product_variation_id($product_data['linked_product']['id'], $p_links_attrs);
                        if ($linked_var_id) {
                            $product_id = $product_data['linked_product']['id'];
                            $variation_id = $linked_var_id;
                            $bd_bundle_var_data['linked_product'] = $product_id;
                        }
                    }
                }

                WC()->cart->add_to_cart($product_id, intval($product_data['qty']), $variation_id, $variation_val, $bd_bundle_var_data);

                unset($variation_attributes);
            endforeach;
        }

        /**
         * Finds and returns matching product variation id/attributes
         *
         * @param int $product_id
         * @param array $attributes
         * @return void
         */
        public static function bd_find_matching_product_variation_id($product_id, $attributes) {
            if (class_exists('WC_Product_Data_Store_CPT')) {
                return (new \WC_Product_Data_Store_CPT())->find_matching_product_variation(
                    new \WC_Product($product_id),
                    $attributes
                );
            }
            return false;
        }
    }

endif;
