<?php

namespace Maba\GentleForce\Tests\Functional;

use Maba\GentleForce\InMemory\InMemoryThrottler;
use Maba\GentleForce\InMemory\MicrotimeProvider;
use Maba\GentleForce\RateLimitProvider;

class FunctionalInMemoryThrottlerTest extends FunctionalTest
{

    protected function createThrottler(RateLimitProvider $rateLimitProvider)
    {
        return new InMemoryThrottler($rateLimitProvider, new MicrotimeProvider());
    }
}
