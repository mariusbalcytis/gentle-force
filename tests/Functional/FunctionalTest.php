<?php

namespace Maba\GentleForce\Tests\Functional;

use Maba\GentleForce\Exception\RateLimitReachedException;
use Maba\GentleForce\RateLimit\UsageRateLimit;
use Maba\GentleForce\RateLimitProvider;
use Maba\GentleForce\Throttler;
use PHPUnit_Framework_TestCase as TestCase;
use Predis\Client;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

class FunctionalTest extends TestCase
{
    const USE_CASE_KEY = 'use_case_key';
    const ID = 'user1';
    const ANOTHER_ID = 'user2';
    const ERROR_CORRECTION_PERIOD_MS = 60;

    /**
     * @var Throttler
     */
    private $throttler;

    /**
     * @var StopwatchEvent
     */
    private $event;

    public function testWithoutBucketedUsages()
    {
        $this->setUpThrottler([
            new UsageRateLimit(10, 4),
        ]);

        $this->assertUsagesValid(10);

        $this->sleepUpTo(400);

        $this->assertUsagesValid(1);
    }

    public function testWithBucketedUsages()
    {
        $this->setUpThrottler([
            (new UsageRateLimit(10, 4))->setBucketedUsages(10),
        ]);

        $this->assertUsagesValid(20);

        $this->sleepUpTo(400);

        $this->assertUsagesValid(1);
    }

    public function testWithSeveralLimits()
    {
        $this->setUpThrottler([
            (new UsageRateLimit(2, 0.6))->setBucketedUsages(3),
            (new UsageRateLimit(1, 1))->setBucketedUsages(5),
        ]);

        $this->assertUsagesValid(5);

        $this->sleepUpTo(300);
        $this->assertUsagesValid(1);

        $this->sleepUpTo(900);
        $this->assertUsagesValid(0);

        $this->sleepUpTo(1000);
        $this->assertUsagesValid(1);
    }

    public function testWithDecreasing()
    {
        $this->setUpThrottler([
            (new UsageRateLimit(2, 0.6))->setBucketedUsages(3),
            (new UsageRateLimit(1, 1))->setBucketedUsages(5),
        ]);

        $this->checkAndIncrease()->decrease();
        $this->assertUsagesValid(5);

        $this->sleepUpTo(300);
        $this->checkAndIncrease()->decrease();
        $this->assertUsagesValid(1);

        $this->sleepUpTo(900);
        $this->assertUsagesValid(0);

        $this->sleepUpTo(1000);
        $this->checkAndIncrease()->decrease();
        $this->assertUsagesValid(1);
    }

    public function testWithReset()
    {
        $this->setUpThrottler([
            (new UsageRateLimit(2, 0.6))->setBucketedUsages(3),
            (new UsageRateLimit(1, 1))->setBucketedUsages(5),
        ]);

        $this->assertUsagesValid(5);
        $this->reset();
        $this->assertUsagesValid(5);

        $this->sleepUpTo(300);
        $this->assertUsagesValid(1);

        $this->reset();
        $this->assertUsagesValid(5);

        $this->reset(self::ANOTHER_ID);
        $this->assertUsagesValid(0);
    }

    private function setUpThrottler($rateLimits)
    {
        $prefix = 'functional_test_' . microtime();

        $rateLimitProvider = new RateLimitProvider();
        $rateLimitProvider->registerRateLimits(self::USE_CASE_KEY, $rateLimits);

        $this->throttler = new Throttler(new Client([
            'host' => isset($_ENV['REDIS_HOST']) ? $_ENV['REDIS_HOST'] : 'localhost',
        ]), $rateLimitProvider, $prefix);

        $this->event = (new Stopwatch())->start('');
    }

    private function assertUsagesValid($countOfUsages)
    {
        for ($i = 0; $i < $countOfUsages; $i++) {
            $this->checkAndIncrease();
        }

        $this->checkAndIncrease(self::ANOTHER_ID);
        $this->addToAssertionCount(1);  // does not fail if other identifier was passed

        $this->assertUsageInvalid();
    }

    private function assertUsageInvalid()
    {
        try {
            $this->checkAndIncrease();

            $this->fail('Should have failed, but RateLimitReachedException was not thrown');
        } catch (RateLimitReachedException $exception) {
            $this->addToAssertionCount(1);  // should fail as 0.2 seconds did not yet pass
        }
    }

    private function checkAndIncrease($id = self::ID)
    {
        return $this->throttler->checkAndIncrease(self::USE_CASE_KEY, $id);
    }

    private function reset($id = self::ID)
    {
        $this->throttler->reset(self::USE_CASE_KEY, $id);
    }

    private function sleepUpTo($milliseconds)
    {
        $duration = $this->event->lap()->getDuration();
        $this->sleepMs($milliseconds - $duration + self::ERROR_CORRECTION_PERIOD_MS);
    }

    private function sleepMs($milliseconds)
    {
        usleep($milliseconds * 1000);
    }
}
