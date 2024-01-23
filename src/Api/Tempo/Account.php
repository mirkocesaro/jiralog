<?php declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\Api\Tempo;

use GuzzleHttp\Exception\GuzzleException;

class Account extends AbstractApi
{
    /**
     * @throws GuzzleException
     */
    public function get(int $accountId, array $options = []): array
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

}