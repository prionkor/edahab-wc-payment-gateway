<?php
/**
 * Plugin Name:     Codeware Plugin Scaffold
 * Plugin URI:      https://codeware.io
 * Description:    	
 * Author:          Codeware Team
 * Author URI:      https://codeware.io
 * Text Domain:     codeware-plugin-scaffold
 * Requires PHP: 5.4
 * Requires at least: 5.0
 * Domain Path:     /languages
 * Version:         1.0.0
 *
 * @package         CODEWARE
 */

use PLUGIN_NAMESPACE\Plugin;

// defined required constants
define( 'PLUGIN_NAMESPACE_URL', plugins_url( '', __FILE__ ) );
define( 'PLUGIN_NAMESPACE_VERSION', '1.0.0');

require_once __DIR__ . '/vendor/autoload.php';

// init plugin
Plugin::init();
