<?php declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\Tempo;

use Dotenv\Dotenv;
use GuzzleHttp\Exception\RequestException;
use MirkoCesaro\JiraLog\Console\Api\Jira\IssueWorklog;
use MirkoCesaro\JiraLog\Console\Api\Jira\Search;
use MirkoCesaro\JiraLog\Console\Api\Tempo\WorklogForUser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ExtractWorklogsCommand extends Command
{
    protected static $defaultName = 'tempo:extract-logs';
    protected Dotenv $dotEnv;

    protected array $history = [];

    public function __construct(string $name = null)
    {
        parent::__construct($name);

        $this->dotEnv = Dotenv::createImmutable(
            __DIR__ .
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
            ->setDescription('Extract Tempo Worklogs')
            ->addArgument('date', InputArgument::OPTIONAL, 'Start Date', date('Y-m-d'))
            ->addArgument('end_date', InputArgument::OPTIONAL, 'End Date', date('Y-m-d'));
    }

    protected function getJiraSearch(): Search
    {
        return new Search([
            'base_url' => $_SERVER['JIRA_ENDPOINT'],
            'username' => $_SERVER['JIRA_EMAIL'],
            'password' => $_SERVER['JIRA_TOKEN']
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $api = new WorklogForUser(
            $_SERVER['TEMPO_ENDPOINT'],
            $_SERVER['TOKEN']
        );

        $date = $input->getArgument('date');
        $endDate = $input->getArgument('end_date');

        $adeoApi = new IssueWorklog([
            'base_url' => $_SERVER['ADEO_JIRA_ENDPOINT'],
            'bearer_token' => $_SERVER['ADEO_JIRA_BEARER_TOKEN']
        ]);

        $options = [
            'from' => $date,
            'to' => $endDate
        ];

        try {

            $results = [];

            do {

                $body = $api->get($_SERVER['AUTHOR_ACCOUNT_ID'], $options);
                $results = array_merge($results, $body['results']);
                if(empty($body['metadata']['next'])) {
                    break;
                }

                $options['offset'] = $body['metadata']['offset'] + $body['metadata']['limit'];

            } while(1);

        } catch (RequestException $exception) {
            if($exception->hasResponse()) {
                $responseBody = json_decode($exception->getResponse()->getBody()->getContents(), true);

                if(!empty($responseBody['errors'])) {
                    foreach($responseBody['errors'] as $error) {
                        $output->writeln("<error>" . $error['message'] . "</error>");
                    }
                }

                if(!empty($responseBody['errorMessages'])) {
                    foreach ($responseBody['errorMessages'] as $errorMessage) {
                        $output->writeln("<error>" . $errorMessage . "</error>");
                    }
                }

                return 1;
            }
            die($exception->getMessage());
        }

        if(!count($results )) {
            die("Nessun risultato trovato\n");
        }

        $results = array_map(function($result) {

            $endTime = new \DateTime($result['startDate'] . ' ' . $result['startTime']);
            $endTime->add(\DateInterval::createFromDateString($result['timeSpentSeconds'] . " seconds"));

            $result = [
                'worklogId' => $result['tempoWorklogId'],
                'issue_id' => $result['issue']['id'],
                'key_adeo' => '',
                'time' => $result['timeSpentSeconds'],
                'date' => $result['startDate'],
                'start' => \DateTime::createFromFormat("H:i:s", $result['startTime'])->format('H:i'),
                'end' => $endTime->format('H:i'),
                'formattedTime' => $this->formatTime($result['timeSpentSeconds']),
                'description' => $result['description']
            ];

            return $result;

        }, $results);

        $results = array_values(array_filter($results, fn($issue) => strtotime($issue['date']) >= strtotime($date)));

        $issues = array_reduce($results, function($issues, $log) {
            if(!in_array($log['issue_id'], $issues)) {
                $issues[] = $log['issue_id'];
            }

            return $issues;

        }, []);

        $issues = array_fill_keys($issues, null);

        foreach($this->getJiraSearch()->execute(sprintf("id in (%s)", implode(',', array_keys($issues))))['issues'] as $issue) {

            $issues[$issue['id']] = [
                'id' => $issue['id'],
                'key' => $issue['key'],
                'summary' => $issue['fields']['summary'],
                'project_key' => $issue['fields']['project']['key']
            ];
        }

        $results = array_map(function($result) use($issues){

            $issue = $issues[$result['issue_id']];
            if($issue) {
                $issueKey = explode(' ', $issue['summary'])[0];
                if (str_contains($issueKey, "BMITFOX-") || str_contains($issueKey, "BMITB2C")) {
                    $result['key_adeo'] = $issueKey;
                }
            }
            unset($result['issue_id']);

            return $result;

        }, $results);


        $table = new Table($output);
        $table->setHeaders(array_keys($results[0]))
            ->setRows($results)
            ->setColumnMaxWidth(7, 100)
            ->render();

        $qh = new QuestionHelper();

        if(!$qh->ask(
            $input,
            $output,
            new ConfirmationQuestion("Esportare il log sul jira di adeo? <comment>[y/N]</comment>", false)
        )) {
            return 0;
        }

        $historyPath = __DIR__."/../../log_history.json";

        if(is_file($historyPath)) {
            $this->history = json_decode(file_get_contents($historyPath), true) ?? [];
        }

        $output->writeln("");

        foreach($results as $issue) {
            if(empty($issue['key_adeo'])) {
                continue;
            }


            $output->writeln(sprintf("<comment>%s - Esportazione in corso... </comment>", $issue['key_adeo']));

            if(!empty($this->history[$issue['worklogId']])) {
                $output->writeln("<info>Worklog già esportato</info>");
                continue;
            }

            if(!$qh->ask(
                $input,
                $output,
                new ConfirmationQuestion("Procedo? <comment>[y/N]</comment>", false)
            )) {
                continue;
            }

            $payload = [
                'comment' => $issue['description'],
                'started' => (new \DateTime($issue['date'] . ' ' .$issue['start'], new \DateTimeZone("Europe/Rome")))->format("Y-m-d\TH:i:s.uO"),
                'timeSpent' => $issue['formattedTime']
            ];

            $adeoWorklog = $adeoApi->create($issue['key_adeo'], $payload);

            $this->history[$issue['worklogId']] = $adeoWorklog['id'];

            $output->writeln([
                "<info>Worklog creato con ID: " . $adeoWorklog['id'] . "</info>",
                ""
            ]);
        }

        file_put_contents($historyPath, json_encode($this->history, JSON_PRETTY_PRINT));

        return 0;
    }

    public function formatTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $formattedTime = [];
        if($hours) $formattedTime[] = $hours . "h";
        if($minutes) $formattedTime[] = $minutes . "m";

        return implode(" ", $formattedTime);
    }
}