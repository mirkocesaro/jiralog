<?php declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\Api\Jira;

class Search extends AbstractApi
{
    /**
     * @param string $jql
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function execute(string $jql): array
    {
        $client = $this->getClient();

        $response = $client->request(
            'POST',
            '/rest/api/2/search',
            [
                'body' => json_encode(
                    [
                        'jql' => $jql,
                        'fields' => [
                            'key',
                            'status',
                            'updated',
                            'timespent',
                            'summary',
                            'project'
                        ]
                    ]
                )
            ]
        );

        $body = $response->getBody()->getContents();

        return json_decode($body, true);
    }
}