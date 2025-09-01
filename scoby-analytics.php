<?php
/*
Plugin Name: Scoby Analytics
Description: Scoby Analytics redefines website traffic analysis by exclusively utilizing anonymous data from your web server, prioritizing visitor privacy. This approach ensures full alignment with GDPR, ePrivacy, and Schrems II, offering insightful analytics without the need for visitor consent.
Version: 3.2.3
Author: Scoby GmbH
Author URI: https://www.scoby.io
Requires PHP: 7.4
License: GPLv2
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
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
//Helpers::resetConfig();
//Helpers::autoConfigure();
if (empty($options['api_key'])) {
    if (Helpers::setupInProgress($options)) {
        add_action('admin_notices', function () use ($options) {
            ?>
            <div class="notice-warning notice">
                <p><?php printf(\wp_kses(__('Scoby Analytics has sent a setup code to %s, please enter this code in the <a href="%s">Plugin\'s Settings</a>.', 'scoby_analytics_textdomain'), array('a' => array('href' => array()))), $options['setup_email'], esc_url(admin_url('options-general.php?page=scoby-analytics-plugin'))); ?></p>
            </div>
            <?php
        });
    } else {
        add_action('admin_notices', function () {
            ?>
            <div class="notice-warning notice">
                <p><?php printf(\wp_kses(__('Scoby Analytics will start measuring your traffic, as soon as you complete setup in the <a href="%s">Plugin\'s Settings</a>.', 'scoby_analytics_textdomain'), array('a' => array('href' => array()))), esc_url(admin_url('options-general.php?page=scoby-analytics-plugin'))); ?></p>
            </div>
            <?php
        });
    }

} else {
    if (Helpers::setupComplete($options)) {
        $options = Helpers::resetSetup($options);
        Helpers::setConfig($options);
        add_action('admin_notices', function () {
            ?>
            <div class="notice-success notice">
                <p><?php printf(\__('Scoby Analytics setup is complete! Your traffic data will start appearing in your Dashboard shortly.', 'scoby_analytics_textdomain')); ?></p>
            </div>
            <?php
        });
    }

    add_action('admin_notices', function () {
        $cachePlugin = get_transient('scoby_analytics_flush_cache_notice');
        if(!empty($cachePlugin)) {
            ?>
            <div class="notice-warning notice">
                <p><?php printf(\wp_kses(__('Scoby Analytics has detected you are using the <b>%s</b> Plugin. Please flush your cache to start measuring.', 'scoby_analytics_textdomain'), array('b' => array())), esc_html($cachePlugin)); ?></p>
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
                <?php
                printf(
                    \wp_kses(
                        __('Scoby Analytics has detected you are using the <b>%s</b> Plugin, but are using our Standard integration. We strongly recommend to <b>switch to Cache-Optimized integration</b> in the <a href="%s">Plugin\'s Advanced Settings</a> for optimal results.', 'scoby_analytics_textdomain'),
                        array(
                            'b' => array(),
                            'a' => array(
                                'href' => array(),
                            ),
                        )
                    ),
                    esc_html($cachePlugin), // Escaping for HTML content
                    esc_url(admin_url('options-general.php?page=scoby-analytics-plugin&tab=advanced')) // Escaping for URL
                );
                ?>
            </div>
            <?php
            delete_transient('scoby_analytics_use_client_integration');
        }
    });
}

function scoby_analytics_add_action_links($actions)
{
    $mylinks = array(
        '<a href="' . esc_attr(admin_url('options-general.php?page=scoby-analytics-plugin')) . '">Settings</a>',
    );
    $actions = array_merge($mylinks, $actions);
    return $actions;
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'scoby_analytics_add_action_links');
