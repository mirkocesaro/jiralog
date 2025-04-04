<?php
declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\Tempo\Worklog;

use Dotenv\Dotenv;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use MirkoCesaro\JiraLog\Console\Exception\NotValidLogException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use MirkoCesaro\JiraLog\Console\Api\Jira\Issue;

class LogCommand extends Command
{
    protected static $defaultName = 'tempo:log';
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

        //ask for requirement variables in env file to track new worklog
        $this->dotEnv->required('TEMPO_ENDPOINT')->notEmpty();
        $this->dotEnv->required('TOKEN')->notEmpty();
        $this->dotEnv->required('AUTHOR_ACCOUNT_ID')->notEmpty();
    }


    protected function configure(): void
    {
        $this
            ->setDescription('Create a new worklog')
            ->setHelp('This command allows you to create a new worklog')
            ->addArgument('date', InputArgument::REQUIRED, 'Date of the work to log ')
            ->addArgument('from', InputArgument::REQUIRED, 'Start time')
            ->addArgument('to', InputArgument::REQUIRED, 'End time')
            ->addArgument('issue', InputArgument::REQUIRED, 'Issue id')
            ->addArgument('comment', InputArgument::OPTIONAL)
            ->addOption(
                'attributes',
                'a',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Add custom attributes to your log',
                []
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $date = $input->getArgument('date');
        $startTime = $input->getArgument('from');
        $endTime = $input->getArgument('to');
        $issue = $input->getArgument('issue');
        $comment = $input->getArgument('comment') ?: '';
        $attributes = $input->getOption('attributes');

        try {
            $issueJiraApi = new Issue([
                'base_url' => $_SERVER["JIRA_ENDPOINT"],
                'username' => $_SERVER["JIRA_EMAIL"],
                'password' => $_SERVER["JIRA_TOKEN"],
                'bearer_token' => $_SERVER["JIRA_BEARER_TOKEN"],
            ]);

            try {
                $issueId = $issueJiraApi->execute($issue);
            } catch (RequestException $exception) {
                if($exception->hasResponse()) {
                    $responseBody = json_decode($exception->getResponse()->getBody()->getContents(), true);

                    foreach($responseBody['errorMessages'] ?? ["Errore del Server"] as $errorMessage) {
                        $output->writeln("<error>" . $errorMessage . "</error>");
                    }
                    return 1;
                }
                die($exception->getMessage());
            }

            if (!$issueId) {
                $output->writeln('No issue id found');
                return Command::FAILURE;
            }

            $log = LogMessage::createLog($issueId, $date, $startTime, $endTime, $comment, $attributes, $_SERVER['AUTHOR_ACCOUNT_ID']);

            $client = new HttpClient([
                'base_uri' => $_SERVER['TEMPO_ENDPOINT'],
                'timeout' => 10.0,
                'headers' => [
                    'Authorization' => 'Bearer ' . $_SERVER['TOKEN'],
                    'Content-Type' => 'application/json',
                ]
            ]);

            $response = $client->request(
                'POST',
                '/4/worklogs',
                ['json' => $log->toArray()]
            );
            return Command::SUCCESS;
        } catch (NotValidLogException $e) {
            $output->writeln($e->getMessage());
            return Command::INVALID;
        } catch (RequestException $e) {
            $output->writeln($e->getMessage());
            $output->writeln([
                'Log Command Exception',
                '======================',
                json_encode($log->toArray()),
                '======================',
            ]);
            return Command::FAILURE;
        }
    }
}
