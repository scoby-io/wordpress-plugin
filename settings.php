<?php

if (!defined('ABSPATH')) exit;

use ScobyAnalytics\Helpers;
use ScobyAnalytics\HttpClient;
use ScobyAnalytics\IntegrationType;
use ScobyAnalyticsDeps\Scoby\Analytics\Client;

require_once __DIR__ . '/deps/scoper-autoload.php';
require_once __DIR__ . '/vendor/autoload.php';

function initialize_settings_page()
{

}

function scoby_analytics_add_settings_page()
{
    $options = Helpers::getConfig();
    if (!empty($options['api_key']) || !empty(scoby_analytics_get_active_tab())) {
        add_options_page('Scoby Analytics', 'Scoby Analytics', 'manage_options', 'scoby-analytics-plugin', 'scoby_analytics_render_settings_page');
    } else {
        add_options_page('Scoby Analytics', 'Scoby Analytics', 'manage_options', 'scoby-analytics-plugin', 'scoby_analytics_render_setup_page');
    }
}

add_action('admin_menu', 'scoby_analytics_add_settings_page');


add_action('admin_menu', function () {

    $options = Helpers::getConfig();
    if (!empty($options['api_key']) || !empty(scoby_analytics_get_active_tab())) {
        add_menu_page('Scoby Analytics', 'Scoby Analytics', 'manage_options', 'scoby-analytics-plugin', 'scoby_analytics_render_settings_page', 'dashicons-chart-bar');
    } else {
        add_menu_page('Scoby Analytics', 'Scoby Analytics', 'manage_options', 'scoby-analytics-plugin', 'scoby_analytics_render_setup_page', 'dashicons-chart-bar');
    }
});

function scoby_analytics_get_active_tab()
{
    if (!empty($_GET['tab'])) {
        return filter_var($_GET['tab'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }
}

function scoby_analytics_render_setup_page()
{
    $settings = Helpers::getConfig();
    if(Helpers::setupInProgress($settings)) {
        scoby_analytics_setup_verify_page();
    } else {
        scoby_analytics_setup_initialize_page();
    }
}

function scoby_analytics_render_settings_page()
{
    ?>
    <div class="wrap">
        <h1>Scoby Analytics</h1>

        <style>
            input[name="scoby_analytics_options[api_key]"] {
                width: 575px !important;
            }

            input[name="scoby_analytics_options[salt]"] {
                width: 300px !important;
            }
        </style>


        <nav class="nav-tab-wrapper">
            <a href="?page=scoby-analytics-plugin&tab=basic"
               class="nav-tab <?php if (empty(scoby_analytics_get_active_tab()) || scoby_analytics_get_active_tab() === 'basic') echo 'nav-tab-active' ?>">General</a>
            <a href="?page=scoby-analytics-plugin&tab=advanced"
               class="nav-tab <?php if (scoby_analytics_get_active_tab() === 'advanced') echo 'nav-tab-active' ?>">Advanced
                Settings</a>
        </nav>
        <form id="scoby_analytics_settings_form" action="options.php" method="post">
            <?php
            settings_fields('scoby_analytics_options');
            do_settings_sections('scoby_analytics'); ?>
            <input name="submit_button" class="button button-primary" type="submit"
                   value="<?php esc_attr_e('Save Settings'); ?>"/>
            <a href="https://analytics.scoby.io" target="_blank" name="submit_button" class="button button-primary"><?php esc_attr_e('Go to Dashboard'); ?></a>
        </form>
    </div>
    <?php
}

function scoby_analytics_setup_initialize_page()
{
    ?>
    <style>
        .scoby_analytics_icon {
            width: 64px !important;
            height: 64px !important;
            margin-left: auto;
            margin-right: auto;
            margin-bottom: 12px;
        }

        .scoby_analytics_setup_section {

            margin-top: 150px;
            text-align: center;
            width: 66%;
            margin-right: auto;
            margin-left: auto;
            /*background: red;*/

        }

        .scoby_analytics_setup_head {

            font-weight: bold;
            font-size: large;
            margin-bottom: 16px;
            color: #454545;

        }

        .scoby_analytics_setup_body {

            font-size: medium;
            margin-bottom: 16px;
            line-height: 24px;
            color: #666;

        }

        .scoby_analytics_setup_lower_body {

            font-size: normal;
            margin-bottom: 12px;
            margin-top: 16px;
            line-height: 16px;
            color: #676767;
            display: block;
            width: 70%;
            margin-right: auto;
            margin-left: auto;

        }

        .scoby_analytics_setup_skip_link {
            font-size: normal;
        }

        .scoby_analytics_setup_button {
            font-size: medium !important;
        }

        input[name="scoby_analytics_options[salt]"] {
            width: 300px !important;
        }
    </style>
    <div class="wrap">
        <h1>Scoby Analytics</h1>

        <div class="scoby_analytics_setup_section">
            <div class="scoby_analytics_icon">
                <svg data-slot="icon" aria-hidden="true" fill="none" stroke-width="1.5" stroke="currentColor"
                     viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13.5 16.875h3.375m0 0h3.375m-3.375 0V13.5m0 3.375v3.375M6 10.5h2.25a2.25 2.25 0 0 0 2.25-2.25V6a2.25 2.25 0 0 0-2.25-2.25H6A2.25 2.25 0 0 0 3.75 6v2.25A2.25 2.25 0 0 0 6 10.5Zm0 9.75h2.25A2.25 2.25 0 0 0 10.5 18v-2.25a2.25 2.25 0 0 0-2.25-2.25H6a2.25 2.25 0 0 0-2.25 2.25V18A2.25 2.25 0 0 0 6 20.25Zm9.75-9.75H18a2.25 2.25 0 0 0 2.25-2.25V6A2.25 2.25 0 0 0 18 3.75h-2.25A2.25 2.25 0 0 0 13.5 6v2.25a2.25 2.25 0 0 0 2.25 2.25Z"
                          stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </div>
            <div class="scoby_analytics_setup_head">Ready to get started?</div>
            <div class="scoby_analytics_setup_body">
                Click the button below to start measuring your traffic the right way with Scoby Analytics - <b>It's free
                    for private websites, non-profit and open-source projects</b> and Commercial projects enjoy all
                features risk-free for 30 days, no credit card required and no need to cancel.
            </div>
            <form id="scoby_analytics_setup_form" action="options.php" method="post">
                <?php
                settings_fields('scoby_analytics_options');
                ?>
                <input name="submit_button" class="button button-primary scoby_analytics_setup_button" type="submit"
                       value="<?php esc_attr_e('Connect ' . get_bloginfo('name') . ' to Scoby Analytics'); ?>"/>

                <input type='hidden' name="scoby_analytics_options[setup]"
                       value="initialize"/>
                <input type='hidden' name="scoby_analytics_options[name]"
                       value="<?php esc_attr_e(get_bloginfo('name')); ?>"/>
                <input type='hidden' name="scoby_analytics_options[email]"
                       value="<?php esc_attr_e(wp_get_current_user()->user_email); ?>"/>
            </form>

            <!--            <input type='text' placeholder='Enter your setup code' class="scoby_analytics_setup_button" />-->
            <!--            <input name="submit_button" class="button button-primary" style="margin-top: 2px" type="button" value="OK"/>-->

            <div class="scoby_analytics_setup_lower_body">
                <a href="?page=scoby-analytics-plugin&tab=basic" class="scoby_analytics_setup_skip_link">
                    Click here if you already have a License Key
                </a>
            </div>

        </div>
    </div>
    <?php
}
function scoby_analytics_setup_verify_page()
{
    $settings = Helpers::getConfig();
    ?>
    <style>
        .scoby_analytics_icon {
            width: 64px !important;
            height: 64px !important;
            margin-left: auto;
            margin-right: auto;
            margin-bottom: 12px;
        }

        .scoby_analytics_setup_section {

            margin-top: 150px;
            text-align: center;
            width: 66%;
            margin-right: auto;
            margin-left: auto;
            /*background: red;*/

        }

        .scoby_analytics_setup_head {

            font-weight: bold;
            font-size: large;
            margin-bottom: 16px;
            color: #454545;

        }

        .scoby_analytics_setup_body {

            font-size: medium;
            margin-bottom: 16px;
            line-height: 24px;
            color: #666;

        }

        .scoby_analytics_setup_lower_body {

            font-size: normal;
            margin-bottom: 12px;
            margin-top: 16px;
            line-height: 16px;
            color: #676767;
            display: block;
            width: 70%;
            margin-right: auto;
            margin-left: auto;

        }

        .scoby_analytics_setup_skip_link {
            font-size: normal;
        }

        .scoby_analytics_setup_button {
            font-size: medium !important;
        }

        input[name="scoby_analytics_options[salt]"] {
            width: 300px !important;
        }
    </style>
    <div class="wrap">
        <h1>Scoby Analytics</h1>

        <div class="scoby_analytics_setup_section">
            <div class="scoby_analytics_icon">
                <svg data-slot="icon" aria-hidden="true" fill="none" stroke-width="1.5" stroke="currentColor"
                     viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13.5 16.875h3.375m0 0h3.375m-3.375 0V13.5m0 3.375v3.375M6 10.5h2.25a2.25 2.25 0 0 0 2.25-2.25V6a2.25 2.25 0 0 0-2.25-2.25H6A2.25 2.25 0 0 0 3.75 6v2.25A2.25 2.25 0 0 0 6 10.5Zm0 9.75h2.25A2.25 2.25 0 0 0 10.5 18v-2.25a2.25 2.25 0 0 0-2.25-2.25H6a2.25 2.25 0 0 0-2.25 2.25V18A2.25 2.25 0 0 0 6 20.25Zm9.75-9.75H18a2.25 2.25 0 0 0 2.25-2.25V6A2.25 2.25 0 0 0 18 3.75h-2.25A2.25 2.25 0 0 0 13.5 6v2.25a2.25 2.25 0 0 0 2.25 2.25Z"
                          stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </div>
            <div class="scoby_analytics_setup_head">Check your Inbox!</div>
            <div class="scoby_analytics_setup_body">
                We have sent a setup code to <?php esc_attr_e($settings['setup_email']); ?>. <br/>
                Enter this code and click "OK" to finalize setup.
            </div>
            <form id="scoby_analytics_setup_form" action="options.php" method="post">
                <?php
                settings_fields('scoby_analytics_options');
                ?>
                <input type='text' placeholder='Enter your setup code' class="scoby_analytics_setup_button" name="scoby_analytics_options[token]" />
                <input type='hidden' name="scoby_analytics_options[code]" value="<?php esc_attr_e($settings['setup_code']); ?>"/>
                <input type='hidden' name="scoby_analytics_options[setup]" value="verify"/>
                <input name="submit_button" class="button button-primary" style="margin-top: 2px" type="submit" value="OK"/>
            </form>

            <form id="scoby_analytics_setup_reset_form" action="options.php" method="post">
                <?php
                settings_fields('scoby_analytics_options');
                ?>
                <input type='hidden' name="scoby_analytics_options[setup]" value="reset"/>
                <div class="scoby_analytics_setup_lower_body">
                    <a href="javascript:;" onclick="document.getElementById('scoby_analytics_setup_reset_form').submit()" class="scoby_analytics_setup_skip_link">
                        Click here to start again
                    </a>
                </div>
            </form>

        </form>
    </div>
    <?php
}

function scoby_analytics_register_settings()
{
    register_setting('scoby_analytics_options', 'scoby_analytics_options', 'scoby_analytics_options_validate');
    add_settings_section('scoby_analytics_settings', 'General Settings', 'scoby_analytics_section_text', 'scoby_analytics');
    add_settings_field('scoby_analytics_setting_api_key', 'License Key', 'scoby_analytics_setting_api_key', 'scoby_analytics', 'scoby_analytics_settings');
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
} elseif (scoby_analytics_get_active_tab() === 'basic') {
    // manually add license key
    add_action('admin_init', 'scoby_analytics_register_settings');
} else {
    add_action('admin_init', 'scoby_analytics_register_settings');
}

function post($url, $data) {
    $body = wp_json_encode($data);

    $response = wp_remote_post($url, [
        'body' => $body,
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'timeout' => 60,
        'redirection' => 5,
        'blocking' => true,
        'httpversion' => '1.0',
        'sslverify' => false,
        'data_format' => 'body',
    ]);

    return  json_decode(wp_remote_retrieve_body($response), true);
}

function scoby_analytics_setup_validate($input)
{
    $settings = [];
    if($input['setup'] === 'initialize' ) {
        $data = post('https://api.scoby.io/setup/initialize', [
            'email' => $input['email'],
            'name' => $input['name'],
        ]);

        if(!empty($data['code'])) {
            $settings['setup_in_progress'] = true;
            $settings['setup_code'] = $data['code'];
            $settings['setup_email'] = $input['email'];
            $settings['setup_expires'] = time() + (15*60);
            $settings['setup'] = 'verify';
        } else {
            add_settings_error(
                'scoby_analytics_setup_general',
                esc_attr('settings_updated'),
                'Something went wrong. Please try again or check how to setup Scoby Analytics manually using our <a href="https://docs.scoby.io/getting-started/create-workspace" target="_blank">Documentation</a>',
                'error'
            );
        }

    } elseif($input['setup'] === 'verify' && !empty($input['token']) ) {

        $data = post('https://api.scoby.io/setup/verify', [
            'code' => $input['code'],
            'token' => $input['token'],
        ]);

        if(!$data['apiKey']) {
            add_settings_error(
                'scoby_analytics_setup_general',
                esc_attr('settings_updated'),
                'The code you have entered is not correct.',
                'error'
            );
        } else {
            $settings = Helpers::resetSetup($settings);
            $settings['api_key'] = $data['apiKey'];
        }
    }

    return $settings;
}


function scoby_analytics_options_validate($input)
{
    $setupSettings = scoby_analytics_setup_validate($input);
    // if newly generated app
    if(!empty($setupSettings['api_key'])) {
        $input['api_key'] = $setupSettings['api_key'];
    }

    $settings = Helpers::getConfig();
    $settings = array_merge($settings, $setupSettings);

    if($input['setup'] === 'reset' ) {
        $settings = Helpers::resetSetup($settings);
    }

    if (!empty($input['reset_api_key'])) {
        $settings['api_key'] = null;
    }
    if (!empty($input['reset_salt'])) {
        $settings['salt'] = Helpers::generateSalt();
    }

    if (!empty($input['api_key'])) {

        if (empty($settings['salt'])) {
            $settings['salt'] = Helpers::generateSalt();
        }

        $apiKey = trim($input['api_key']);
        $salt = $settings['salt'];

        if (empty($apiKey)) {
            add_settings_error(
                'scoby_analytics_options_api_key',
                esc_attr('settings_updated'),
                'The License Key can not be empty.',
                'error'
            );
            return;
        }

        $client = new Client($apiKey, $salt);

        $httpClient = new HttpClient();
        $client->setHttpClient($httpClient);

        if ($client->testConnection()) {
            $settings['api_key'] = $apiKey;
            $settings['salt'] = $salt;

            if(Helpers::setupInProgress($settings)) {
                $settings = Helpers::resetSetup($settings);
                $settings['setup'] = "complete";
            }

        } else {
            add_settings_error(
                'scoby_analytics_options_api_key',
                esc_attr('settings_updated'),
                'The License Key you provided is invalid. Please check and try again.',
                'error'
            );
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
}

function scoby_analytics_setting_api_key()
{
    $options = Helpers::getConfig();
    $apiKey = !empty($options['api_key']) ? $options['api_key'] : "";
    $disabled = !empty($apiKey) ? 'disabled' : '';
    printf("<input id='scoby_analytics_setting_api_key' name='scoby_analytics_options[api_key]' type='text' value='%s' %s />", \esc_attr($apiKey), \esc_attr($disabled));
    printf("<input type='hidden' id='scoby_analytics_reset_api_key' name='scoby_analytics_options[reset_api_key]' value=0 />");
    if (empty($apiKey)) {
        echo '<p>Have no License Key yet? Follow our <a href="https://docs.scoby.io/getting-started/create-workspace" target="_blank">Getting Started guide</a> and test 30 days for free! 
            <br>Free means free like in free beer - no credit card needed, no need to cancel.</p>';
    } else {
        printf('<a style="margin-left: 10px" href="javascript:;" onclick="document.getElementById(\'scoby_analytics_reset_api_key\').value=1;document.getElementById(\'scoby_analytics_settings_form\').submit();">reset License Key</a>');
    }

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
                 cache in front of your wordpress installation, please switch to Cache-Optimized integration and flush your Cache.</p>';
    }

}

function scoby_analytics_setting_endpoint()
{
    $options = Helpers::getConfig();
    $endpoint = !empty($options['proxy_endpoint']) ? $options['proxy_endpoint'] : Helpers::generateProxyEndpoint();
    printf("<input type='text' name='scoby_analytics_options[proxy_endpoint]' value='%s'>", \esc_attr($endpoint));

    $host = filter_var($_SERVER['HTTP_HOST'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    printf('<p>When using Cache-Optimized integration, Scoby Analytics measures page views by calls to this path. <br>
             When this value is set to "foobar", scoby will measure traffic through calls to %s/foobar. <br>
             Scoby automatically chooses a random value to avoid collisions with any of your site\'s URLs.</p>', \esc_html($host));
}

function scoby_analytics_setting_salt()
{
    $options = Helpers::getConfig();
    $salt = !empty($options['salt']) ? $options['salt'] : Helpers::generateSalt();
    $disabled = !empty($salt) ? 'disabled' : '';
    printf("<input type='text' name='scoby_analytics_options[salt]' value='%s' %s>", \esc_attr($salt), \esc_attr($disabled));
    printf("<input type='hidden' id='scoby_analytics_reset_salt' name='scoby_analytics_options[reset_salt]' value=0 />");

    printf('<a style="margin-left: 10px" href="javascript:;" onclick="var ok = confirm(\'Generating a new Privacy Salt will affect the unique counts in your dashboard. Are you sure you want to continue?\'); if(ok) { document.getElementById(\'scoby_analytics_reset_salt\').value=1;document.getElementById(\'scoby_analytics_settings_form\').submit() }">regenerate</a>');

    echo '<p>This value is used to anonymize sensitive parts of your traffic before they are sent to Scoby Analytics\' servers. <br/>You can safely ignore this setting, changing will affect the unique counts in your dashboard. <br/>Please do not share this value with Scoby Analytics support.</p>';
}