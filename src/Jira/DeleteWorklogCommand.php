<?php
declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\Jira;

use Dotenv\Dotenv;
use MirkoCesaro\JiraLog\Console\Api\Jira\IssueWorklog;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteWorklogCommand extends Command
{
    protected static $defaultName = 'jira:delete-worklog';
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
            ->addArgument('issue_key', InputArgument::REQUIRED, 'ID or Issue Key')
            ->addArgument('worklog_id', InputArgument::REQUIRED, 'ID of worklog to remove');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configuration = [
            'base_url' => $_SERVER["ADEO_JIRA_ENDPOINT"],
            'bearer_token' => $_SERVER["ADEO_JIRA_BEARER_TOKEN"],
        ];

        $api = new IssueWorklog($configuration);

        $issueKey = $input->getArgument('issue_key');
        $worklogId = $input->getArgument('worklog_id');

        $output->writeln(
            sprintf("Trying to delete WorkLog %s from issue %s", $worklogId, $issueKey)
        );

        $response = $api->delete($issueKey, $worklogId);

        if (!empty($response['errorMessages'])) {
            foreach ($response['errorMessages'] as $errorMessage) {
                $output->writeln("<error>$errorMessage</error>");
            }
        }

        return 0;
    }
}

