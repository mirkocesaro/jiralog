<?php

declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\Api\Jira;

class Issue extends AbstractApi
{
    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function execute(string $issueKey): ?string
    {
        $client = $this->getClient();

        $response = $client->request(
            'GET',
            '/rest/api/3/issue/' . $issueKey,
        );

        $body = $response->getBody()->getContents();
        $body = json_decode($body, true);

        return $body['id'] ?? null;
    }
}