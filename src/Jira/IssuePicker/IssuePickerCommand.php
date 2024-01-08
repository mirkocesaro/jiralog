<?php
declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\Jira\IssuePicker;

use Dotenv\Dotenv;
use GuzzleHttp\Exception\RequestException;
use MirkoCesaro\JiraLog\Console\Api\Jira\IssueWorklog;
use MirkoCesaro\JiraLog\Console\Api\Jira\Search;
use MirkoCesaro\JiraLog\Console\Api\Jira\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IssuePickerCommand extends Command
{
    protected static $defaultName = 'jira:issue-picker';
    protected Dotenv $dotEnv;

    public function __construct(string $name = null)
    {
        parent::__construct($name);

        $this->dotEnv = Dotenv::createImmutable(
            __DIR__ .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '..'
        );

        $this->dotEnv->load();
        $this->dotEnv->required("JIRA_TOKEN");
        $this->dotEnv->required("JIRA_EMAIL");
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Search for Issue')
            ->addArgument('query', InputArgument::REQUIRED, 'Query')
            ->addOption('jql', 'j', InputOption::VALUE_NONE, "Query is JQL" )
            ->addOption('prefix', 'p', InputOption::VALUE_OPTIONAL, "ENV Auth Prefix" );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $prefix = $input->getOption("prefix");
        if($prefix) $prefix .= "_";

        $api = new Search([
            'base_url' => $_SERVER["{$prefix}JIRA_ENDPOINT"],
            'username' => $_SERVER["{$prefix}JIRA_EMAIL"],
            'password' => $_SERVER["{$prefix}JIRA_TOKEN"],
            'bearer_token' => $_SERVER["{$prefix}JIRA_BEARER_TOKEN"],
        ]);

        $query = $input->getArgument('query');
        if($input->getOption('jql')) {
            $jql = $query;
        } else {
            $jql = [];
            foreach(explode(' ', $query) as $term) {
                if(empty(trim($term))) {
                    continue;
                }
                $jql[] = sprintf('text ~ "%s*"', $term);
            }
            $jql = implode(' and ', $jql);

        }

        try {

            $response = $api->execute($jql);

        } catch (RequestException $exception) {
            if($exception->hasResponse()) {
                $responseBody = json_decode($exception->getResponse()->getBody()->getContents(), true);

                foreach($responseBody['errorMessages'] as $errorMessage) {
                    $output->writeln("<error>" . $errorMessage . "</error>");
                }
                return 1;
            }
            die($exception->getMessage());
        }

        $issues = array_map(function ($issue) {

            return [
                'key' => $issue['key'],
                'status' => $issue['fields']['status']['name'],
                'updated' => (new \DateTime($issue['fields']['updated']))->format("d/m/Y H:i:s"),
                'timeSpent' => $issue['fields']['timespent'],
                'summary' => $issue['fields']['summary'],
            ];

        }, $response['issues'] ?? []);

        if(!count($issues)) {
            $output->writeln("Nessun risultato trovato!");
            return 0;
        }

        $headers = array_keys($issues[0]);

        $table = new Table($output);
        $table->setHeaders($headers)
            ->setRows($issues);

        $table->render();

        return 0;
    }
}

