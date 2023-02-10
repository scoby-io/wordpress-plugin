<?php

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


add_action( 'admin_menu', function () {
    add_menu_page( 'Scoby Analytics', 'Scoby Analytics', 'manage_options', 'scoby-analytics-plugin', 'scoby_analytics_render_settings_page', 'dashicons-chart-bar' );
});

function getActiveTab() {
    return !empty($_GET['tab']) ? $_GET['tab'] : null;
}

function scoby_analytics_render_settings_page()
{
    ?>
    <div class="wrap">
        <h1>Scoby Analytics</h1>


        <nav class="nav-tab-wrapper">
            <a href="?page=scoby-analytics-plugin" class="nav-tab <?php if(empty(getActiveTab())) echo 'nav-tab-active' ?>">General</a>
            <a href="?page=scoby-analytics-plugin&tab=advanced" class="nav-tab <?php if(getActiveTab() === 'advanced') echo 'nav-tab-active' ?>">Advanced Settings</a>
        </nav>
        <form action="options.php" method="post">
            <?php
            settings_fields('scoby_analytics_options');
            do_settings_sections('scoby_analytics'); ?>
            <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save Settings'); ?>"/>
        </form>
    </div>
    <?php
}

function scoby_analytics_register_settings()
{
    register_setting('scoby_analytics_options', 'scoby_analytics_options', 'scoby_analytics_options_validate');
    add_settings_section('scoby_analytics_settings', 'General Settings', 'scoby_analytics_section_text', 'scoby_analytics');

    add_settings_field('scoby_analytics_setting_jar_id', 'Jar ID', 'scoby_analytics_setting_jar_id', 'scoby_analytics', 'scoby_analytics_settings');
}

function scoby_analytics_register_advanced_settings()
{
    add_settings_section('scoby_analytics_advanced_settings', 'Advanced Settings', 'scoby_analytics_section_text', 'scoby_analytics');

    add_settings_field('scoby_analytics_setting_integration_type', 'Integration Type', 'scoby_analytics_setting_integration_type', 'scoby_analytics', 'scoby_analytics_advanced_settings');
    add_settings_field('scoby_analytics_setting_endpoint', 'Privacy Proxy Path', 'scoby_analytics_setting_endpoint', 'scoby_analytics', 'scoby_analytics_advanced_settings');
    add_settings_field('scoby_analytics_setting_logging_enabled', 'Logging enabled?', 'scoby_analytics_setting_logging_enabled', 'scoby_analytics', 'scoby_analytics_advanced_settings');

}

if(getActiveTab() === 'advanced') {
    add_action('admin_init', 'scoby_analytics_register_advanced_settings');
} else {
    add_action('admin_init', 'scoby_analytics_register_settings');
}

function scoby_analytics_options_validate($input)
{
    $newinput = Helpers::getConfig();

    if(!empty($input['jar_id'])) {

        $jarId = trim($input['jar_id']);

        if (empty($jarId)) {
            add_settings_error(
                'scoby_analytics_options_jar_id',
                esc_attr('settings_updated'),
                'The Jar ID can not be empty.',
                'error'
            );
            return;
        }

        $client = new Client($jarId);

        if ($client->testConnection()) {
            $newinput['jar_id'] = $jarId;
        } else {
            add_settings_error(
                'scoby_analytics_options_jar_id',
                esc_attr('settings_updated'),
                'The Jar ID you provided is invalid. Please check and try again.',
                'error'
            );
            return;
        }
    }

    if(!empty($input['logging_enabled'])) {
        $newinput['logging_enabled'] = $input['logging_enabled'] === 'yes';
    }

    if(!empty($input['integration_type'])) {
        $newinput['integration_type'] = $input['integration_type'];
    }

    if(!empty($input['proxy_endpoint'])) {
        $newinput['proxy_endpoint'] = $input['proxy_endpoint'];
    }

    $newinput['manual_config'] = true;

    set_transient('scoby_analytics_check_config', true);

    return $newinput;
}

function scoby_analytics_section_text()
{
//    echo '<p>Please enter the Jar ID from your </p>';
}

function scoby_analytics_setting_jar_id()
{
    $options = get_option('scoby_analytics_options');
    $apiKey = !empty($options['jar_id']) ? $options['jar_id'] : "";
    echo "<input id='scoby_analytics_setting_jar_id' name='scoby_analytics_options[jar_id]' type='text' value='" . esc_attr($apiKey) . "' />";
    echo '<p>To find your Jar ID:</p>';
    echo '<ol>
    <li>Log into your account on <a href="https://app.scoby.io" target="_blank">https://app.scoby.io</a></li>
    <li>Click your name on the upper right</li>
    <li>Select "Integration Guide"</li>
    <li>Find your Jar ID in the Wordpress section.</li>
</ol>';
}

function scoby_analytics_setting_logging_enabled()
{
    $options = Helpers::getConfig();
    $loggingEnabled = !empty($options['logging_enabled']) ? $options['logging_enabled'] : false;
    $checked = $loggingEnabled === true ? 'checked' : '';
    echo "<input type='checkbox' id='scoby_analytics_setting_logging_enabled' name='scoby_analytics_options[logging_enabled]' value='yes' " . $checked . " />";
    echo '<p>If logging is enabled all requests to scoby servers and other useful debug information <br>
             will be logged into the log file of this Wordpress installation.</p>';
}

function scoby_analytics_setting_integration_type()
{
    $options = Helpers::getConfig();
    $integrationType = !empty($options['integration_type']) ? $options['integration_type'] : IntegrationType::detect();
    echo "<select id='scoby_analytics_setting_integration_type' name='scoby_analytics_options[integration_type]' >
    <option value='SERVER' ".($integrationType === 'SERVER' ? 'selected' : '').">Standard</option>    
    <option value='CLIENT' ".($integrationType === 'CLIENT' ? 'selected' : '').">Cache-Optimized</option>    
</select>
<input type='hidden' name='scoby_analytics_options[endpoint]' value=''>
";
    $cachePlugin = Helpers::getInstalledCachePlugin();
    if($cachePlugin) {
        echo '<p>We detected the '.$cachePlugin.' Plugin and recommend to use our Cache-Optimized Integration Type. <br>
                 Our Standard integration type requires each page view to be served by your wordpress installation<br>
                 Please only use our Standard integration if you know what you are doing.</p>';
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
    echo "<input type='text' name='scoby_analytics_options[proxy_endpoint]' value='".$endpoint."'>";

    echo '<p>When using Cache-Optimized integration, your traffic is routed through this path. <br>
             When this value is set to "foobar", scoby will measure traffic through calls to '.$_SERVER['HTTP_HOST'].'/foobar. <br>
             Scoby automatically chooses a random value to avoid collisions with any of your site\'s URLs.</p>';
}
;