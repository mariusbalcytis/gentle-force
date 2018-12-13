<?php

namespace Maba\GentleForce;

class NullThrottler implements ThrottlerInterface
{
    public function checkAndIncrease($useCaseKey, $identifier)
    {
        return new IncreaseResult($this, 1, $useCaseKey, $identifier);
    }

    public function decrease($useCaseKey, $identifier)
    {
        // left blank intentionally
    }

    public function reset($useCaseKey, $identifier)
    {
        // left blank intentionally
    }
}
