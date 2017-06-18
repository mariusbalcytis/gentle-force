<?php

namespace Maba\GentleForce;

use Maba\GentleForce\Exception\RateLimitReachedException;

interface ThrottlerInterface
{
    /**
     * @param string $useCaseKey configured key for this use case, like "credentials_error_ip"
     * @param string $identifier rate-limiting group, like IP address or username
     * @return IncreaseResult
     * @throws RateLimitReachedException
     */
    public function checkAndIncrease($useCaseKey, $identifier);

    /**
     * @param string $useCaseKey configured key for this use case, like "credentials_error_ip"
     * @param string $identifier rate-limiting group, like IP address or username
     */
    public function decrease($useCaseKey, $identifier);

    /**
     * @param string $useCaseKey configured key for this use case, like "credentials_error_ip"
     * @param string $identifier rate-limiting group, like IP address or username
     */
    public function reset($useCaseKey, $identifier);
}
