<?php

declare(strict_types=1);

namespace MirkoCesaro\JiraLog\UTest\Tempo\Worklog;

use MirkoCesaro\JiraLog\Console\Tempo\Worklog\LogMessage;
use PHPUnit\Framework\TestCase;

class LogMessageTest extends TestCase
{
    public function testCreateLogFromInput(): void
    {
        $log = LogMessage::createLog('TEST-73', '2021-10-1', '1030', '1145', 'test comment', 'userid');

        $requestPayload = $log->toArray();

        $this->assertSame('TEST-73', $requestPayload['issueKey']);
        $this->assertSame('2021-10-01', $requestPayload['startDate']);
        $this->assertSame('10:30:00', $requestPayload['startTime']);
        $this->assertSame(4500, $requestPayload['timeSpentSeconds']);
        $this->assertSame('test comment', $requestPayload['description']);
        $this->assertSame('userid', $requestPayload['authorAccountId']);
    }
}
