<?php
defined('ABSPATH') ?: exit();

if (!trait_exists('Return_WC_Variation_Attrib_Dropdown')) :

    trait Return_WC_Variation_Attrib_Dropdown {

        /**
         * Build and return WC variation attribute options dropdown
         *
         * @param array $args
         * @return void
         */
        public static function return_wc_dropdown_variation_attribute_options($args = array()) {

            $args = wp_parse_args(apply_filters('woocommerce_dropdown_variation_attribute_options_args', $args), array(
                'options'          => false,
                'attribute'        => false,
                'product'          => false,
                'selected'         => false,
                'n_item'           => false,
                'img_variations'   => '',
                'name'             => '',
                'id'               => '',
                'class'            => '',
                'show_option_none' => __('Choose an option', 'woocommerce'),
            ));

            $options               = $args['options'];
            $product               = $args['product'];
            $attribute             = $args['attribute'];
            $name                  = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title($attribute);
            $id                    = $args['id'] ? $args['id'] : sanitize_title($attribute);
            $class                 = $args['class'];
            $show_option_none      = $args['show_option_none'] ? true : false;
            $show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : __('Choose an option', 'woocommerce'); // We'll do our best to hide the placeholder, but we'll need to show something when resetting options.

            if (empty($options) && !empty($product) && !empty($attribute)) {
                $attributes = $product->get_variation_attributes();
                $options    = $attributes[$attribute];
            }

            $html  = '<select class="' . esc_attr($class) . ' bd_product_attribute sel_product_' . esc_attr($id) . '" name="i_variation_' . esc_attr($name) . '" data-attribute_name="attribute_' . esc_attr(sanitize_title($attribute)) . '" data-item="' . $args['n_item'] . '">';

            if (!empty($options)) {
                if ($product && taxonomy_exists($attribute)) {
                    // Get terms if this is a taxonomy - ordered. We need the names too.
                    $terms = wc_get_product_terms($product->get_id(), $attribute, array(
                        'fields' => 'all',
                    ));

                    foreach ($terms as $i => $term) {
                        if (in_array($term->slug, $options, true)) {
                            $html .= '<option data-item="' . $args['n_item'] . '" data-img="' . $args['img_variations'][$i] . '" value="' . esc_attr($term->slug) . '" ' . selected(sanitize_title($args['selected']), $term->slug, false) . '>' . esc_html(apply_filters('woocommerce_variation_option_name', $term->name)) . '</option>';
                        }
                    }
                } else {
                    foreach ($options as $option) {
                        // This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
                        $selected = sanitize_title($args['selected']) === $args['selected'] ? selected($args['selected'], sanitize_title($option), false) : selected($args['selected'], $option, false);
                        $html    .= '<option data-item="' . $args['n_item'] . '" value="' . esc_attr($option) . '" ' . $selected . '>' . esc_html(apply_filters('woocommerce_variation_option_name', $option)) . '</option>';
                    }
                }
            }

            $html .= '</select>';

            return apply_filters('woocommerce_dropdown_variation_attribute_options_html', $html, $args); // WPCS: XSS ok.
        }
    }

endif;
