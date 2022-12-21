<?php

/**
 * @package bundle-dropdown
 * 
 * Plugin Name: Riode Product Single Bundle Dropdown
 * Plugin URI:
 * Description: Bundle dropdown for WC product single similar to Multi Woo Checkout
 * Author: WC Bessinger
 * Version:    1.0.1
 * Author URI:
 * Text Domain: riode
 * Domain Path: /languages
 */

define('BDVersion', '1.0.1');
define('BD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BD_PROTECTION_H', plugin_basename(__FILE__));
define('BD_NAME', 'bundle-dropdown');
define('BD_PAGE_LINK', 'bundle-dropdown');

// Create admin menu dashboard
function bd_admin_menu() {
    add_menu_page(
        __('Bundle Dropdown', 'bundle-dropdown'),
        __('Bundle Dropdown', 'bundle-dropdown'),
        'read',
        'bundle-dropdown',
        null,
        BD_PLUGIN_URL . 'images/bd_logo.png',
        '55'
    );
}
add_action('admin_menu', 'bd_admin_menu');

// create template onecheckout page
require_once(BD_PLUGIN_DIR . 'lib/front/class-add-template.php');
require_once(BD_PLUGIN_DIR . 'functions.php');

// class handle plugin
require_once(BD_PLUGIN_DIR . 'lib/front/class.bd.php');
add_action('init', array('BD', 'init'));

// bundle selection admin & front
if (is_admin()) {
    require_once(BD_PLUGIN_DIR . 'lib/admin/bundle-selection-admin.php');
} else {
    require_once(BD_PLUGIN_DIR . 'lib/front/class-add-shortcode.php');
}

// add custom logo theme
add_theme_support('custom-logo');