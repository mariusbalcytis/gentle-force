<?php

namespace Maba\GentleForce\Redis\Command;

use Maba\GentleForce\Redis\Result\CheckAndIncreaseResult;
use Predis\Command\ScriptCommand;

/**
 * @internal
 */
class CheckAndIncreaseCommand extends ScriptCommand
{
    /**
     * @param string $key
     * @param array|\Maba\GentleForce\RateLimit\RateLimitInterface[] $rateLimits
     * @param float $microtime
     */
    public function __construct($key, array $rateLimits, $microtime)
    {
        $this->setArguments([$key, $this->formatRateLimits($rateLimits), $microtime]);
    }

    /**
     * @param array|\Maba\GentleForce\RateLimit\RateLimitInterface[] $rateLimits
     * @return string
     */
    private function formatRateLimits(array $rateLimits)
    {
        $list = [];
        foreach ($rateLimits as $rateLimit) {
            $list[] = [$rateLimit->calculateTokensPerUsage(), $rateLimit->calculateBucketSize()];
        }
        return json_encode($list);
    }

    public function getKeysCount()
    {
        return 1;
    }

    public function getScript()
    {
        return file_get_contents(__DIR__ . '/../scripts/checkAndIncrease.lua');
    }

    public function parseResponse($response)
    {
        $response = (float)$response;

        if ($response < 0) {
            return CheckAndIncreaseResult::createForReachedLimit(-$response);
        }

        return CheckAndIncreaseResult::createForAvailableUsages($response);
    }
}
