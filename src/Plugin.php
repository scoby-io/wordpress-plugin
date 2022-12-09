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

    private function getProxySource() {
        return join(DIRECTORY_SEPARATOR, array(
            __DIR__,
            'privacy-proxy.php'
        ));
    }

    private function getProxyTarget() {
        return join(DIRECTORY_SEPARATOR, array(
            WPMU_PLUGIN_DIR,
            'scoby-analytics-privacy-proxy.php'
        ));
    }

    public function hasCachePluginInstalled() {

        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        foreach (\get_plugins() as $plugin) {
            if(substr_count(strtolower($plugin['Name']), 'cache') > 0) {
                return $plugin['Name'];
            }
        }
        return false;
    }

    public function activate()
    {
        if(!is_dir(WPMU_PLUGIN_DIR)) {
            mkdir(WPMU_PLUGIN_DIR);
        }

        copy($this->getProxySource(), $this->getProxyTarget());
        $this->autoConfig();
    }

    private function autoConfig() {

        $settings = get_option('scoby_analytics_options', []);

        if(empty($settings['integration_type'])) {
            $cachePlugin = $this->hasCachePluginInstalled();
            $integrationType = $cachePlugin ? IntegrationType::$CLIENT : IntegrationType::$SERVER;
            $settings['integration_type'] = $integrationType;
            $settings['proxy_endpoint'] = substr(str_shuffle(MD5(microtime())), 0, 6);
            update_option('scoby_analytics_options', $settings);
            set_transient('scoby_analytics_flush_cache_notice', $cachePlugin);
        }
    }

    public function deactivate()
    {
        $target = $this->getProxyTarget();
        if(file_exists($target)) {
            unlink($this->getProxyTarget());
        }
    }
}