<?php

namespace ScobyAnalytics;

use ScobyAnalyticsDeps\Scoby\Analytics\Client;
use ScobyAnalyticsDeps\Scoby\Analytics\Helpers;

class Plugin
{
    private $blockedUserAgents = ['WP Rocket/Preload'];

    public function initialize()
    {
        $settings = $this->getSettings();

        if(!empty($settings['integration_type']) && $settings['integration_type'] === 'SERVER') {
            \add_action('wp_footer', function () use ($settings) {
                $userAgent = Helpers::getUserAgent();
                if (!empty($settings['jar_id']) && !in_array($userAgent, $this->blockedUserAgents)) {
                    $jarId = $settings['jar_id'];
                    $client = new Client($jarId);
                    $client->setUserAgent($userAgent);
                    $loggingEnabled = $settings['logging_enabled'] === true;
                    if ($loggingEnabled) {
                        $logger = new Logger();
                        $client->setLogger($logger);
                    }

                    $client->logPageViewAsync();
                }
            });
        } else if(!empty($settings['integration_type']) && $settings['integration_type'] === 'CLIENT') {
            $proxyEndpoint = !empty($settings['proxy_endpoint']) ? $settings['proxy_endpoint'] : '';
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
    }
}