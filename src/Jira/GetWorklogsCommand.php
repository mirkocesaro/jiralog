<?php
declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\Jira;

use Dotenv\Dotenv;
use GuzzleHttp\Exception\RequestException;
use MirkoCesaro\JiraLog\Console\Api\Jira\IssueWorklog;
use MirkoCesaro\JiraLog\Console\Api\Jira\Search;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GetWorklogsCommand extends Command
{
    protected static $defaultName = 'jira:worklogs';
    protected Dotenv $dotEnv;

    public function __construct(string $name = null)
    {
        parent::__construct($name);

        $this->dotEnv = Dotenv::createImmutable(
            __DIR__ .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '..'
        );

        $this->dotEnv->load();
        $this->dotEnv->required("ADEO_JIRA_BEARER_TOKEN");
        $this->dotEnv->required("ADEO_JIRA_EMAIL");
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Get Worklogs for specified Jira issue')
            ->addArgument('issues', InputArgument::REQUIRED + InputArgument::IS_ARRAY, 'Issue Key');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $api = new IssueWorklog([
            'base_url' => $_SERVER["ADEO_JIRA_ENDPOINT"],
            'bearer_token' => $_SERVER["ADEO_JIRA_BEARER_TOKEN"],
        ]);

        $jiraEmail = $_SERVER['ADEO_JIRA_EMAIL'];

        $issues = $input->getArgument("issues");
        foreach($issues as $issueKey) {

            try {
                $response = $api->get($issueKey);

            } catch (RequestException $exception) {
                if ($exception->hasResponse()) {
                    $responseBody = json_decode($exception->getResponse()->getBody()->getContents(), true);

                    foreach ($responseBody['errorMessages'] as $errorMessage) {
                        $output->writeln("<error>" . $errorMessage . "</error>");
                    }
                    return 1;
                }
                die($exception->getMessage());
            }

            $worklogs = array_map(function ($worklog) {

                return [
                    'started' => (new \DateTime($worklog['started']))
                        ->setTimezone(new \DateTimeZone("Europe/Rome"))
                        ->format('d/m/Y H:i'),
                    'worklogId' => $worklog['id'],
                    'author' => $worklog['author']['displayName'],
                    'author_email' => $worklog['author']['emailAddress'],
                    'timeSpent' => $worklog['timeSpent'],
                    'timeSpentSeconds' => $worklog['timeSpentSeconds'],

                ];

            }, $response['worklogs']);

            $worklogs = array_values(array_filter($worklogs, fn($worklog) => $worklog['author_email'] == $jiraEmail));

            $headers = array_keys($worklogs[0]);

            $table = new Table($output);
            $table->setHeaders($headers)
                ->setHeaderTitle($issueKey)
                ->setRows($worklogs);

            $table->render();

            $output->writeln("");

        }

        return 0;
    }
}

