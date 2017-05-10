<?php

namespace Maba\GentleForce\Exception;

use RuntimeException;
use Throwable;

class RateLimitReachedException extends RuntimeException
{
    /**
     * @var float
     */
    private $waitForInSeconds;

    public function __construct($waitForInSeconds, $message = 'Rate limit was reached', Throwable $previous = null)
    {
        $this->waitForInSeconds = $waitForInSeconds;
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return float
     */
    public function getWaitForInSeconds()
    {
        return $this->waitForInSeconds;
    }
}
