<?php declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\Api\Jira;

use GuzzleHttp\Client as HttpClient;

abstract class AbstractApi
{
    private string $apiToken;
    private string $baseUrl;
    private string $authType = 'Basic';

    public function __construct(array $config)
    {
        $this->baseUrl = $config['base_url'];
        if(empty($config['bearer_token'])) {
            $this->apiToken = base64_encode(sprintf("%s:%s",
                $config['username'],
                $config['password'],
            ));
        } else {
            $this->apiToken = $config['bearer_token'];
            $this->authType = 'Bearer';
        }
    }

    protected function getAuthorization(): string
    {
        return sprintf("%s %s", $this->authType, $this->apiToken);
    }

    public function getClient() : HttpClient
    {
        return new HttpClient([
            'base_uri' => $this->baseUrl,
            'timeout' => 10.0,
            'headers' => [
                'Authorization' => $this->getAuthorization(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ]);
    }

}