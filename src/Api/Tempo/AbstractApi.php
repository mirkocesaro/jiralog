<?php declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\Api\Tempo;

use GuzzleHttp\Client as HttpClient;

abstract class AbstractApi
{
    public function __construct(
        protected string $baseUrl,
        protected string $apiToken
    ) {}

    public function getClient() : HttpClient
    {
        return new HttpClient([
            'base_uri' => $this->baseUrl,
            'timeout' => 10.0,
            'headers' => [
                'Authorization' => "Bearer {$this->apiToken}",
                'Content-Type' => 'application/json',
            ]
        ]);
    }

}