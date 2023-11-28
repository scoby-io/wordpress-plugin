<?php
/*
Plugin Name: Scoby Analytics
Description: Scoby Analytics provides meaningful insights about your websites traffic while protecting your visitors privacy at the same time. Scoby uses no cookies, does not access the end user's device, nor gathers any other personally identifiable information - we only collect anonymous data directly on your web server. Thus Scoby Analytics requires no consent regarding GDPR, ePrivacy, and Schrems II.
Version: 2.2.4
Author: Scoby GmbH
Author URI: https://www.scoby.io
Requires PHP: 7.4
*/

if (!defined('ABSPATH')) exit;
define('SCOBY_ANALYTICS_MIN_PHP_VERSION', '7.4');
if(!defined('SCOBY_ANALYTICS_PLUGIN_ROOT')) {
    define('SCOBY_ANALYTICS_PLUGIN_ROOT', untrailingslashit(plugin_dir_path(__FILE__)));
}

require_once SCOBY_ANALYTICS_PLUGIN_ROOT . '/deps/scoper-autoload.php';
require_once SCOBY_ANALYTICS_PLUGIN_ROOT . '/deps/autoload.php';
require_once SCOBY_ANALYTICS_PLUGIN_ROOT . '/vendor/autoload.php';

require_once SCOBY_ANALYTICS_PLUGIN_ROOT . '/settings.php';

use ScobyAnalytics\Helpers;
use ScobyAnalytics\Plugin;

if (version_compare(PHP_VERSION, SCOBY_ANALYTICS_MIN_PHP_VERSION, '<=')) {
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
    $version = Helpers::getVersion();
    if ($version !== get_option('scoby_analytics_version')) {
        $plugin->activate();
        update_option('scoby_analytics_version', $version);
    }

    if(get_transient('scoby_analytics_check_config')) {
        Helpers::checkConfig();
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

$options = Helpers::getConfig();
if (empty($options['api_key'])) {
    add_action('admin_notices', function () {
        ?>
		<div class="notice-warning notice">
			<p><?php print_f(_e('Scoby Analytics will measure your traffic, as soon as you have entered your API Key in the <a href="%s">Plugin\'s Settings</a>.', 'scoby_analytics_textdomain'),  esc_attr(admin_url('options-general.php?page=scoby-analytics-plugin'))); ?></p>
		</div>
        <?php
    });
} else {
    add_action('admin_notices', function () {
        $cachePlugin = get_transient('scoby_analytics_flush_cache_notice');
        if(!empty($cachePlugin)) {
            ?>
            <div class="notice-warning notice">
                <p><?php print_f(_e('Scoby Analytics has detected you are using the <b>%s</b> Plugin. Please flush your cache to start measuring.', 'scoby_analytics_textdomain'), esc_html($cachePlugin)); ?></p>
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
                <p><?php print_f(_e('Scoby Analytics has detected you are using the <b>%s</b> Plugin, but are using our Standard integration. We strongly recommend to <b>switch to Cache-Optimized integration</b> in the  <a href="%s">Plugin\'s Advanced Settings</a> for optimal results.', 'scoby_analytics_textdomain'), esc_html($cachePlugin), esc_attr(admin_url('options-general.php?page=scoby-analytics-plugin&tab=advanced'))); ?></p>
            </div>
            <?php
            delete_transient('scoby_analytics_use_client_integration');
        }
    });
}

function scoby_analytics_add_action_links($actions)
{
    $mylinks = array(
        printf('<a href="%s">Settings</a>', esc_attr(admin_url('options-general.php?page=scoby-analytics-plugin'))),
    );
    $actions = array_merge($mylinks, $actions);
    return $actions;
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'scoby_analytics_add_action_links');
