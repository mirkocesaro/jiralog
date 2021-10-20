<?php

declare(strict_types=1);

namespace MirkoCesaro\JiraLog\UTest\Tempo\Worklog;

use MirkoCesaro\JiraLog\Console\Exception\NotValidLogException;
use MirkoCesaro\JiraLog\Console\Tempo\Worklog\LogMessage;
use PHPUnit\Framework\TestCase;

class LogMessageTest extends TestCase
{
    public function testCreateLogFromInput(): void
    {
        $log = LogMessage::createLog(
            'TEST-73',
            '2021-10-1',
            '1030',
            '1145',
            'test comment',
            [],
            'userid'
        );

        $requestPayload = $log->toArray();

        $this->assertSame('TEST-73', $requestPayload['issueKey']);
        $this->assertSame('2021-10-01', $requestPayload['startDate']);
        $this->assertSame('10:30:00', $requestPayload['startTime']);
        $this->assertSame(4500, $requestPayload['timeSpentSeconds']);
        $this->assertSame('test comment', $requestPayload['description']);
        $this->assertSame('userid', $requestPayload['authorAccountId']);
        $this->assertSame([], $requestPayload['attributes']);
    }


    public function testLogFinishedAfterMidnight(): void
    {
        $this->expectException(NotValidLogException::class);

        $log = LogMessage::createLog(
            'TEST-73',
            '2021-10-1',
            '2100',
            '0100',
            'test comment',
            [],
            'userid'
        );
    }

    public function testWrongInput():void
    {
        $this->expectException(NotValidLogException::class);

        LogMessage::createLog('', '', '', '', '', [], '');
    }

    public function testZeroSecondsToLog():void
    {
        $this->expectException(NotValidLogException::class);

        LogMessage::createLog(
            'TEST-73',
            '2021-1-1',
            '1000',
            '1000',
            'Tet',
            [],
            'user-1'
        );
    }

    public function testCreateLogFromInputWithAttributes(): void
    {
        $log = LogMessage::createLog(
            'TEST-73',
            '2021-10-1',
            '1030',
            '1145',
            'test comment',
            ['firstKey:firstValue','secondKey:secondValue'],
            'userid'
        );

        $requestPayload = $log->toArray();

        $this->assertSame('TEST-73', $requestPayload['issueKey']);
        $this->assertSame('2021-10-01', $requestPayload['startDate']);
        $this->assertSame('10:30:00', $requestPayload['startTime']);
        $this->assertSame(4500, $requestPayload['timeSpentSeconds']);
        $this->assertSame('test comment', $requestPayload['description']);
        $this->assertSame('userid', $requestPayload['authorAccountId']);
        $this->assertSame([
            [
                'key'=> 'firstKey',
                'value' =>'firstValue'
            ],
            [
                'key'=> 'secondKey',
                'value' =>'secondValue'
            ]
        ], $requestPayload['attributes']);
    }
}
