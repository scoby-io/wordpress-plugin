<?php
/*
Plugin Name: Scoby Analytics
Description: Scoby Analytics provides meaningful insights about your websites traffic while protecting your visitors privacy at the same time. Scoby uses no cookies, does not access the end user's device, nor gathers any other personally identifiable information - we only collect anonymous data directly on your web server. Thus Scoby Analytics requires no consent regarding GDPR, ePrivacy, and Schrems II.
Version: 1.3.1-3-2-1-0
Author: scoby UG
Author URI: https://www.scoby.io
Requires PHP:    7.4
*/

defined('ABSPATH') or die('I can only run in Wordpress.');
define('MIN_PHP_VERSION', '7.4');
if(!defined('SCOBY_ANALYTICS_PLUGIN_ROOT')) {
    define('SCOBY_ANALYTICS_PLUGIN_ROOT', untrailingslashit(plugin_dir_path(__FILE__)));
}

require_once SCOBY_ANALYTICS_PLUGIN_ROOT . '/deps/scoper-autoload.php';
require_once SCOBY_ANALYTICS_PLUGIN_ROOT . '/vendor/autoload.php';

require_once SCOBY_ANALYTICS_PLUGIN_ROOT . '/settings.php';
require_once SCOBY_ANALYTICS_PLUGIN_ROOT . '/plugin-update-checker/plugin-update-checker.php';

use ScobyAnalytics\Plugin;
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

if (version_compare(PHP_VERSION, MIN_PHP_VERSION, '<=')) {
    add_action(
        'admin_init',
        static function () {
            deactivate_plugins(plugin_basename(__FILE__));
        }
    );
    add_action(
        'admin_notices',
        static function () {
            echo wp_kses_post(
                sprintf(
                    '<div class="notice notice-error"><p>%s</p></div>',
                    __('"scoby Analytics" requires PHP 7.4 or newer.')
                )
            );
        }
    );

    // Return early to prevent loading the plugin.
    return;
}

$plugin = new Plugin();

register_activation_hook(__FILE__, array($plugin, 'activate'));
register_deactivation_hook(__FILE__, array($plugin, 'deactivate'));

add_action('plugins_loaded', function () use ($plugin) {
    $version = \ScobyAnalytics\Helpers::getVersion();
    if ($version !== get_option('scoby_analytics_version')) {
        $plugin->activate();
        update_option('scoby_analytics_version', $version);
    }

    if(get_transient('scoby_analytics_check_config')) {
        \ScobyAnalytics\Helpers::checkConfig();
        delete_transient('scoby_analytics_check_config');
    }
});

if (!wp_installing()) {
    add_action(
        'plugins_loaded',
        static function () use ($plugin) {
            $plugin->initialize();
        }
    );
}

$options = get_option('scoby_analytics_options');
if (empty($options['jar_id'])) {
    add_action('admin_notices', function () {
        ?>
		<div class="error notice">
			<p><?php _e('Scoby Analytics will only measure your traffic, once you have entered your Jar ID in the <a href="' . admin_url('options-general.php?page=scoby-analytics-plugin') . '">Plugin\'s Settings</a>.', 'my_plugin_textdomain'); ?></p>
		</div>
        <?php
    });
} else {
    add_action('admin_notices', function () {
        $cachePlugin = get_transient('scoby_analytics_flush_cache_notice');
        if(!empty($cachePlugin)) {
            ?>
            <div class="notice-warning notice">
                <p><?php _e('Scoby Analytics has detected you are using the <b>'.$cachePlugin.'</b> Plugin. Please flush your cache to start measuring.', 'my_plugin_textdomain'); ?></p>
            </div>
            <?php
            delete_transient('scoby_analytics_flush_cache_notice');
        }
    });

    add_action('admin_notices', function () {
        $cachePlugin = get_transient('scoby_analytics_use_client_integration');
        if(!empty($cachePlugin)) {
            ?>
            <div class="notice-warning notice">
                <p><?php _e('Scoby Analytics has detected you are using the <b>'.$cachePlugin.'</b> Plugin, but are using our Standard integration. We strongly recommend to <b>switch to Cache-Optimized integration</b> in the  <a href="' . admin_url('options-general.php?page=scoby-analytics-plugin&tab=advanced') . '">Plugin\'s Advanced Settings</a> for optimal results.', 'my_plugin_textdomain'); ?></p>
            </div>
            <?php
            delete_transient('scoby_analytics_use_client_integration');
        }
    });
}




function add_action_links($actions)
{
    $mylinks = array(
        '<a href="' . admin_url('options-general.php?page=scoby-analytics-plugin') . '">Settings</a>',
    );
    $actions = array_merge($mylinks, $actions);
    return $actions;
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'add_action_links');


$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/scobyio/wordpress-plugin/',
    __FILE__,
    'scoby-analytics'
);
$myUpdateChecker->getVcsApi()->enableReleaseAssets();
