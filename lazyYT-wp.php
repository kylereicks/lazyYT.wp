<?php
/*
Plugin Name: LazyYT.WP
Plugin URI: http://github.com/kylereicks/lazyYT.wp
Description: A WordPress plugin to lazy-load YouTube videos using [LazyYT.js](https://github.com/tylerpearson/lazyYT).
Author: Kyle Reicks
Version: 0.0.0
Author URI: http://github.com/kylereicks/
*/

define('LAZYYT_WP_PATH', plugin_dir_path(__FILE__));
define('LAZYYT_WP_URL', plugins_url('/', __FILE__));
define('LAZYYT_WP_VERSION', '0.0.0');
define('LAZYYT_JS_VERSION', '0.0.0');

require_once(LAZYYT_WP_PATH . 'inc/class-lazyYT-wp.php');

register_deactivation_hook(__FILE__, array('LazyYT_WP', 'deactivate'));

LazyYT_WP::get_instance();
