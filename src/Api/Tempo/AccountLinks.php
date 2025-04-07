<?php declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\Api\Tempo;

use GuzzleHttp\Exception\GuzzleException;

class AccountLinks extends AbstractApi
{
    /**
     * @throws GuzzleException
     */
    public function getByProject(int $projectId, array $options = []): array
    {
        $client = $this->getClient();

        $response = $client->request(
            'GET',
            sprintf("/4/account-links/project/%s", $projectId),
            [
                'query' => $options
            ]
        );

        return json_decode($response->getBody()->getContents(), true);

    }
}