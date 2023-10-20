<?php

namespace ScobyAnalytics;

use ScobyAnalyticsDeps\Scoby\Analytics\Client;
use ScobyAnalyticsDeps\Scoby\Analytics\Helpers;

class Plugin
{
    public function initialize()
    {
        $settings = $this->getSettings();

        if(!empty($settings['integration_type']) && $settings['integration_type'] === 'SERVER') {
            \add_action('wp_footer', function () use ($settings) {
                if (!empty($settings['api_key'])) {
                    $apiKey = $settings['api_key'];
                    $salt = $settings['salt'];
                    $client = new Client($apiKey, $salt);
                    $loggingEnabled = !empty($settings['logging_enabled']) && $settings['logging_enabled'] === true;
                    if ($loggingEnabled) {
                        $logger = new Logger();
                        $client->setLogger($logger);
                    }

                    $client->logPageViewAsync();
                }
            });
        } else if(!empty($settings['integration_type']) && $settings['integration_type'] === 'CLIENT') {
            $proxyEndpoint = \esc_js(!empty($settings['proxy_endpoint']) ? $settings['proxy_endpoint'] : '');
            \add_action('wp_footer', function () use ($proxyEndpoint) {

                $scriptCode = <<<SCRIPT_CODE
<script type="text/javascript" async>
fetch('/$proxyEndpoint?' + (Math.random() + 1).toString(36).substring(2), {
    method: 'POST',
    mode: 'same-origin',
    cache: 'no-cache',
    credentials: 'omit',
    referrerPolicy: 'no-referrer',
    body: JSON.stringify({
        url: document.location.href,
        ref: document.referrer
    })
}).catch(console.log);
</script>
SCRIPT_CODE;

                echo $scriptCode;
            });
        }
    }

    private function getSettings()
    {
        return get_option('scoby_analytics_options');
    }

    public function activate()
    {
        \ScobyAnalytics\Helpers::autoConfigure();
        \ScobyAnalytics\Helpers::installPrivacyProxy();
    }

    public function deactivate()
    {
        \ScobyAnalytics\Helpers::uninstallPrivacyProxy();
        \ScobyAnalytics\Helpers::resetConfig();
    }
}