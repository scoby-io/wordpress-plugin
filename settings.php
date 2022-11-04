<?php

function scoby_analytics_add_settings_page()
{
    add_options_page('Scoby Analytics', 'Scoby Analytics', 'manage_options', 'scoby-analytics-plugin', 'scoby_analytics_render_settings_page');
}

add_action('admin_menu', 'scoby_analytics_add_settings_page');


function scoby_analytics_render_settings_page()
{
    ?>
    <form action="options.php" method="post">
        <?php
        settings_fields('scoby_analytics_options');
        do_settings_sections('scoby_analytics'); ?>
        <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save'); ?>"/>
    </form>
    <?php
}

function scoby_analytics_register_settings()
{
    register_setting('scoby_analytics_options', 'scoby_analytics_options', 'scoby_analytics_options_validate');
    add_settings_section('scoby_analytics_settings', 'Scoby Analytics Settings', 'scoby_analytics_section_text', 'scoby_analytics');

    add_settings_field('scoby_analytics_setting_jar_id', 'Jar ID', 'scoby_analytics_setting_jar_id', 'scoby_analytics', 'scoby_analytics_settings');
    add_settings_field('scoby_analytics_setting_logging_enabled', 'Logging enabled?', 'scoby_analytics_setting_logging_enabled', 'scoby_analytics', 'scoby_analytics_settings');
}

add_action('admin_init', 'scoby_analytics_register_settings');


function scoby_analytics_options_validate($input)
{
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

    $client = new \Scoby\Analytics\Client($jarId);

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

    $newinput['logging_enabled'] = $input['logging_enabled'] === 'yes';

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
}

function scoby_analytics_setting_logging_enabled()
{
    $options = get_option('scoby_analytics_options');
    $loggingEnabled = !empty($options['logging_enabled']) ? $options['logging_enabled'] : false;
    $checked = $loggingEnabled === true ? 'checked' : '';
    echo "<input type='checkbox' id='scoby_analytics_setting_logging_enabled' name='scoby_analytics_options[logging_enabled]' value='yes' " . $checked . " />";
}


