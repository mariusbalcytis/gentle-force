<?php

namespace Maba\GentleForce\Tests\Redis\Result;

use Maba\GentleForce\Redis\Result\CheckAndIncreaseResult;
use PHPUnit_Framework_TestCase as TestCase;

class CheckAndIncreaseResultTest extends TestCase
{
    public function testCreateForAvailableUsages()
    {
        $result = CheckAndIncreaseResult::createForAvailableUsages(2);
        $this->assertFalse($result->isLimitReached());
        $this->assertSame(2, $result->getUsagesAvailable());
    }

    public function testCreateForReachedLimit()
    {
        $result = CheckAndIncreaseResult::createForReachedLimit(60);
        $this->assertTrue($result->isLimitReached());
        $this->assertSame(60, $result->getWaitForInSeconds());
    }
}
