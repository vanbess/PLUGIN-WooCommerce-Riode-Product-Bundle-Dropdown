<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!class_exists('BDShortCode')) {


    /**
     * Function that devs can use to check if a page includes the OPC shortcode
     *
     * @since 1.1
     */
    // function is_bd_checkout($post_id = null) {

    //     // If no post_id specified try getting the post_id
    //     if (empty($post_id)) {
    //         global $post;

    //         if (is_object($post)) {
    //             $post_id = $post->ID;
    //         } else {
    //             // Try to get the post ID from the URL in case this function is called before init
    //             $schema = is_ssl() ? 'https://' : 'http://';
    //             $url = explode('?', $schema . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    //             $post_id = url_to_postid($url[0]);
    //         }
    //     }

    //     // If still no post_id return straight away
    //     if (empty($post_id) || is_admin()) {

    //         $is_opc = false;
    //     } else {

    //         if (0 == BDShortCode::$shortcode_page_id) {
    //             $post_to_check = !empty($post) ? $post : get_post($post_id);
    //             BDShortCode::check_for_shortcode($post_to_check);
    //         }

    //         // Compare IDs
    //         if ($post_id == BDShortCode::$shortcode_page_id || ('yes' == get_post_meta($post_id, '_wcopc', true))) {
    //             $is_opc = true;
    //         } else {
    //             $is_opc = false;
    //         }
    //     }

    //     return apply_filters('is_bd_checkout', $is_opc);
    // }


    class BDShortCode {

        private static $initiated = false;

        private static $nonce_action = 'bundle_dropdown';
        static $shortcode_page_id = 0;
        static $add_scripts = false;
        static $guest_checkout_option_changed = false;
        static $plugin_url;
        static $plugin_path;
        static $template_path;

        public static $package_addon_product_ids = '';
        public static $package_default_id = '';
        public static $package_theme_color = '';
        public static $package_product_ids = '';
        public static $package_number_item_2 = '';
        public static $addon_product_ids = '';

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

            self::$plugin_path    = untrailingslashit(BD_PLUGIN_DIR);
            self::$template_path  = self::$plugin_path . '/templates/';

            add_shortcode('bundle_dropdown', array(__CLASS__, 'bundle_dropdown_shortcode'));

            // Filter is_checkout() on OPC posts/pages
            // add_filter('woocommerce_is_checkout', array(__CLASS__, 'is_checkout_filter'));

            // Display order review template even when cart is empty in WC < 2.3
            add_action('wp_ajax_woocommerce_update_order_review', array(__CLASS__, 'short_circuit_ajax_update_order_review'), 9);
            add_action('wp_ajax_nopriv_woocommerce_update_order_review', array(__CLASS__, 'short_circuit_ajax_update_order_review'), 9);

            // Display order review template even when cart is empty in WC 2.3+
            add_action('woocommerce_update_order_review_fragments', array(__CLASS__, 'bd_update_order_review_fragments'), 9);

            // Override the checkout template on OPC pages and Ajax requests to update checkout on OPC pages
            add_filter('wc_get_template', array(__CLASS__, 'override_checkout_template'), 10, 5);

            // Ensure we have a session when loading OPC pages
            add_action('template_redirect', array(__CLASS__, 'maybe_set_session'), 1);
        }

        /**
         * Make sure a session is set whenever loading an OPC page.
         */
        public static function maybe_set_session() {
            if (!WC()->session->has_session()) {
                WC()->session->set_customer_session_cookie(true);
            }
        }

        /**
         * Hook to wc_get_template() and override the checkout template used on OPC pages and when updating the order review fields
         * via WC_Ajax::update_order_review()
         *
         * @return string
         */
        public static function override_checkout_template($located, $template_name, $args, $template_path, $default_path) {
            if ($default_path !== self::$template_path && !self::is_woocommerce_pre('2.3') && self::is_any_form_of_opc_page()) {

                if ('checkout/form-checkout.php' == $template_name) {
                    $located = wc_locate_template('checkout/form-checkout-opc.php', '', self::$template_path);
                }
                if ('checkout/review-order.php' == $template_name) {
                    $located = wc_locate_template('checkout/review-order-opc.php', '', self::$template_path);
                }
            }

            return $located;
        }

        /**
         * Check if the installed version of WooCommerce is older than 2.3.
         *
         * @since 1.2.4
         */
        public static function is_woocommerce_pre($version) {

            if (!defined('WC_VERSION') || version_compare(WC_VERSION, $version, '<')) {
                $woocommerce_is_pre = true;
            } else {
                $woocommerce_is_pre = false;
            }

            return $woocommerce_is_pre;
        }

        /**
         * The master check for an OPC request. Checks everything from page ID to $_POST data for
         * some indication that the current request relates to an Ajax request.
         *
         * @return bool
         */
        public static function is_any_form_of_opc_page() {

            $is_opc = false;

            if (isset($_POST['post_data'])) {

                parse_str($_POST['post_data'], $checkout_post_data);

                if (isset($checkout_post_data['is_opc'])) {
                    $is_opc = true;
                }

                // Modify template when doing ajax and sending an OPC request
            } elseif (check_ajax_referer(self::$nonce_action, 'nonce', false)) {

                $is_opc = true;
            }

            return $is_opc;
        }

        /**
         * Function to check for shortcode
         *
         * @param object $post_to_check
         * @return void
         */
        public static function check_for_shortcode($post_to_check) {
            if (false !== stripos($post_to_check->post_content, '[bundle_dropdown')) {
                self::$add_scripts = true;
                self::$shortcode_page_id = $post_to_check->ID;
                $contains_shortcode = true;
            } else {
                $contains_shortcode = false;
            }

            return $contains_shortcode;
        }

        /**
         * Runs just before @see woocommerce_ajax_update_order_review() and terminates the current request if
         * the cart is empty to prevent WooCommerce printing an error that doesn't apply on one page checkout purchases.
         *
         * @since 1.0
         */
        public static function short_circuit_ajax_update_order_review() {

            if (self::is_woocommerce_pre('2.3') && sizeof(WC()->cart->get_cart()) == 0) {
                if (version_compare(WC_VERSION, '2.2.9', '>=')) {
                    ob_start();
                    do_action('woocommerce_checkout_order_review', true);
                    $woocommerce_checkout_order_review = ob_get_clean();

                    // Get messages if reload checkout is not true
                    $messages = '';

                    if (!isset(WC()->session->reload_checkout)) {

                        ob_start();
                        wc_print_notices();
                        $messages = ob_get_clean();

                        // Wrap messages if not empty
                        if (!empty($messages)) {
                            $messages = '<div class="woocommerce-error-ajax">' . $messages . '</div>';
                        }
                    }

                    // Setup data
                    $data = array(
                        'result'   => empty($messages) ? 'success' : 'failure',
                        'messages' => $messages,
                        'html'     => $woocommerce_checkout_order_review
                    );

                    // Send JSON
                    wp_send_json($data);
                } else {
                    do_action('woocommerce_checkout_order_review', true); // Display review order table
                    die();
                }
            }
        }

        /**
         * Set empty order review and payment fields when updating the order table via Ajax and the cart is empty.
         *
         * WooCommerce 2.3 introduced a new cart fragments system to update the order review and payment fields section
         * on checkout so the method previoulsy used in @see self::short_circuit_ajax_update_order_review() no longer
         * works with 2.3.
         *
         * @param  array
         * @return array
         * @since 1.1.1
         */
        public static function bd_update_order_review_fragments($fragments) {

            // If the cart is empty
            if (self::is_any_form_of_opc_page() && 0 == sizeof(WC()->cart->get_cart())) {

                // Remove the "session has expired" notice
                if (isset($fragments['form.woocommerce-checkout'])) {
                    unset($fragments['form.woocommerce-checkout']);
                }

                $checkout = WC()->checkout();

                // To have control over when the create account fields are displayed - we'll display them all the time and hide/show with js
                if (!is_user_logged_in()) {
                    if (false === $checkout->enable_guest_checkout) {
                        $checkout->enable_guest_checkout = true;
                        self::$guest_checkout_option_changed = true;
                    }
                }

                // Add non-blocked order review fragment
                ob_start();
                woocommerce_order_review();
                $fragments['.woocommerce-checkout-review-order-table'] = ob_get_clean();

                // Reset guest checkout option
                if (true === self::$guest_checkout_option_changed) {
                    $checkout->enable_guest_checkout = false;
                    self::$guest_checkout_option_changed = false;
                }

                // Add non-blocked checkout payment fragement
                ob_start();
                woocommerce_checkout_payment();
                $fragments['.woocommerce-checkout-payment'] = ob_get_clean();
            }

            return $fragments;
        }

        /**
         * Seems to load all JS and CSS 
         *
         * @param array $atts
         * @return void
         */
        public static function package_order_checkout_shortcode($atts) {

            $suffix      = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
            $assets_path = str_replace(array('http:', 'https:'), '', WC()->plugin_url()) . '/assets/';

            wp_enqueue_script('wc-checkout', $assets_path . 'js/frontend/checkout' . $suffix . '.js', array('jquery', 'woocommerce', 'wc-country-select', 'wc-address-i18n'), WC_VERSION, true);
            wp_enqueue_script('wc-credit-card-form');

            wp_enqueue_script('flatsome-woocommerce-floating-labels', get_template_directory_uri() . '/assets/libs/float-labels.min.js', array('flatsome-theme-woocommerce-js'), '3.5', true);
            wp_dequeue_style('selectWoo');
            wp_deregister_style('selectWoo');
            wp_dequeue_script('selectWoo');
            wp_deregister_script('selectWoo');

            ob_start();

            require_once BD_PLUGIN_DIR . 'view/bundle_dropdown_package_view.php';
            $content = ob_get_clean();

            return $content;
        }

        /**
         * Apparently loads correct shortcode based on supplied template number
         *
         * @param [type] $atts
         * @return void
         */
        public static function bundle_dropdown_shortcode($atts) {

            $options = shortcode_atts(array(
                'style'             => 'A',
                'theme_color'       => 'blue',
                'ids'               => '0',
                'progress_bar'      => false,
                'default_id'        => '',
                'addon_product_ids' => '',
                'addon_default'     => '',
                'title'             => 'Checkout',
                'buy_now'           => false,
            ), $atts);

            // load progress bar
            if ($options['progress_bar']) {

                $custom_logo_id = get_theme_mod('custom_logo');
                $image_logo = wp_get_attachment_image_src($custom_logo_id, 'full');

                include(BD_PLUGIN_DIR . 'view/includes/progress_bar.php');
            }

            // get default package id
            self::$package_default_id = $options['default_id'];

            // get/set theme color
            self::$package_theme_color = $options['theme_color'];

            if (!in_array($options['theme_color'], array('blue', 'monotone', 'pink'))) {
                self::$package_theme_color = 'blue';
            }

            // get data bundle selection
            $bundle_ids = explode(',', $options['ids']);
            $bundle_ids = array_reverse($bundle_ids);

            // holds product ids
            $prod_ids = [];

            // loop
            foreach ($bundle_ids as $key => $bundle_id) {

                // get correct bundle ID for current language if it exists
                if (function_exists('pll_get_post')) {
                    $bundle_id = pll_get_post($bundle_id, pll_current_language()) ?: $bundle_id;
                }

                // retrieve discount data
                $discount_data = get_post_meta($bundle_id, 'product_discount', TRUE);

                // if discount data is not array, we might have to decode
                if (!is_array($discount_data)) {
                    $discount_data = json_decode($discount_data, true);
                }

                if (isset($discount_data['selValue'])) {

                    $prod_ids[$key]['type']                  = $discount_data['selValue'];
                    $prod_ids[$key]['bun_id']                = $bundle_id;
                    $prod_ids[$key]['description']           = $discount_data['description'];
                    $prod_ids[$key]['image_package_desktop'] = isset($discount_data['image_package_desktop']) ? $discount_data['image_package_desktop'] : "";
                    $prod_ids[$key]['image_package_mobile']  = isset($discount_data['image_package_mobile']) ? $discount_data['image_package_mobile'] : "";
                    $prod_ids[$key]['feature_description']   = $discount_data['feature_description'];
                    $prod_ids[$key]['label_item']            = $discount_data['label_item'];
                    $prod_ids[$key]['discount_percentage']   = (isset($discount_data['discount_percentage']) && is_numeric($discount_data['discount_percentage'])) ? $discount_data['discount_percentage'] : 0;
                    $prod_ids[$key]['sell_out_risk']         = isset($discount_data['sell_out_risk']) ? $discount_data['sell_out_risk'] : "";
                    $prod_ids[$key]['popularity']            = isset($discount_data['popularity']) ? $discount_data['popularity'] : "";
                    $prod_ids[$key]['free_shipping']         = isset($discount_data['free_shipping']) ? $discount_data['free_shipping'] : "";
                    $prod_ids[$key]['show_discount_label']   = isset($discount_data['show_discount_label']) ? $discount_data['show_discount_label'] : "";

                    // free type
                    if ($discount_data['selValue'] == 'free') :
                        $prod_ids[$key]['product_name']  = isset($discount_data['product_name']) ? $discount_data['product_name'] : '';
                        $prod_ids[$key]['title_package'] = isset($discount_data['title_package']) ? $discount_data['title_package'] : "";
                        $prod_ids[$key]['id']            = str_replace(' ', '', $discount_data['selValue_free']['post']['id']);
                        $prod_ids[$key]['qty']           = $discount_data['selValue_free']['quantity'];
                        $prod_ids[$key]['id_free']       = $discount_data['selValue_free_prod']['post']['id'];
                        $prod_ids[$key]['qty_free']      = $discount_data['selValue_free_prod']['quantity'];
                        $prod_ids[$key]['custom_price']  = isset($discount_data['custom_price']) ? $discount_data['custom_price'] : '';
                    endif;

                    // off type
                    if ($discount_data['selValue'] == 'off') :
                        $prod_ids[$key]['product_name']  = isset($discount_data['product_name']) ? $discount_data['product_name'] : '';
                        $prod_ids[$key]['title_package'] = isset($discount_data['title_package']) ? $discount_data['title_package'] : "";
                        $prod_ids[$key]['id']            = str_replace(' ', '', $discount_data['selValue_off']['post']['id']);
                        $prod_ids[$key]['qty']           = $discount_data['selValue_off']['quantity'];
                        $prod_ids[$key]['coupon']         = $discount_data['selValue_off']['coupon'];
                        $prod_ids[$key]['custom_price']  = isset($discount_data['custom_price']) ? $discount_data['custom_price'] : '';
                    endif;

                    // bun type
                    if ($discount_data['selValue'] == 'bun') :

                        // get current WC currency and set default rate
                        $curr      = get_woocommerce_currency();
                        $curr_rate = 1;

                        // change $curr_rate if currency converter plugin is installed
                        if (function_exists('alg_wc_cs_get_currency_exchange_rate')) :
                            $curr_rate = alg_wc_cs_get_currency_exchange_rate($curr);
                        endif;

                        // continue setup
                        $prod_ids[$key]['title_header']  = isset($discount_data['title_header']) ? $discount_data['title_header'] : "";
                        $prod_ids[$key]['title_package'] = isset($discount_data['title_package_bundle']) ? $discount_data['title_package_bundle'] : "";
                        $prod_ids[$key]['total_price']   = is_numeric($discount_data['selValue_bun']['price_currency'][$curr]) ? $discount_data['selValue_bun']['price_currency'][$curr] : (current($discount_data['selValue_bun']['price_currency']) > 0 ? current($discount_data['selValue_bun']['price_currency']) * $curr_rate : false);

                        // retrieve bundle id => bundle qty combinations
                        foreach ($discount_data['selValue_bun']['post'] as $i => $bun) :
                            $prod_ids[$key]['prod'][$i]['id'] = $bun['id'];
                            $prod_ids[$key]['prod'][$i]['qty'] = $bun['quantity'];
                        endforeach;

                    endif;
                }
            }

            // set value of $package_product_ids === $prod_ids
            self::$package_product_ids = $prod_ids;

            // add product to cart when cart empty (add to cart here???)
            if (WC()->cart->get_cart_contents_count() === 0) :

                // if WC does not have session, set customer cookie
                if (!WC()->session->has_session()) :
                    WC()->session->set_customer_session_cookie(true);
                endif;

                // add to cart
                WC()->cart->add_to_cart($prod_ids[0]['prod'][0]['id'], 1, 0, [], ['bundle_dropdown' => 'true']);

            endif;

            // load correct shortcode based on style, else load default style A
            switch ($options['style']):
                case 'A':
                    $result = self::bundle_dropdown_shortcode_style_A();
                    break;
                case 'B':
                    $result = self::bundle_dropdown_shortcode_style_B();
                    break;
                default:
                    $result = self::bundle_dropdown_shortcode_style_A();
                    break;
            endswitch;

            return $result;
        }

        /**
         * Renders shortcode style A with all required scripts/html/etc
         *
         * @return void
         */
        public static function bundle_dropdown_shortcode_style_A() {

            $suffix      = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
            $assets_path = str_replace(array('http:', 'https:'), '', WC()->plugin_url()) . '/assets/';

            wp_enqueue_script('wc-checkout', $assets_path . 'js/frontend/checkout' . $suffix . '.js', array('jquery', 'woocommerce', 'wc-country-select', 'wc-address-i18n'), WC_VERSION, true);
            wp_enqueue_script('bundle_dropdown_js', BD_PLUGIN_URL . 'resources/js/bundle_dropdown.js', array(), time(), true);
    
            ob_start();
            require_once BD_PLUGIN_DIR . 'view/bundle_dropdown_view.php';
            $content = ob_get_clean();
    
            return $content;
        }

        /**
         * Renders shortcode style B with all required scripts/html/etc
         *
         * @return void
         */
        public static function bundle_dropdown_shortcode_style_B() {

            $suffix      = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
            $assets_path = str_replace(array('http:', 'https:'), '', WC()->plugin_url()) . '/assets/';

            wp_enqueue_script('wc-checkout', $assets_path . 'js/frontend/checkout' . $suffix . '.js', array('jquery', 'woocommerce', 'wc-country-select', 'wc-address-i18n'), WC_VERSION, true);
            wp_enqueue_style('bundle_dropdown_B', BD_PLUGIN_URL . 'resources/style/shortcode_style_B/front_style_B.css', array(), time(), 'all');
            wp_enqueue_script('bundle_dropdown_B', BD_PLUGIN_URL . 'resources/js/shortcode_style_B/bundle_dropdown_B.js', array(), time(), true);
            wp_enqueue_script('bundle_dropdown_js', BD_PLUGIN_URL . 'resources/js/bundle_dropdown.js', array(), time(), true);

            ob_start();
            require_once BD_PLUGIN_DIR . 'view/shortcode_style_B/bundle_dropdown_view_style_B.php';
            $content = ob_get_clean();

            return $content;
        }
    }



    // hook action shortcode class
}
add_action('init', array('BDShortCode', 'init'));
