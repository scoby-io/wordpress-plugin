<?php

defined('ABSPATH') or die('I can only run in Wordpress.');
define('MIN_PHP_VERSION', '7.4');
if(!defined('SCOBY_ANALYTICS_PLUGIN_ROOT')) {
    define('SCOBY_ANALYTICS_PLUGIN_ROOT', plugin_dir_path(__FILE__));
}

$libs = require SCOBY_ANALYTICS_PLUGIN_ROOT . 'vendor/autoload.php';