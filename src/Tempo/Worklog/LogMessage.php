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
    protected $attributes;

    public function __construct(
        string $issueKey,
        \DateTime $startDate,
        \DateTime $startTime,
        int $timeSpentSeconds,
        string $description,
        array $attributes,
        string $authorAccountId
    ) {
        $this->issueKey = $issueKey;
        $this->startDate = $startDate;
        $this->timeSpentSeconds = $timeSpentSeconds;
        $this->description = $description;
        $this->authorAccountId = $authorAccountId;
        $this->startTime = $startTime;
        $this->attributes = $attributes;
    }

    public static function createLog(
        string $issueKey,
        string $date,
        string $startTime,
        string $endTime,
        string $comment,
        array $attributes,
        string $authorAccountId
    ):self {
        $date = \DateTime::createFromFormat('Y-m-d', $date);

        $timeStart = \DateTime::createFromFormat('Hi', $startTime);
        $timeEnd = \DateTime::createFromFormat('Hi', $endTime);
        if (!$date || !$timeStart|| !$timeEnd) {
            throw new NotValidLogException('Not valid date');
        }

        if ($timeEnd < $timeStart) {
            throw new NotValidLogException('Start time must be greater that end time.');
        }
        $timeDiff = $timeEnd->diff($timeStart);
        $seconds = $timeDiff->h*60*60 + $timeDiff->i*60;

        if ($seconds<=0) {
            throw new NotValidLogException('Log time has to be greater then zero');
        }
        
        $attributeList = [];

        foreach ($attributes as $attribute) {
            if (strpos($attribute, ':') === false) {
                throw new NotValidLogException('Not valid attribute: ' . $attribute. '. You should use a string like `key:value`');
            }
            $splittedAttribute = explode(':', $attribute);

            if (count($splittedAttribute) != 2) {
                throw new NotValidLogException('Not valid attribute: ' . $attribute. '. You should use only one key and one value like `key:value`');
            }
            $attributeList[] = [
                "key"=> $splittedAttribute[0],
                "value"=> $splittedAttribute[1]
            ];
        }

        return new self(
            $issueKey,
            $date,
            $timeStart,
            $seconds,
            $comment,
            $attributeList,
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
            "authorAccountId"=> $this->authorAccountId,
            "attributes"=> $this->attributes,
        ];
    }
}
