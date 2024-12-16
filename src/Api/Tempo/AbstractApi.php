<?php declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\Api\Tempo;

use Dotenv\Dotenv;
use GuzzleHttp\Client as HttpClient;

abstract class AbstractApi
{
    protected DotEnv $env;

    public function __construct(
        protected string $baseUrl,
        protected string $apiToken
    ) {
        $this->env = Dotenv::createImmutable(
            __DIR__ .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '..'
        );

        $this->env->load();
    }

    public function getClient() : HttpClient
    {
        return new HttpClient([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->getTimeout(),
            'headers' => [
                'Authorization' => "Bearer {$this->apiToken}",
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    public function getRequest($url)
    {
        return $this->getClient()->get($url);
    }

    public function getTimeout(): int
    {
        return (int)($_SERVER['TEMPO_API_TIMEOUT'] ?? 10);
    }
}