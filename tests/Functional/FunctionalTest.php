<?php

namespace Maba\GentleForce\Tests\Functional;

use Maba\GentleForce\Exception\RateLimitReachedException;
use Maba\GentleForce\RateLimit\UsageRateLimit;
use Maba\GentleForce\RateLimitProvider;
use Maba\GentleForce\Throttler;
use Maba\GentleForce\ThrottlerInterface;
use PHPUnit\Framework\TestCase;
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
     * @var ThrottlerInterface
     */
    private $throttler;

    /**
     * @var StopwatchEvent
     */
    private $event;

    public function testWithoutBucketedUsages()
    {
        $this->setUpThrottler([
            new UsageRateLimit(10, 6),
        ]);

        $this->assertUsagesValid(10);

        $this->sleepUpTo(600);

        $this->assertUsagesValid(1);
    }

    public function testWithBucketedUsages()
    {
        $this->setUpThrottler([
            (new UsageRateLimit(10, 6))->setBucketedUsages(10),
        ]);

        $this->assertUsagesValid(20);

        $this->sleepUpTo(600);

        $this->assertUsagesValid(1);
    }

    public function testWithSeveralLimits()
    {
        $this->setUpThrottler([
            (new UsageRateLimit(2, 1.2))->setBucketedUsages(3),
            (new UsageRateLimit(1, 2))->setBucketedUsages(5),
        ]);

        $this->assertUsagesValid(5);

        $this->sleepUpTo(600);
        $this->assertUsagesValid(1);

        $this->sleepUpTo(1800);
        $this->assertUsagesValid(0);

        $this->sleepUpTo(2000);
        $this->assertUsagesValid(1);
    }

    public function testWithDecreasing()
    {
        $this->setUpThrottler([
            (new UsageRateLimit(2, 1.2))->setBucketedUsages(3),
            (new UsageRateLimit(1, 2))->setBucketedUsages(5),
        ]);

        $this->checkAndIncrease()->decrease();
        $this->assertUsagesValid(5);

        $this->sleepUpTo(600);
        $this->checkAndIncrease()->decrease();
        $this->assertUsagesValid(1);

        $this->sleepUpTo(1800);
        $this->assertUsagesValid(0);

        $this->sleepUpTo(2000);
        $this->checkAndIncrease()->decrease();
        $this->assertUsagesValid(1);
    }

    public function testWithReset()
    {
        $this->setUpThrottler([
            (new UsageRateLimit(2, 1.2))->setBucketedUsages(3),
            (new UsageRateLimit(1, 2))->setBucketedUsages(5),
        ]);

        $this->assertUsagesValid(5);
        $this->reset();
        $this->assertUsagesValid(5);

        $this->sleepUpTo(600);
        $this->assertUsagesValid(1);

        $this->reset();
        $this->assertUsagesValid(5);

        $this->reset(self::ANOTHER_ID);
        $this->assertUsagesValid(0);
    }

    private function setUpThrottler($rateLimits)
    {
        $rateLimitProvider = new RateLimitProvider();
        $rateLimitProvider->registerRateLimits(self::USE_CASE_KEY, $rateLimits);

        $this->throttler = $this->createThrottler($rateLimitProvider);

        $this->event = (new Stopwatch())->start('');
    }

    protected function createThrottler(RateLimitProvider $rateLimitProvider)
    {
        $prefix = 'functional_test_' . microtime();
        return new Throttler(new Client([
            'host' => isset($_ENV['REDIS_HOST']) ? $_ENV['REDIS_HOST'] : 'localhost',
        ]), $rateLimitProvider, $prefix);
    }

    private function assertUsagesValid($countOfUsages)
    {
        for ($i = 1; $i <= $countOfUsages; $i++) {
            $result = $this->checkAndIncrease();
            $this->assertSame($countOfUsages - $i, $result->getUsagesAvailable());
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
