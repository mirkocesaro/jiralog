<?php declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\Api\Jira;

class User extends AbstractApi
{
    public function execute(string $username): array
    {
        $client = $this->getClient();

        $response = $client->request(
            'GET',
            '/rest/api/2/user',
            [
                'query' => [
                    'username' => $username
                ]
            ]
        );

        $body = $response->getBody()->getContents();

        return json_decode($body, true);
    }

    public function getByAccountId(string $accountId): array
    {
        $client = $this->getClient();

        $response = $client->request(
            'GET',
            '/rest/api/2/user',
            [
                'query' => [
                    'accountId' => $accountId
                ]
            ]
        );

        $body = $response->getBody()->getContents();

        return json_decode($body, true);
    }
}