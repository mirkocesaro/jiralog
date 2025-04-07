<?php declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\Tempo;

use Dotenv\Dotenv;
use GuzzleHttp\Exception\RequestException;
use MirkoCesaro\JiraLog\Console\Api\Jira\Search;
use MirkoCesaro\JiraLog\Console\Api\Tempo\Worklog;
use MirkoCesaro\JiraLog\Console\Utils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
            ->addArgument('end_date', InputArgument::OPTIONAL, 'End Date', null)
            ->addOption('silent', 's', InputOption::VALUE_NONE, 'Silent')
            ->addOption('no-extract', 'N', InputOption::VALUE_NONE, "Show logs without exporting");
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
        $api = new Worklog(
            $_SERVER['TEMPO_ENDPOINT'],
            $_SERVER['TOKEN']
        );

        $date = $input->getArgument('date');
        $endDate = $input->getArgument('end_date') ?? $date;
        $silentMode = $input->getOption('silent');

        $options = [
            'from' => $date,
            'to' => $endDate
        ];

        try {

            $results = [];

            do {

                $body = $api->getByUser($_SERVER['AUTHOR_ACCOUNT_ID'], $options);
                $results = array_merge($results, $body['results']);
                if(empty($body['metadata']['next'])) {
                    break;
                }

                $options['offset'] = $body['metadata']['offset'] + $body['metadata']['limit'];

            } while(1);

        } catch (RequestException $exception) {
            if($exception->hasResponse()) {
                $response = $exception->getResponse();

                if($response->getStatusCode() === 401) {
                    $output->writeln("Errore di autenticazione. Verifica che il token TEMPO sia corretto e non scaduto!");
                }

                $rawBody = $exception->getResponse()->getBody()->getContents();
                $responseBody = json_decode($rawBody, true);

                if(!empty($responseBody['errors'])) {
                    foreach($responseBody['errors'] as $error) {
                        $output->writeln("<error>" . $error['message'] . "</error>");
                    }
                } elseif(!empty($responseBody['errorMessages'])) {
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
                'time' => $result['timeSpentSeconds'],
                'date' => $result['startDate'],
                'start' => \DateTime::createFromFormat("H:i:s", $result['startTime'])->format('H:i'),
                'end' => $endTime->format('H:i'),
                'formattedTime' => Utils::formatTime($result['timeSpentSeconds']),
                'description' => $result['description']
            ];

            return $result;

        }, $results);

        $results = array_values(array_filter($results, fn($issue) => strtotime($issue['date']) >= strtotime($date)));
        $total = array_reduce($results, fn($total, $issue) => $total + $issue['time'], 0);

        $issues = array_reduce($results, function($issues, $log) {
            if(!in_array($log['issue_id'], $issues)) {
                $issues[] = $log['issue_id'];
            }

            return $issues;

        }, []);

        $issues = array_fill_keys($issues, null);

        try {


            foreach($this->getJiraSearch()->execute(sprintf("id in (%s)", implode(',', array_keys($issues))))['issues'] as $issue) {

                $issues[$issue['id']] = [
                    'id' => $issue['id'],
                    'key' => $issue['key'],
                    'summary' => $issue['fields']['summary'],
                    'project_key' => $issue['fields']['project']['key']
                ];
            }

        } catch (RequestException $exception) {

            if($exception->hasResponse()) {

                $rawBody = $exception->getResponse()->getBody()->getContents();
                $responseBody = json_decode($rawBody, true);

                if(!empty($responseBody['errors'])) {
                    foreach($responseBody['errors'] as $error) {
                        $output->writeln("<error>" . $error['message'] . "</error>");
                    }
                } elseif(!empty($responseBody['errorMessages'])) {
                    foreach ($responseBody['errorMessages'] as $errorMessage) {
                        $output->writeln("<error>" . $errorMessage . "</error>");
                    }
                }

                return 1;
            }
        }

        $results = array_map(function($result) use($issues){

            $issue = $issues[$result['issue_id']];
            if($issue) {
                $issueKey = explode(' ', $issue['summary'])[0];
            }
            unset($result['issue_id']);

            return $result;

        }, $results);


        $table = new Table($output);
        $table->setHeaders(array_keys($results[0]))
            ->setRows($results)
            ->setColumnMaxWidth(7, 100)
            ->setFooterTitle("Totale: " . Utils::formatTime($total))
            ->render();

        return 0;
    }

}