<?php
declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\Tempo\Worklog;

use Dotenv\Dotenv;
use GuzzleHttp\Client as HttpClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
            ->addArgument('comment', InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $date = $input->getArgument('date');
        $startTime = $input->getArgument('from');
        $endTime = $input->getArgument('to');
        $issue = $input->getArgument('issue');
        $comment = $input->getArgument('comment') ?: '';


        $log = LogMessage::createLog($issue, $date, $startTime, $endTime, $comment, $_SERVER['AUTHOR_ACCOUNT_ID']);

        $client = new HttpClient([
            'base_uri' => $_SERVER['TEMPO_ENDPOINT'],
            'timeout'  => 5.0,
            'headers' => [
                'Authorization' => 'Bearer '.$_SERVER['TOKEN'],
                'Content-Type' => 'application/json',
            ]
        ]);

        $response = $client->request(
            'POST',
            '/core/3/worklogs',
            ['json'=>$log->toArray()]
        );

        //$output->writeln($response->getStatusCode());
        //$output->writeln($response->getBody());

        return Command::SUCCESS;

        // on error
        // return Command::FAILURE;

        // on invalid argument
        // return Command::INVALID
    }
}
