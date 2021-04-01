<?php 

namespace WCEPG;

class Plugin{
	public static function init(){
		Ajax::init();
		add_filter( 'woocommerce_payment_gateways', [ __CLASS__, 'add_to_gateways' ] );
		add_action( 'wp_enqueue_scripts', [ __NAMESPACE__ . '\Enqueue', 'init' ] );
		// add_action( 'wp_loaded', [ __NAMESPACE__ . '\Shortcode', 'init' ] );
	}

	public static function add_to_gateways( $gateways ) {
    $gateways[] = __NAMESPACE__ . '\Edahab_Gateway';
    return $gateways;
	}
}
