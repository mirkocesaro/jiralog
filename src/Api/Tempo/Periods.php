<?php declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\Api\Tempo;

use GuzzleHttp\Exception\GuzzleException;

class Periods extends AbstractApi
{
    /**
     * @throws GuzzleException
     */
    public function get(string $startDate, string $endDate): array
    {
        $client = $this->getClient();

        $response = $client->request(
            'GET',
            "/4/periods",
            [
                'query' => [
                    'from' => $startDate,
                    'to' => $endDate
                ]
            ]
        );

        return json_decode($response->getBody()->getContents(), true);

    }

}