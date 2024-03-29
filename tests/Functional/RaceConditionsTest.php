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

class RaceConditionsTest extends TestCase
{
    public const USE_CASE_KEY = 'use_case_key';
    public const ID = 'user1';

    /**
     * @var ThrottlerInterface
     */
    private $throttler;

    /**
     * @var StopwatchEvent
     */
    private $event;

    public function testSynchronously()
    {
        $maxUsages = 1000;
        $this->setUpThrottler([
            (new UsageRateLimit($maxUsages, 3 * $maxUsages)),
        ]);

        $this->assertSame($maxUsages, $this->getAvailableUsageCount());
    }

    public function testAsynchronously()
    {
        $maxUsages = 10000;
        $this->setUpThrottler([
            (new UsageRateLimit($maxUsages, 10 * $maxUsages)),
        ]);

        $results = Forker::map(
            range(0, 100),
            function ($index) {
                return $this->getAvailableUsageCount();
            }
        );
        $totalCount = 0;
        for ($i = 0; $i < \count($results); $i++) {
            $totalCount += $results[$i];
        }

        $this->assertSame($maxUsages, $totalCount);
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

    private function getAvailableUsageCount()
    {
        $count = 0;
        while (true) {
            try {
                $this->checkAndIncrease();
                $count++;
            } catch (RateLimitReachedException $exception) {
                break;
            }
        }
        return $count;
    }

    private function checkAndIncrease($id = self::ID)
    {
        return $this->throttler->checkAndIncrease(self::USE_CASE_KEY, $id);
    }
}
