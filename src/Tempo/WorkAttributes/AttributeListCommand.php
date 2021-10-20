<?php
declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\Tempo\WorkAttributes;

use Dotenv\Dotenv;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use MirkoCesaro\JiraLog\Console\Exception\NotValidLogException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AttributeListCommand extends Command
{
    protected static $defaultName = 'tempo:work-attributes';
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
    }


    protected function configure(): void
    {
        $this
            ->setDescription('Get available work attribute')
            ->setHelp('This command shows the work attribute defined on tempo');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $client = new HttpClient([
                'base_uri' => $_SERVER['TEMPO_ENDPOINT'],
                'timeout' => 10.0,
                'headers' => [
                    'Authorization' => 'Bearer ' . $_SERVER['TOKEN'],
                    'Content-Type' => 'application/json',
                ]
            ]);

            $response = $client->request(
                'GET',
                '/core/3/work-attributes'
            );

            $attributes =  json_decode($response->getBody()->getContents(), true);

            if (!is_array($attributes) || !array_key_exists('results', $attributes)) {
                $output->writeln('Work attributes not defined');
                return Command::FAILURE;
            }

            foreach ($attributes['results'] as $attribute) {
                $output->writeln([
                    'Attribute key: '.$attribute['key'],
                    'Valid values: '.implode(',', $attribute['values']),
                    '======================'
                ]);
            }
            return Command::SUCCESS;
        } catch (RequestException $e) {
            $output->writeln($e->getMessage());
            return Command::FAILURE;
        }
    }
}
