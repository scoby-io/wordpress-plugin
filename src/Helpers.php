<?php

namespace ScobyAnalytics;

class Helpers {
    public static function hasCachePluginInstalled() {
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

    public static function getInstalledCachePlugin() {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        foreach (\get_plugins() as $plugin) {
            if(substr_count(strtolower($plugin['Name']), 'cache') > 0) {
                return $plugin['Name'];
            }
        }
        return null;
    }

    public static function generateProxyEndpoint() {
        return substr(str_shuffle(MD5(microtime())), 0, 6);
    }

    public static function autoConfigure() {
        $settings = get_option('scoby_analytics_options', []);

        // privacy proxy needs this when plugin is installed in funky path
        $settings['plugin_dir'] = SCOBY_ANALYTICS_PLUGIN_ROOT;

        if(empty($settings['manual_config'])) {
            $settings['integration_type'] = IntegrationType::detect();

            if(empty($settings['proxy_endpoint'])) {
                $settings['proxy_endpoint'] = self::generateProxyEndpoint();
            }
        }

        update_option('scoby_analytics_options', $settings);

        set_transient('scoby_analytics_check_config', true);
    }

    public static function checkConfig() {
        $settings = get_option('scoby_analytics_options', []);

        $cachePlugin = self::getInstalledCachePlugin();
        if($settings['integration_type'] === IntegrationType::$CLIENT && $cachePlugin) {
            set_transient('scoby_analytics_flush_cache_notice', $cachePlugin);
        }

        $cachePlugin = self::getInstalledCachePlugin();
        if($settings['integration_type'] === IntegrationType::$SERVER && $cachePlugin) {
            set_transient('scoby_analytics_use_client_integration', $cachePlugin);
        }
    }

    private static function getProxySource() {
        return join(DIRECTORY_SEPARATOR, array(
            __DIR__,
            'privacy-proxy.php'
        ));
    }

    private static function getProxyTarget() {
        return join(DIRECTORY_SEPARATOR, array(
            WPMU_PLUGIN_DIR,
            'scoby-analytics-privacy-proxy.php'
        ));
    }

    public static function installPrivacyProxy() {

        self::uninstallPrivacyProxy();

        if(!is_dir(WPMU_PLUGIN_DIR)) {
            mkdir(WPMU_PLUGIN_DIR);
        }

        copy(self::getProxySource(), self::getProxyTarget());
    }

    public static function uninstallPrivacyProxy() {

        $target = self::getProxyTarget();
        if(file_exists($target)) {
            unlink(self::getProxyTarget());
        }
    }

    public static function getVersion() {
        $packageJson = file_get_contents(__DIR__ . '/../package.json');
        $data = json_decode($packageJson, true);
        return $data['version'];
    }
}