<?php

namespace Maba\GentleForce\Redis\Result;

/**
 * @internal
 */
class CheckAndIncreaseResult
{
    /**
     * @var float|null
     */
    private $waitForInSeconds;

    /**
     * @var float|null
     */
    private $usagesAvailable;

    public static function createForReachedLimit($waitForInSeconds)
    {
        $result = new self();
        $result->waitForInSeconds = $waitForInSeconds;
        return $result;
    }

    public static function createForAvailableUsages($usagesAvailable)
    {
        $result = new self();
        $result->usagesAvailable = $usagesAvailable;
        return $result;
    }

    public function isLimitReached()
    {
        return $this->usagesAvailable === null;
    }

    /**
     * @return float|null
     */
    public function getWaitForInSeconds()
    {
        return $this->waitForInSeconds;
    }

    /**
     * @return float|null
     */
    public function getUsagesAvailable()
    {
        return $this->usagesAvailable;
    }
}
