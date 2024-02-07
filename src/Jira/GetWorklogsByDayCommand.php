<?php
declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\Jira;

use Dotenv\Dotenv;
use GuzzleHttp\Exception\RequestException;
use MirkoCesaro\JiraLog\Console\Api\Jira\IssueWorklog;
use MirkoCesaro\JiraLog\Console\Api\Jira\Search;
use MirkoCesaro\JiraLog\Console\Utils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetWorklogsByDayCommand extends Command
{
    protected static $defaultName = 'jira:daily-worklogs';
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
            ->addArgument('day', InputArgument::OPTIONAL, 'Day', date('Y-m-d'))
            ->addArgument('user', InputArgument::OPTIONAL, 'User');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configuration = [
            'base_url' => $_SERVER["ADEO_JIRA_ENDPOINT"],
            'bearer_token' => $_SERVER["ADEO_JIRA_BEARER_TOKEN"],
        ];
        $jiraEmail = $_SERVER['ADEO_JIRA_EMAIL'];

        $api = new IssueWorklog($configuration);
        $issueApi = new Search($configuration);

        $day = $input->getArgument("day");
        $user = $input->getArgument("user");
        $query = sprintf("worklogDate = %s and worklogAuthor = %s", $day, $user ?? "currentUser()");

        $issues = $issueApi->execute($query, ['fields' => ['summary']]);

        if(empty($issues['issues'])) {
            die("Non risultano worklogs per la data e l'utente specificati!\n");
        }

        $issueKeys = array_map(fn($issue) => $issue['key'], $issues['issues']);

        $output->writeln(sprintf("Issue con worklogs per la data specificata: %s", implode(', ', $issueKeys)));

        $worklogs = [];

        foreach($issueKeys as $issueKey) {

            try {

                $output->writeln(sprintf("Estraggo worklogs per %s...", $issueKey));

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

            $issueWorklogs = array_map(function ($worklog) use ($issueKey) {

                return [
                    'issue' => $issueKey,
                    'started' => (new \DateTime($worklog['started']))
                        ->setTimezone(new \DateTimeZone("Europe/Rome"))
                        ->format('d/m/Y H:i'),
                    'worklogId' => $worklog['id'],
                    'author' => $worklog['author']['displayName'],
                    'author_email' => $worklog['author']['emailAddress'],
                    'timeSpent' => $worklog['timeSpent'],
                    'timeSpentSeconds' => $worklog['timeSpentSeconds'],

                ];

            },
                array_filter($response['worklogs'], function ($worklog) use ($jiraEmail, $day, $user) {

                    $isValidUser = $user ?
                        ($worklog['author']['name'] == $user) :
                        $worklog['author']['emailAddress'] == $jiraEmail;

                    return $isValidUser &&
                        (new \DateTime($worklog['started']))
                            ->setTimezone(new \DateTimeZone("Europe/Rome"))
                            ->format('Y-m-d') == $day;

                })


            );

            $worklogs = array_merge($worklogs, $issueWorklogs);

        }

        usort($worklogs, fn($a, $b) => $a['started'] <=> $b['started']);

        $total = array_reduce($worklogs, fn($total, $issue) => $total + $issue['timeSpentSeconds'], 0);

        $headers = array_keys($worklogs[0]);

        $table = new Table($output);
        $table->setHeaders($headers)
            ->setFooterTitle("Totale: " . Utils::formatTime($total))
            ->setRows($worklogs);

        $table->render();

        $output->writeln("");


        return 0;
    }
}

