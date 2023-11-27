<?php
namespace ScobyAnalytics;

use ScobyAnalyticsDeps\Psr\Http\Client\ClientInterface;
use ScobyAnalyticsDeps\Psr\Http\Message\RequestInterface;
use ScobyAnalyticsDeps\Psr\Http\Message\ResponseInterface;
use ScobyAnalyticsDeps\RebelCode\Psr7\Request;
use ScobyAnalyticsDeps\RebelCode\WordPress\Http\WpClient;
use ScobyAnalyticsDeps\RebelCode\WordPress\Http\WpHandler;

class HttpClient implements ClientInterface {

    public function createRequest($method, $url): Request
    {
        return new Request($method, $url);
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $client = new WpClient(new WpHandler());
        return $client->sendRequest($request);
    }
}