<?php 

namespace PLUGIN_NAMESPACE;

class Shortcode{
	public static function init() {
		add_shortcode('plugin_shortcode', [ __CLASS__, 'html' ] );
	}

	public static function html() {
		return '<h1>SHORTCODE</h1>';
	}
}
