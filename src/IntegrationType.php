<?php
namespace ScobyAnalytics;

class IntegrationType {
    public static $SERVER = 'SERVER';
    public static $CLIENT = 'CLIENT';

    public static function detect() {
        $hashCachePluginInstalled = Helpers::hasCachePluginInstalled();
        return $hashCachePluginInstalled ? self::$CLIENT : self::$SERVER;
    }
}