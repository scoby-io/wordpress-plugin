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
        return self::generateRandomString(6);
    }

    public static function generateSalt() {
        return self::generateRandomString(32);
    }

    private static function generateRandomString($length) {
        return substr(str_shuffle(MD5(microtime())), 0, $length);
    }

    public static function autoConfigure()
    {
        $settings = self::getConfig();

        // privacy proxy needs this when plugin is installed in funky path
        $settings['plugin_dir'] = SCOBY_ANALYTICS_PLUGIN_ROOT;

        if(empty($settings['manual_config'])) {
            $settings['integration_type'] = IntegrationType::detect();

            if(empty($settings['proxy_endpoint'])) {
                $settings['proxy_endpoint'] = self::generateProxyEndpoint();
            }
        }

        // migrate jar_id to api_key
        if (empty($settings['api_key'])) {
            if (!empty($settings['jar_id'])) {
                $jarId = $settings['jar_id'];
                $settings['api_key'] = base64_encode($jarId . "|" . md5(self::generateSalt()));
                unset($settings['jar_id']);
            }
        }

        if(empty($settings['salt'])) {
            $settings['salt'] = self::generateSalt();
        }

        self::setConfig($settings);

        set_transient('scoby_analytics_check_config', true);
    }

    public static function getConfig() {
        $settings = get_option('scoby_analytics_options', []);

        if(!is_array($settings)) {
            return [];
        }

        return $settings;
    }

    public static function setConfig($config) {
        update_option('scoby_analytics_options', $config);
    }

    public static function resetConfig() {
        delete_option('scoby_analytics_options');
    }

    public static function checkConfig() {
        $settings = self::getConfig();

        $cachePlugin = self::getInstalledCachePlugin();
        if(!empty($settings['integration_type']) && $settings['integration_type'] === IntegrationType::$CLIENT && $cachePlugin) {
            set_transient('scoby_analytics_flush_cache_notice', $cachePlugin);
            return false;
        }

        $cachePlugin = self::getInstalledCachePlugin();
        if(!empty($settings['integration_type']) && $settings['integration_type'] === IntegrationType::$SERVER && $cachePlugin) {
            set_transient('scoby_analytics_use_client_integration', $cachePlugin);
            return false;
        }

        return true;
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
        if (file_exists($target)) {
            unlink(self::getProxyTarget());
        }
    }

    public static function getVersion() {
        $packageJson = file_get_contents(__DIR__ . '/../package.json');
        $data = json_decode($packageJson, true);
        return $data['version'];
    }

    public static function setupInProgress($settings) {
        if(!empty($settings['setup_in_progress'])) {
            if($settings['setup_expires'] > time() && $settings['setup'] === 'verify') {
                return true;
            }
        }
        return false;
    }
    public static function setupComplete($settings) {
        if(!empty($settings['setup']) && $settings['setup'] === 'complete') {
                return true;
        }
        return false;
    }
    public static function maybeCleanupSetup($settings) {
        // clean up?
        if(!empty($settings['setup_in_progress'])) {
            if($settings['setup_expires'] > time()) {
                $settings = self::resetSetup($settings);
            }
        }
        return $settings;
    }
    public static function resetSetup($settings) {
        if(!empty($settings['setup_code'] )) {
            $settings['setup_code'] = null;
        }
        if(!empty($settings['setup_email'] )) {
            $settings['setup_email'] = null;
        }
        if(!empty($settings['setup_started'] )) {
            $settings['setup_started'] = null;
        }
        if(!empty($settings['setup'] )) {
            $settings['setup'] = null;
        }
        if(!empty($settings['setup_in_progress'] )) {
            $settings['setup_in_progress'] = null;
        }
        return $settings;
    }
}