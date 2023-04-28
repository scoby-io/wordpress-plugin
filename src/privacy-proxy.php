<?php
/**
 * Plugin Name: Scoby Analytics Privacy Proxy
 * Description: Preserves your visitors' privacy when using scoby analytics' client-side integration.
 * Author: scoby UG
 * Version: 1.0
 * Author URI: https://scoby.io
 */

use ScobyAnalytics\Logger;
use ScobyAnalyticsDeps\Scoby\Analytics\Client;

$settings = get_option('scoby_analytics_options');

$proxyEnabled = !empty($settings['integration_type']) && $settings['integration_type'] === 'CLIENT';
$proxyEndpoint = !empty($settings['proxy_endpoint']) ? "/" . $settings['proxy_endpoint'] : null;
$path = explode("?", $_SERVER['REQUEST_URI'])[0];

if($proxyEnabled === false || $proxyEndpoint === null || $path !== $proxyEndpoint) {
    return;
}

$pluginDir = $settings['plugin_dir'];

require join(DIRECTORY_SEPARATOR,  array(
    $pluginDir,
    'vendor',
    'autoload.php'
));

require join(DIRECTORY_SEPARATOR,  array(
    $pluginDir,
    'deps',
    'scoper-autoload.php'
));


$content = trim(file_get_contents("php://input"));
$data = json_decode($content, true);
if(!empty($data)) {

    $apiKey = $settings['api_key'];
    $salt = $settings['salt'];
    $client = new Client($apiKey, $salt);
    $client->setRequestedUrl($data['url']);
    if(!empty($_REQUEST['ref'])) {
        $client->setReferringUrl($data['ref']);
    }
    $loggingEnabled = $settings['logging_enabled'] === true;
    if ($loggingEnabled) {
        $logger = new Logger();
        $client->setLogger($logger);
    }

    $client->logPageViewAsync();
}

ob_start();

header("HTTP/1.1 204 No Content");
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

ob_end_flush(); //now the headers are sent
die;