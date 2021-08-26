<?php

declare(strict_types=1);


namespace MirkoCesaro\JiraLog\Console\Tempo\Worklog;

use MirkoCesaro\JiraLog\Console\Exception\NotValidLogException;

class LogMessage
{
    protected string $issueKey;
    protected int $timeSpentSeconds;
    protected $startDate;
    protected $startTime;
    protected $description;
    protected $authorAccountId;

    //{
    //  "issueKey": "DUM-1",
    //  "timeSpentSeconds": 3600,
    //  "billableSeconds": 5200,
    //  "startDate": "2017-02-06",
    //  "startTime": "20:06:00",
    //  "description": "Investigating a problem with our external database system", // optional depending on setting in Tempo Admin
    //  "authorAccountId": "1111aaaa2222bbbb3333cccc",
    //  "remainingEstimateSeconds": 7200, // optional depending on setting in Tempo Admin
    //  "attributes": [
//    {
//        "key": "_EXTERNALREF_",
//      "value": "EXT-32548"
//    },
//    {
//        "key": "_COLOR_",
//      "value": "green"
//    }
    //  ]
    //}
    public function __construct(
        string $issueKey,
        \DateTime $startDate,
        \DateTime $startTime,
        int $timeSpentSeconds,
        string $description,
        string $authorAccountId
    ) {
        $this->issueKey = $issueKey;
        $this->startDate = $startDate;
        $this->timeSpentSeconds = $timeSpentSeconds;
        $this->description = $description;
        $this->authorAccountId = $authorAccountId;
        $this->startTime = $startTime;
    }

    public static function createLog(
        string $issueKey,
        string $date,
        string $startTime,
        string $endTime,
        string $comment,
        string $authorAccountId
    ):self {
        $date = \DateTime::createFromFormat('Y-m-d', $date);

        $timeStart = \DateTime::createFromFormat('Hi', $startTime);
        $timeEnd = \DateTime::createFromFormat('Hi', $endTime);
        if (!$date || !$timeStart|| !$timeEnd) {
            throw new NotValidLogException('Not valid date');
        }
        $timeDiff = $timeEnd->diff($timeStart);
        $seconds = $timeDiff->h*60*60 + $timeDiff->i*60;

        if ($seconds<=0) {
            throw new NotValidLogException('Log time has to be greater then zero');
        }

        return new self(
            $issueKey,
            $date,
            $timeStart,
            $seconds,
            $comment,
            $authorAccountId
        );
    }

    public function toArray(): array
    {
        return [
            "issueKey"=> $this->issueKey,
            "timeSpentSeconds" =>$this->timeSpentSeconds,
            "startDate"=> $this->startDate->format('Y-m-d'),
            "startTime" => $this->startTime->format('H:i:00'),
            "description"=> $this->description,
            "authorAccountId"=> $this->authorAccountId
        ];
    }
}
