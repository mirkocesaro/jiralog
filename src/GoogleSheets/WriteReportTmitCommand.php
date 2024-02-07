<?php declare(strict_types=1);

namespace MirkoCesaro\JiraLog\Console\GoogleSheets;

use Dotenv\Dotenv;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WriteReportTmitCommand extends Command
{
    protected static $defaultName = 'gsheets:write-report-tmit';
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
//        $this->dotEnv->required("ADEO_JIRA_BEARER_TOKEN");
//        $this->dotEnv->required("ADEO_JIRA_EMAIL");
    }

    protected function configure(): void
    {
        $this
            ->setDescription('');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $client = new \Google_Client();
        $client->setApplicationName("Jiralog");
        $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
        $client->setAccessType("offline");
        $client->setAuthConfig(BASE_PATH . DIRECTORY_SEPARATOR. $_SERVER['TECNOMAT_GOOGLE_SHEETS_AUTH_JSON']);

        $service = new \Google_Service_Sheets($client);

        $spreadsheetId = $_SERVER['TECNOMAT_GOOGLE_SHEETS_SPREADSHEET_ID'];

        $fh = fopen('report_tmit.csv', 'r');
        $data = [];
        while($row = fgetcsv($fh)) {
            if(empty($head)) {
                $data[] = $row;
                $head = $row;
                continue;
            }

            $row[2] = (float)($row[2]);
            $row[33] = (float)($row[33]);
            $row[34] = (float)($row[34]);
            $row[35] = (float)($row[35]);

            $data[] = $row;
        }

        $valueRange = new \Google_Service_Sheets_ValueRange();
        $valueRange->setValues(array_values($data));
        $range = $_SERVER['TECNOMAT_GOOGLE_SHEETS_UPDATE_RANGE']; // the service will detect the last row of this sheet
        $options = ['valueInputOption' => 'USER_ENTERED'];
        $service->spreadsheets_values->update($spreadsheetId, $range, $valueRange, $options);

        return Command::SUCCESS;
    }
}