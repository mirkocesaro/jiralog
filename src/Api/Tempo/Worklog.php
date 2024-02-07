<?php declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\Api\Tempo;

class Worklog extends AbstractApi
{

    public function get(array $options = []): array
    {
        $client = $this->getClient();

        $response = $client->request(
            'GET',
            "/4/worklogs",
            [
                'query' => $options
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param string $userId
     * @param array $options
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getByUser(string $userId, array $options = []): array
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

    public function getByAccount(string $accountKey, array $options = []): array
    {
        $client = $this->getClient();

        $response = $client->request(
            'GET',
            sprintf("/4/worklogs/account/%s", $accountKey),
            [
                'query' => $options
            ]
        );

        return json_decode($response->getBody()->getContents(), true);

    }

}