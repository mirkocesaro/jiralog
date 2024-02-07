<?php declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\Api\Tempo;

use GuzzleHttp\Exception\GuzzleException;

class Account extends AbstractApi
{
    /**
     * @throws GuzzleException
     */
    public function getAccount(int $accountId, array $options = []): array
    {
        $client = $this->getClient();

        $response = $client->request(
            'GET',
            sprintf("/4/accounts/%s", $accountId),
            [
                'query' => $options
            ]
        );

        return json_decode($response->getBody()->getContents(), true);

    }

    /**
     * @throws GuzzleException
     */
    public function get(array $options = []): array
    {
        $client = $this->getClient();

        $response = $client->request(
            'GET',
            "/4/accounts",
            [
                'query' => $options
            ]
        );

        return json_decode($response->getBody()->getContents(), true);

    }

}