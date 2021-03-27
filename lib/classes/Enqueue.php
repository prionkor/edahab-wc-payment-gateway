<?php 

namespace PLUGIN_NAMESPACE;

class Enqueue{
	public static function init(){
		self::register_scripts();
		self::register_styles();
		self::localize();
	}

	public static function register_scripts(){

	}

	public static function register_styles(){

	}

	public static function localize(){

	}

	/**
	 * @param array $handles Array of media handles
	 * @param string $types Media types 'script' or 'style'
	 */
	public static function enqueue_media($handles, $type = 'script') {

		if ($type === 'script') {
			foreach ($handles as $h) {
				wp_enqueue_script($h);
			}
			return;
		}

		// enqueue styles
		foreach ($handles as $h) {
			wp_enqueue_style($h);
		}
	}
}
