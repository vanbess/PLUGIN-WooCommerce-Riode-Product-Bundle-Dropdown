<?php
defined('ABSPATH') ?: exit();

if (!trait_exists('Return_Linked_Product_Dropdown')) :

    trait Return_Linked_Product_Dropdown {

        /**
         * Build and returns linked products variations dropdown HTML
         *
         * @param array $args
         * @param array $var_data
         * @return void
         */
        public static function return_bd_linked_variations_dropdown($args = [], &$var_data = []) {

            $html = '';

            if (!empty($args)) {

                $product_id = $args['product_id'];
                $class      = isset($args['class']) ? $args['class'] : '';

                $all_gnrl_plgfyqdp_set = get_option('plgfyqdp_save_gnrl_settingsplv');

                if ('' == $all_gnrl_plgfyqdp_set) {

                    $all_gnrl_plgfyqdp_set = array(
                        'plgfqdp_ishyper' => 'true',
                        'brdractive'      => '#621bff',
                        'brdrinactv'      => '#dddddd',
                        'pdngclr'         => '#ffffff',
                        'bckgrdclr'       => '#f1f1f1',
                        'txtclr'          => '#000000'
                    );
                }

                $plgfqdp_ishyper = $all_gnrl_plgfyqdp_set['plgfqdp_ishyper'];

                if ('true' == $plgfqdp_ishyper) {
                    $plgfqdp_ishyper = ' target="_blank" ';
                } else {
                    $plgfqdp_ishyper = '';
                }

                $current_id         = $product_id;
                $all                = array();
                $sub                = array();
                $thsruleisapplied   = '';
                $plgfyqdp_all_rules = get_option('plgfymao_all_rulesplgfyplv');

                if ('' == $plgfyqdp_all_rules) {
                    $plgfyqdp_all_rules = array();
                }

                $all_attributes   = wc_get_attribute_taxonomies();

                foreach ($plgfyqdp_all_rules as $key => $value) {

                    $to_be_sent = $plgfyqdp_all_rules[$key];
                    $previous_attrs = [];

                    if (isset($to_be_sent['selected_checks_attr'])) {

                        foreach ($to_be_sent['selected_checks_attr'] as $keyi => $valuei) {
                            $attribute_id = $valuei[4];
                            $new_record_against_attr_id = $all_attributes['id:' . $attribute_id];
                            $plgfyqdp_all_rules[$key]['selected_checks_attr'][$keyi][0] = $new_record_against_attr_id->attribute_name;
                            $plgfyqdp_all_rules[$key]['selected_checks_attr'][$keyi][3] = $new_record_against_attr_id->attribute_label;
                            $previous_attrs[] = $valuei[4];
                        }
                    }

                    foreach ($all_attributes as $key11 => $value11) {
                        if (!in_array($value11->attribute_id, $previous_attrs)) {
                            $new_attribute = array($value11->attribute_name, 'false', 'false', $value11->attribute_label, $value11->attribute_id);
                            $plgfyqdp_all_rules[$key]['selected_checks_attr'][] = $new_attribute;
                        }
                    }
                }

                update_option('plgfymao_all_rulesplgfyplv', $plgfyqdp_all_rules);

                $plgfyqdp_all_rules = get_option('plgfymao_all_rulesplgfyplv');

                if ('' == $plgfyqdp_all_rules) {
                    $plgfyqdp_all_rules = array();
                }

                $all                 = [];
                $attrkeytobusedlater = '';

                foreach ($plgfyqdp_all_rules as $key0 => $value0) {
                    if ('true' == $value0['plgfyplv_activate_rule']) {


                        if ('Products' == $value0['applied_on']) {

                            if (in_array($current_id, $value0['apllied_on_ids'])) {

                                $linked              = [];
                                $breakitbab          = true;
                                $attrkeytobusedlater = $key0;
                                $linked              = $value0['apllied_on_ids'];
                            }

                        } else {

                            $ppridss = [];

                            foreach ($value0['apllied_on_ids'] as $key0po => $value0po) {

                                $all_ids = get_posts(array(
                                    'post_type'   => array('product', 'product_variation'),
                                    'numberposts' => -1,
                                    'post_status' => 'publish',
                                    'fields'      => 'ids',
                                    'tax_query'   => array(
                                        array(
                                            'taxonomy' => 'product_cat',
                                            'terms'    => $value0po,
                                            'operator' => 'IN',
                                        )
                                    )
                                ));

                                foreach ($all_ids as $idalp => $valalp) {
                                    $ppridss[] = $valalp;
                                }

                                if (in_array($current_id, $all_ids)) {
                                    $linked = [];
                                    foreach ($ppridss as $keypprr => $valuepprr) {
                                        $linked[] = $valuepprr;
                                    }
                                    $breakitbab = true;
                                    $attrkeytobusedlater = $key0;
                                }
                            }
                        }

                        $sub = [];
                        $atr = [];

                        foreach ($value0['selected_checks_attr'] as $key1 => $value1) {
                            if ('true' == $value1[1]) {
                                $atr[] = $value1[0];
                            }
                        }

                        if ($breakitbab) {

                            $sub[] = $linked;
                            $sub[] = $atr;
                            $all[] = $sub;
                            $thsruleisapplied = $key0;

                            break;
                        }
                    }
                }

                if ('-1' > $thsruleisapplied) {
                    return;
                }

                $khasertoalip = array();

                foreach ($all[0][1] as $key => $attrslug) {

                    $uppersub = [];
                    
                    if (count($all[0][0]) > 0) {

                        $al_grouped_p_idsyyuiop = $all[0][0];
                        $temp_val_of_0 = $al_grouped_p_idsyyuiop[0];
                        $al_grouped_p_idsyyuiop[0] = $current_id;
                        $al_grouped_p_idsyyuiop[] = $temp_val_of_0;

                        $al_grouped_p_idsyyuiop = array_unique($al_grouped_p_idsyyuiop);
                    }


                    foreach ($al_grouped_p_idsyyuiop as $keyinner => $applied_on_id_pid) {
                        $product = wc_get_product($applied_on_id_pid);
                        $innersub = [];
                        $attribues = $product->get_attribute($attrslug);

                        if ('' != $attribues) {

                            $attribues = explode(',', $attribues);
                            $attribues = $attribues[0];
                            $innersub[] = $attribues;
                            $innersub[] = $applied_on_id_pid;
                            $uppersub[] = $innersub;
                        }
                    }

                    $khasertoalip[$attrslug] = $uppersub;
                }

                foreach ($khasertoalip as $attr_slug => $all_linked_products) {

                    $istrue = false;
                    $labelforslugattr = '';
                    foreach ($plgfyqdp_all_rules[$thsruleisapplied]['selected_checks_attr'] as $lostkey => $lost_val) {
                        if ($attr_slug == $lost_val[0]) {
                            $istrue = $lost_val[2];
                            $labelforslugattr = $lost_val[3];
                            break;
                        }
                    }
?>

                    <div class="variation_item">

                        <p class="variation_name"><?php echo __('Color', 'woocommerce') ?>: </p>

                        <div class="attribute-swatch" attribute-index="">
                            <div class="swatchinput">
                                <?php

                                $unique_attrs = [];
                                foreach ($all_linked_products as $keyplugify => $valueplugify) {

                                    $is_out_of_stock = 'false';
                                    $_backorders = get_post_meta($valueplugify[1], '_backorders', true);
                                    $stock_status = get_post_meta($valueplugify[1], '_stock_status', true);

                                    // Image swatch for linked products
                                    $product = wc_get_product($valueplugify[1]);
                                    if (!isset($var_data[$valueplugify[1]]) && $product->is_type('variable')) {
                                        $var_arr = [];
                                        foreach ($product->get_available_variations() as $key => $value) {
                                            array_push($var_arr, [
                                                'id'         => $value['variation_id'],
                                                'price'      => $prod['custom_price'][$value['variation_id']][$current_curr],
                                                'attributes' => $value['attributes'],
                                                'image'      => $value['image']['url']
                                            ]);
                                        }
                                        $var_data[$valueplugify[1]] = $var_arr;
                                    }

                                    if (class_exists('WooCommerce') && defined('RIODE_VERSION')) {

                                        $term_ids = wc_get_product_terms($valueplugify[1], 'pa_color', array('fields' => 'ids'));

                                        foreach ($term_ids as $term_id) {
                                            $attr_value = get_term_meta($term_id, 'attr_color', true);
                                            $attr_img   = get_term_meta($term_id, 'attr_image', true);
                                        }
                                    }
                                    if ('instock' == $stock_status) {

                                        $stock_count   = get_post_meta($valueplugify[1], '_stock', true);
                                        $_manage_stock = get_post_meta($valueplugify[1], '_manage_stock', true);
                                        $_backorders   = get_post_meta($valueplugify[1], '_backorders', true);

                                        if ('no' != $_manage_stock && 0 >= $stock_count && 'no' == $_backorders) {
                                            $is_out_of_stock = 'true';
                                        }
                                    } else if ('outofstock' == $stock_status && 'no' == $_backorders) {
                                        $is_out_of_stock = 'true';
                                    }

                                    if ('' != $valueplugify[0]) {

                                        if (!in_array($valueplugify[0], $unique_attrs)) {

                                            $unique_attrs[] = $valueplugify[0];
                                            if ($valueplugify[1] == $current_id) {

                                                if ('true' == $istrue) {
                                                    $image = wp_get_attachment_image_src(get_post_thumbnail_id($valueplugify[1]), 'single-post-thumbnail');
                                                    $img_srcy = '';
                                                    if ('' == $image) {
                                                        $image = array();
                                                    }
                                                    if (0 < count($image) && isset($image[0]) && '' != $image[0]) {
                                                        $img_srcy = $image[0];
                                                    } else {

                                                        $img_srcy = plugins_url() . '/products-linked-by-variations-for-woocommerce/Front/Assets/woocommerce-placeholder-plugify.png';
                                                    }
                                ?>
                                                    <div class="imgclasssmallactive tooltipplugify" style="width:auto;border-radius: 2px;padding: 3px;border: 1px solid green;">
                                                        <img class="child_class_plugify" style="height: 40px;text-align: center;" src="<?php echo filter_var($img_srcy); ?>">
                                                        <div class="tooltiptextplugify">
                                                            <?php echo filter_var($valueplugify[0]); ?>
                                                        </div>
                                                    </div>
                                                    <?php

                                                } else {
                                                    if ($attr_value) {
                                                    ?>

                                                        <label selectid="" class="wcvaswatchlabel wcvaround linked_product selected" data-attribute_name="attribute_pa_color" data-option="<?php echo filter_var($valueplugify[0]); ?>" data-linked_id="<?php echo $valueplugify[1] ?>" style="background-color:<?php echo sanitize_hex_color($attr_value); ?>; width:32px; height:32px; "></label>
                                                        <?php
                                                    } else {
                                                        if ($attr_img) {
                                                            $attr_image = '';
                                                            $attr_image = wp_get_attachment_image_src($attr_img, array(32, 32));
                                                            if ($attr_image) {
                                                                $attr_image = $attr_image[0];
                                                            }

                                                            if (!$attr_image) {
                                                                $attr_image = wc_placeholder_img_src(array(32, 32));
                                                            }
                                                        ?>
                                                            <div class="imgclasssmallactive tooltipplugify" style="margin: 0 5px;width:35px;height: 35px;border-radius: 50%;overflow: hidden;border: 1px solid green;">
                                                                <img class="child_class_plugify" style="height: 35px;text-align: center;" src="<?php echo filter_var($attr_image); ?>">
                                                                <div class="tooltiptextplugify">
                                                                    <?php echo filter_var($valueplugify[0]); ?>
                                                                </div>
                                                            </div>
                                                        <?php
                                                        } else {
                                                        ?>
                                                            <div class="imgclasssmallactive" style="width:auto; border-radius: 2px;padding: 3px;border: 1px solid green;">
                                                                <div class="child_class_plugify" style="text-align: center;padding: 2px 15px;"><?php echo filter_var($valueplugify[0]); ?></div>
                                                            </div>
                                                    <?php
                                                        }
                                                    }
                                                }
                                            } else {

                                                $style_cursor    = '';
                                                $htmllpluigg     = '';
                                                $plgfqdp_ishyper = $all_gnrl_plgfyqdp_set['plgfqdp_ishyper'];

                                                if ('true' == $plgfqdp_ishyper) {
                                                    $plgfqdp_ishyper = ' target="_blank" ';
                                                } else {
                                                    $plgfqdp_ishyper = '';
                                                }
                                                if ('true' == $is_out_of_stock) {
                                                    $style_cursor    = ' cursor:not-allowed; ';
                                                    $htmllpluigg     = ' href="javascript:void(0)" ';
                                                    $plgfqdp_ishyper = '  ';
                                                }

                                                if ('true' == $istrue) {
                                                    $image = wp_get_attachment_image_src(get_post_thumbnail_id($valueplugify[1]), 'single-post-thumbnail');
                                                    $img_srcy = '';
                                                    if ('' == $image) {
                                                        $image = array();
                                                    }
                                                    if (0 < count($image) && isset($image[0]) && '' != $image[0]) {
                                                        $img_srcy = $image[0];
                                                    } else {

                                                        $img_srcy = plugins_url() . '/products-linked-by-variations-for-woocommerce/Front/Assets/woocommerce-placeholder-plugify.png';
                                                    }

                                                    ?>
                                                    <div class="imgclasssmall tooltipplugify <?php echo (empty($style_cursor) ? '' : 'disabled') ?>" style="width:auto;border-radius: 2px;padding: 3px;border: 1px solid #ddd; <?php echo filter_var($style_cursor); ?>">
                                                        <a style="<?php echo filter_var($style_cursor); ?>" <?php echo filter_var($htmllpluigg); ?> <?php echo filter_var($plgfqdp_ishyper); ?> class="aclass-clr" href="<?php echo filter_var(get_permalink($valueplugify[1])); ?>"><img class="child_class_plugify" style="height: 40px;text-align: center;" src="<?php echo filter_var($img_srcy); ?>">
                                                        </a>
                                                        <div class="tooltiptextplugify">
                                                            <?php
                                                            if ('true' == $is_out_of_stock) {
                                                                echo esc_attr_e('Out Of Stock', 'woocommerce');
                                                            } else {
                                                                echo filter_var($valueplugify[0]);
                                                            }
                                                            ?>
                                                        </div>
                                                    </div>

                                                    <?php
                                                } else {
                                                    if ($attr_value) {
                                                    ?>

                                                        <label selectid="" class=" wcvaswatchlabel wcvaround linked_product <?php echo (empty($style_cursor) ? '' : 'disabled') ?>" data-attribute_name="attribute_pa_color" data-option="<?php echo filter_var($valueplugify[0]); ?>" data-linked_id="<?php echo $valueplugify[1] ?>" style="background-color:<?php echo sanitize_hex_color($attr_value); ?>; width:32px; height:32px; <?php echo $style_cursor; ?>"></label>

                                                        <?php
                                                    } else {
                                                        if ($attr_img) {
                                                            $attr_image = '';
                                                            $attr_image = wp_get_attachment_image_src($attr_img, array(32, 32));
                                                            if ($attr_image) {
                                                                $attr_image = $attr_image[0];
                                                            }

                                                            if (!$attr_image) {
                                                                $attr_image = wc_placeholder_img_src(array(32, 32));
                                                            }
                                                        ?>
                                                            <div class="imgclasssmall tooltipplugify <?php echo (empty($style_cursor) ? '' : 'disabled') ?>" style="margin: 0 5px;width:35px;height:35px;border-radius: 50%;overflow: hidden;border: 1px solid #ddd; <?php echo filter_var($style_cursor); ?>">
                                                                <a style="<?php echo filter_var($style_cursor); ?>" <?php echo filter_var($htmllpluigg); ?> <?php echo filter_var($plgfqdp_ishyper); ?> class="aclass-clr" href="<?php echo filter_var(get_permalink($valueplugify[1])); ?>"><img class="child_class_plugify" style="height: 35px;text-align: center;" src="<?php echo filter_var($attr_image); ?>">

                                                                </a>

                                                                <div class="tooltiptextplugify">
                                                                    <?php
                                                                    if ('true' == $is_out_of_stock) {
                                                                        echo esc_attr_e('Out Of Stock', 'woocommerce');
                                                                    } else {
                                                                        echo filter_var($valueplugify[0]);
                                                                    }
                                                                    ?>
                                                                </div>
                                                            </div>

                                                        <?php
                                                        } else {
                                                        ?>
                                                            <div class="imgclasssmall <?php echo (empty($style_cursor) ? '' : 'disabled') ?>" style="width:auto;border-radius: 2px;padding: 3px;border: 1px solid #ddd; background-color: <?php echo sanitize_hex_color($attr_value);
                                                                                                                                                                                                                                            echo filter_var($style_cursor); ?>">
                                                                <a style="<?php echo filter_var($style_cursor); ?>" <?php echo filter_var($htmllpluigg); ?> <?php echo filter_var($plgfqdp_ishyper); ?> class="aclass-clr" href="<?php echo filter_var(get_permalink($valueplugify[1])); ?>">
                                                                    <div class="child_class_plugify" style="text-align: center;padding: 2px 15px;"><?php echo filter_var($valueplugify[0]); ?>
                                                                </a>

                                                            </div>
                            </div>

<?php }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
?>
                        </div>
                    </div>
                    </div>
<?php
                }
            }

            return $html;
        }
    }

endif;
?>