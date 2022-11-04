<?php

namespace ScobyAnalytics;

use Scoby\Analytics\Client;

class Plugin
{

    public function __construct()
    {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function initialize()
    {
        \add_action('wp_footer', function () {
            $settings = $this->getSettings();
            if (!empty($settings['jar_id'])) {
                $jarId = $settings['jar_id'];
                $client = new Client($jarId);
                $loggingEnabled = $settings['logging_enabled'] === true;
                if ($loggingEnabled) {
                    $logger = new Logger();
                    $client->setLogger($logger);
                }
                $client->logPageViewAsync();
            }
        });
    }

    private function getSettings()
    {
        return get_option('scoby_analytics_options');
    }

    public function activate()
    {

    }

    public function deactivate()
    {

    }

}