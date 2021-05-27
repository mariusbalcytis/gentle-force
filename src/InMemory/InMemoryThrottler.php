<?php

namespace Maba\GentleForce\InMemory;

use Maba\GentleForce\Exception\RateLimitReachedException;
use Maba\GentleForce\IncreaseResult;
use Maba\GentleForce\RateLimitProvider;
use Maba\GentleForce\ThrottlerInterface;

/**
 * Only for testing purposes (mocking) - mimics functionality without external dependencies.
 * Current time is also mockable.
 *
 * This allows writing consistent and fast tests for functionality that uses throttler.
 *
 * @internal
 */
class InMemoryThrottler implements ThrottlerInterface
{
    private $rateLimitProvider;
    private $microtimeProvider;

    /**
     * @var array
     */
    private $storage = [];

    public function __construct(RateLimitProvider $rateLimitProvider, MicrotimeProvider $microtimeProvider)
    {
        $this->rateLimitProvider = $rateLimitProvider;
        $this->microtimeProvider = $microtimeProvider;
    }

    /**
     * @param string $useCaseKey configured key for this use case, like "credentials_error_ip"
     * @param string $identifier rate-limiting group, like IP address or username
     * @return IncreaseResult
     * @throws RateLimitReachedException
     */
    public function checkAndIncrease($useCaseKey, $identifier)
    {
        $now = $this->microtimeProvider->getMicrotime();
        $rateLimits = $this->rateLimitProvider->getRateLimits($useCaseKey);
        $key = $this->buildKey($useCaseKey, $identifier);

        $totals = [];
        $usagesAvailable = [];
        $validAfter = 0;
        foreach ($rateLimits as $rateLimit) {
            $tokensPerUsage = $rateLimit->calculateTokensPerUsage();
            $bucketSize = $rateLimit->calculateBucketSize();
            $subKey = $tokensPerUsage . ':' . $bucketSize;
            $total = 0;
            $emptyAt = isset($this->storage[$key][$subKey]) ? $this->storage[$key][$subKey] : null;
            if ($emptyAt !== null) {
                $total = max(0, $emptyAt - $now);
            }
            $total += $tokensPerUsage;
            if ($total > $bucketSize) {
                $validAfter = max($validAfter, $total - $bucketSize);
            }

            $totals[$subKey] = $total;
            $usagesAvailable[$subKey] = ($bucketSize - $total) / $tokensPerUsage;
        }

        if ($validAfter > 0) {
            throw new RateLimitReachedException($validAfter);
        }

        $minUsagesAvailable = \INF;
        foreach ($totals as $subKey => $total) {
            $this->storage[$key][$subKey] = $now + $total;
            $minUsagesAvailable = min($minUsagesAvailable, $usagesAvailable[$subKey]);
        }

        return new IncreaseResult($this, (int)floor($minUsagesAvailable), $useCaseKey, $identifier);
    }

    /**
     * @param string $useCaseKey configured key for this use case, like "credentials_error_ip"
     * @param string $identifier rate-limiting group, like IP address or username
     */
    public function decrease($useCaseKey, $identifier)
    {
        $rateLimits = $this->rateLimitProvider->getRateLimits($useCaseKey);
        $key = $this->buildKey($useCaseKey, $identifier);

        foreach ($rateLimits as $rateLimit) {
            $tokensPerUsage = $rateLimit->calculateTokensPerUsage();
            $bucketSize = $rateLimit->calculateBucketSize();
            $subKey = $tokensPerUsage . ':' . $bucketSize;
            if (isset($this->storage[$key][$subKey])) {
                $this->storage[$key][$subKey] -= $tokensPerUsage;
            }
        }
    }

    /**
     * @param string $useCaseKey configured key for this use case, like "credentials_error_ip"
     * @param string $identifier rate-limiting group, like IP address or username
     */
    public function reset($useCaseKey, $identifier)
    {
        $key = $this->buildKey($useCaseKey, $identifier);
        unset($this->storage[$key]);
    }

    private function buildKey($useCaseKey, $identifier)
    {
        return $useCaseKey . ':' . $identifier;
    }
}
