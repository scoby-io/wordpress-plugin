<?php
/**
 * Plugin Name: Scoby Analytics Privacy Proxy
 * Description: Preserves your visitors' privacy when using scoby analytics' client-side integration.
 * Author: scoby UG
 * Version: 1.0
 * Author URI: https://scoby.io
 */

if ( ! defined( 'ABSPATH' ) ) exit;

use ScobyAnalytics\HttpClient;
use ScobyAnalytics\Logger;
use ScobyAnalyticsDeps\Scoby\Analytics\Client;

$settings = get_option('scoby_analytics_options');

$proxyEnabled = !empty($settings['integration_type']) && $settings['integration_type'] === 'CLIENT';
$proxyEndpoint = !empty($settings['proxy_endpoint']) ? "/" . $settings['proxy_endpoint'] : null;

$uri = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$path = explode("?", $uri)[0];

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


$content = \trim(\file_get_contents("php://input"));
$input = \json_decode($content);

$filters = [
    'url'=>FILTER_VALIDATE_URL,
    'ref'=>FILTER_VALIDATE_URL,
];
$options = [
    'url'=> [
        'flags'=>FILTER_NULL_ON_FAILURE
    ],
    'ref'=> [
        'flags'=>FILTER_NULL_ON_FAILURE
    ],
];
$data = [];
foreach($input as $key => $value) {
    $data[$key] = filter_var($value, $filters[$key], $options[$key]);
}

if(!empty($data)) {

    $apiKey = $settings['api_key'];
    $salt = $settings['salt'];
    $client = new Client($apiKey, $salt);

    $httpClient = new HttpClient();
    $client->setHttpClient($httpClient);

    $requestedUrl = filter_var($data['url'], FILTER_VALIDATE_URL);
    $client->setRequestedUrl($data['url']);

    if(!empty($data['ref'])) {
        $referringUrl = filter_var($data['ref'], FILTER_VALIDATE_URL);
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