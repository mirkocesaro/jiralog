<?php declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\Api\Jira;

class IssueWorklog extends AbstractApi
{
    public function get(string $issueIdOrKey, array $options = []): array
    {
        $client = $this->getClient();

        $response = $client->request(
            "GET",
            sprintf("/rest/api/2/issue/%s/worklog", $issueIdOrKey),
            [
                'body' => json_encode($options)
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param string $issueIdOrKey
     * @param array $body
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function create(string $issueIdOrKey, array $body): array
    {
        $client = $this->getClient();

        $url = sprintf("/rest/api/2/issue/%s/worklog", $issueIdOrKey);
        $url .= "?" . http_build_query([
            'adjustEstimate' => 'leave',
            'notifyUsers' => 'false'
        ]);

        $response = $client->request(
            'POST',
            $url,
            [
                'body' => json_encode($body)
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }
}