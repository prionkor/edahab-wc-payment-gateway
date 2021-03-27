<?php 

namespace PLUGIN_NAMESPACE;

class Plugin{
	public static function init(){
		Ajax::init();

		add_action( 'wp_enqueue_scripts', [ 'PIC_MB\Enqueue', 'init' ] );
		add_action( 'wp_loaded', [ 'PIC_MB\Shortcode', 'init' ] );
	}
}
