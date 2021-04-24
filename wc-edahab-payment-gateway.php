<?php
/**
 * Plugin Name:     Woo Edahab Payment Gateway
 * Plugin URI:      https://codeware.io
 * Description:    	Edahab payment gateway for WordPress
 * Author:          Sisir K. Adhikari
 * Author URI:      https://codeware.io
 * Text Domain:     wcepg
 * Requires PHP: 5.4
 * Requires at least: 5.0
 * Domain Path:     /languages
 * Version:         1.0.0
 *
 * @package         WCEPG
 */

use WCEPG\Plugin;

// defined required constants
define( 'WCEPG_URL', plugins_url( '', __FILE__ ) );
define( 'WCEPG_VERSION', '1.0.0');

// Make sure WooCommerce is active
global $wc_activated;
$wc_activated = in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/lib/functions.php';

// init plugin
Plugin::init();
