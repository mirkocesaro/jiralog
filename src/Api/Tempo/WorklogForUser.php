<?php declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\Api\Tempo;

class WorklogForUser extends AbstractApi
{
    /**
     * @param string $userId
     * @param array $options
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get(string $userId, array $options = []): array
    {
        $client = $this->getClient();

        $response = $client->request(
            'GET',
            sprintf("/4/worklogs/user/%s", $userId),
            [
                'query' => $options
            ]
        );

        return json_decode($response->getBody()->getContents(), true);

    }

}