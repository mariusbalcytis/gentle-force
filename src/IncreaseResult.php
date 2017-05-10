<?php

namespace Maba\GentleForce;

class IncreaseResult
{
    private $throttler;
    private $usagesAvailable;
    private $useCaseKey;
    private $identifier;

    public function __construct(Throttler $throttler, $usagesAvailable, $useCaseKey, $identifier)
    {
        $this->throttler = $throttler;
        $this->usagesAvailable = $usagesAvailable;
        $this->useCaseKey = $useCaseKey;
        $this->identifier = $identifier;
    }

    public function getUsagesAvailable()
    {
        return $this->usagesAvailable;
    }

    public function decrease()
    {
        $this->throttler->decrease($this->useCaseKey, $this->identifier);
    }
}
