<?php 

namespace WGJ;

class Plugin{
    public static function init(){
        Shortcode::init();
        Enqueue::init();
        Ajax::init();
    }
}
