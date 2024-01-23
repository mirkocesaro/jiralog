<?php declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\Api\Tempo;

use GuzzleHttp\Exception\GuzzleException;

class WorkAttributes extends AbstractApi
{
    /**
     * @throws GuzzleException
     */
    public function get(array $options = []): array
    {
        $client = $this->getClient();

        $response = $client->request(
            'GET',
            "/4/work-attributes",
            [
                'query' => $options
            ]
        );

        return json_decode($response->getBody()->getContents(), true);

    }

}