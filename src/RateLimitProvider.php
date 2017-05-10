<?php

namespace Maba\GentleForce;

use InvalidArgumentException;

class RateLimitProvider
{
    private $rateLimits = [];

    /**
     * @param string $useCaseKey
     * @param array|\Maba\GentleForce\RateLimit\RateLimitInterface[] $rateLimits
     */
    public function registerRateLimits($useCaseKey, array $rateLimits)
    {
        $this->rateLimits[$useCaseKey] = $rateLimits;
    }

    /**
     * @param string $useCaseKey
     * @return \Maba\GentleForce\RateLimit\RateLimitInterface[]
     */
    public function getRateLimits($useCaseKey)
    {
        if (!isset($this->rateLimits[$useCaseKey])) {
            throw new InvalidArgumentException(sprintf('Rate limits not configured for "%s"', $useCaseKey));
        }

        return $this->rateLimits[$useCaseKey];
    }
}
