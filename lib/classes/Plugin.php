<?php 

namespace PLUGIN_NAMESPACE;

class Plugin{
    public static function init(){
        Shortcode::init();
        Enqueue::init();
        Ajax::init();
    }
}
