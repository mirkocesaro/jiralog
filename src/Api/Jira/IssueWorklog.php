<?php declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\Api\Jira;

use GuzzleHttp\Exception\RequestException;

class IssueWorklog extends AbstractApi
{
    public function get(string $issueIdOrKey, array $options = []): array
    {
        $client = $this->getClient();

        $response = $client->request(
            "GET",
            sprintf("/rest/api/2/issue/%s/worklog?startedAfter=1737590400000", $issueIdOrKey),
            [
                //'query' => $options
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    public function delete(string $issueIdOrKey, string $workLogId): ?array
    {
        $client = $this->getClient();

        $url = sprintf("/rest/api/2/issue/%s/worklog/%s", $issueIdOrKey, $workLogId);
        $url .= "?" . http_build_query([
                'adjustEstimate' => 'leave',
                'notifyUsers' => 'false'
            ]);

        try {
            $response = $client->request(
                "DELETE",
                $url
            );
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $exception) {
            if ($exception->hasResponse()) {
                return json_decode($exception->getResponse()->getBody()->getContents(), true);
            }
            throw $exception;
        }
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

        dd($url, $body);

        $response = $client->request(
            'POST',
            $url,
            [
                'body' => json_encode($body)
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    public function getById(string $issueIdOrKey, string $worklogId): array
    {
        $client = $this->getClient();

        $url = sprintf("/rest/api/2/issue/%s/worklog/%s", $issueIdOrKey, $worklogId);

        $response = $client->request(
            'GET',
            $url,
        );
        return json_decode($response->getBody()->getContents(), true);

    }

    public function update(string $issueIdOrKey, string $worklogId, array $body)
    {
        $client = $this->getClient();

        $url = sprintf("/rest/api/2/issue/%s/worklog/%s", $issueIdOrKey, $worklogId);
        $url .= "?" . http_build_query([
                'adjustEstimate' => 'leave',
                'notifyUsers' => 'false'
            ]);

        $response = $client->request(
            'PUT',
            $url,
            [
                'body' => json_encode($body)
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }
}