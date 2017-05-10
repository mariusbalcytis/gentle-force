<?php

namespace Maba\GentleForce\RateLimit;

/**
 * Represents needed rate limit parameters for token bucket / leaky bucket algorithm.
 *
 * Single token is always issued at every second - other parameters can be calculated from that (floats are possible)
 */
interface RateLimitInterface
{
    /**
     * Gets size of the bucket. It's either in tokens or in seconds, as single token is issued every second.
     *
     * @return float
     */
    public function calculateBucketSize();

    /**
     * Gets tokens needed for single usage.
     *
     * @return float
     */
    public function calculateTokensPerUsage();
}
