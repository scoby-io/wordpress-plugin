<?php

if (!defined('ABSPATH')) exit;

use ScobyAnalytics\Helpers;
use ScobyAnalytics\IntegrationType;
use ScobyAnalyticsDeps\Scoby\Analytics\Client;

require_once __DIR__ . '/deps/scoper-autoload.php';
require_once __DIR__ . '/vendor/autoload.php';

function scoby_analytics_add_settings_page()
{
    add_options_page('Scoby Analytics', 'Scoby Analytics', 'manage_options', 'scoby-analytics-plugin', 'scoby_analytics_render_settings_page');
}

add_action('admin_menu', 'scoby_analytics_add_settings_page');


add_action('admin_menu', function () {
    add_menu_page('Scoby Analytics', 'Scoby Analytics', 'manage_options', 'scoby-analytics-plugin', 'scoby_analytics_render_settings_page', 'dashicons-chart-bar');
});

function scoby_analytics_get_active_tab()
{
    if (!empty($_GET['tab'])) {
        return filter_var($_GET['tab'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }
}

function scoby_analytics_render_settings_page()
{
    ?>
    <div class="wrap">
        <h1>Scoby Analytics</h1>

        <style>
            input[name="scoby_analytics_options[api_key]"] {
                width: 600px !important;
            }

            input[name="scoby_analytics_options[salt]"] {
                width: 450px !important;
            }
        </style>


        <nav class="nav-tab-wrapper">
            <a href="?page=scoby-analytics-plugin"
               class="nav-tab <?php if (empty(scoby_analytics_get_active_tab())) echo 'nav-tab-active' ?>">General</a>
            <a href="?page=scoby-analytics-plugin&tab=advanced"
               class="nav-tab <?php if (scoby_analytics_get_active_tab() === 'advanced') echo 'nav-tab-active' ?>">Advanced
                Settings</a>
        </nav>
        <form action="options.php" method="post">
            <?php
            settings_fields('scoby_analytics_options');
            do_settings_sections('scoby_analytics'); ?>
            <input name="submit" class="button button-primary" type="submit"
                   value="<?php esc_attr_e('Save Settings'); ?>"/>
        </form>
    </div>
    <?php
}

function scoby_analytics_register_settings()
{
    register_setting('scoby_analytics_options', 'scoby_analytics_options', 'scoby_analytics_options_validate');
    add_settings_section('scoby_analytics_settings', 'General Settings', 'scoby_analytics_section_text', 'scoby_analytics');

    add_settings_field('scoby_analytics_setting_api_key', 'API Key', 'scoby_analytics_setting_api_key', 'scoby_analytics', 'scoby_analytics_settings');
}

function scoby_analytics_register_advanced_settings()
{
    add_settings_section('scoby_analytics_advanced_settings', 'Advanced Settings', 'scoby_analytics_section_text', 'scoby_analytics');

    add_settings_field('scoby_analytics_setting_integration_type', 'Integration Type', 'scoby_analytics_setting_integration_type', 'scoby_analytics', 'scoby_analytics_advanced_settings');
    add_settings_field('scoby_analytics_setting_endpoint', 'Privacy Proxy Path', 'scoby_analytics_setting_endpoint', 'scoby_analytics', 'scoby_analytics_advanced_settings');
    add_settings_field('scoby_analytics_setting_salt', 'Privacy Salt', 'scoby_analytics_setting_salt', 'scoby_analytics', 'scoby_analytics_advanced_settings');
    add_settings_field('scoby_analytics_setting_logging_enabled', 'Logging enabled?', 'scoby_analytics_setting_logging_enabled', 'scoby_analytics', 'scoby_analytics_advanced_settings');

}

if (scoby_analytics_get_active_tab() === 'advanced') {
    add_action('admin_init', 'scoby_analytics_register_advanced_settings');
} else {
    add_action('admin_init', 'scoby_analytics_register_settings');
}


function scoby_analytics_options_validate($input)
{
    $settings = Helpers::getConfig();

    if (!empty($input['api_key'])) {

        $apiKey = trim($input['api_key']);
        $salt = $settings['salt'];

        if (empty($apiKey)) {
            add_settings_error(
                'scoby_analytics_options_api_key',
                esc_attr('settings_updated'),
                'The API Key can not be empty.',
                'error'
            );
            return;
        }

        $client = new Client($apiKey, $salt);

        if ($client->testConnection()) {
            $settings['api_key'] = $apiKey;
            $settings['salt'] = $salt;
        } else {
            add_settings_error(
                'scoby_analytics_options_api_key',
                esc_attr('settings_updated'),
                'The API Key you provided is invalid. Please check and try again.',
                'error'
            );
            return;
        }
    }

    $settings['logging_enabled'] = (!empty($input['logging_enabled']) && $input['logging_enabled'] === 'yes');

    if (!empty($input['integration_type'])) {
        $settings['integration_type'] = $input['integration_type'];
    }

    if (!empty($input['salt'])) {
        $settings['salt'] = $input['salt'];
    }

    if (!empty($input['proxy_endpoint'])) {
        $settings['proxy_endpoint'] = $input['proxy_endpoint'];
    }

    $settings['manual_config'] = true;

    set_transient('scoby_analytics_check_config', true);

    return $settings;
}

function scoby_analytics_section_text()
{
//    echo '<p>Please enter the API Key from your </p>';
}

function scoby_analytics_setting_api_key()
{
    $options = Helpers::getConfig();
    $apiKey = !empty($options['api_key']) ? $options['api_key'] : "";
    printf("<input id='scoby_analytics_setting_api_key' name='scoby_analytics_options[api_key]' type='text' value='%s' />", esc_attr($apiKey));
    echo '<p>Have no API Key yet? Get yours now on <a href="https://analytics.scoby.io" target="_blank">https://analytics.scoby.io</a> and test 30 days for free! 
            <br>Free means free like in free beer - no credit card needed, no need to cancel.</p>';
}

function scoby_analytics_integration_test()
{
    $settings = Helpers::getConfig();

    // call homepage to trigger a call to scoby
    \wp_remote_get(\get_home_url());
    sleep(1);

    $apiKey = trim($settings['api_key']);
    $salt = $settings['salt'];

    $client = new Client($apiKey, $salt);
    $res = $client->getApiStatus();
    if ($res->getStatusCode() === 200) {
        $body = json_decode($res->getBody(), true);

        $origin = new DateTimeImmutable($body['lastHit']);
        $target = new DateTimeImmutable();
        $interval = $origin->diff($target);

        if (intval($interval->format('%s')) < 10) {
            echo '<p>Traffic is flowing in. Everything is fine.</p>';
        } else {
            echo '<p>It seems we are not receiving traffic from you currently. Please check your error logs and contact support.</p>';
        }
    } else {
        echo '<p>Something is not working properly on our end currently. Please check again later.</p>';
    }
}


function scoby_analytics_setting_logging_enabled()
{
    $options = Helpers::getConfig();
    $loggingEnabled = !empty($options['logging_enabled']) ? $options['logging_enabled'] : false;
    $checked = $loggingEnabled === true ? 'checked' : '';
    printf("<input type='checkbox' id='scoby_analytics_setting_logging_enabled' name='scoby_analytics_options[logging_enabled]' value='yes' %s />", esc_attr($checked));
    echo '<p>If logging is enabled all requests to scoby servers and other useful debug information <br>
             will be logged into the log file of this Wordpress installation.</p>';
}

function scoby_analytics_setting_integration_type()
{
    $options = Helpers::getConfig();
    $integrationType = !empty($options['integration_type']) ? $options['integration_type'] : IntegrationType::detect();
    echo "<select id='scoby_analytics_setting_integration_type' name='scoby_analytics_options[integration_type]' >
    <option value='SERVER' " . ($integrationType === 'SERVER' ? 'selected' : '') . ">Standard</option>    
    <option value='CLIENT' " . ($integrationType === 'CLIENT' ? 'selected' : '') . ">Cache-Optimized</option>    
</select>
<input type='hidden' name='scoby_analytics_options[endpoint]' value=''>
";
    $cachePlugin = Helpers::getInstalledCachePlugin();
    if ($cachePlugin) {
        printf('<p>We detected the %s Plugin and recommend to use our Cache-Optimized Integration Type. <br>
                 Our Standard integration type requires each page view to be served by your wordpress installation<br>
                 Please only use our Standard integration if you know what you are doing.</p>', \esc_html($cachePlugin));
    } else {
        echo '<p>We did not detect any Cache Plugin such as WP Rocket, Fastest Cache, Super Cache etc), so we assume <br>
                 you can safely use our Standard integration. If you render your pages to a CDN or facilitate some other <br>
                 cache in front of your wordpress installation, please switch to Cache-Optimized integration.</p>';
    }

}

function scoby_analytics_setting_endpoint()
{
    $options = Helpers::getConfig();
    $endpoint = !empty($options['proxy_endpoint']) ? $options['proxy_endpoint'] : Helpers::generateProxyEndpoint();
    printf("<input type='text' name='scoby_analytics_options[proxy_endpoint]' value='%s'>", \esc_attr($endpoint));

    $host = filter_var($_SERVER['HTTP_HOST'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    printf('<p>When using Cache-Optimized integration, your traffic is routed through this path. <br>
             When this value is set to "foobar", scoby will measure traffic through calls to %s/foobar. <br>
             Scoby automatically chooses a random value to avoid collisions with any of your site\'s URLs.</p>', \esc_html($host));
}

function scoby_analytics_setting_salt()
{
    $options = Helpers::getConfig();
    $endpoint = !empty($options['salt']) ? $options['salt'] : Helpers::generateSalt();
    printf("<input type='text' name='scoby_analytics_options[salt]' value='%s'>", \esc_attr($endpoint));

    echo '<p>This value is used to anonymize sensitive parts of your traffic before it is sent to our servers. <br>You can safely ignore this setting and please do not change it unless you know what your are doing.</p>';
}