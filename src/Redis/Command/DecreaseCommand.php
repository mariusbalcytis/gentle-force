<?php

namespace Maba\GentleForce\Redis\Command;

use Maba\GentleForce\RateLimit\RateLimitInterface;
use Predis\Command\ScriptCommand;

/**
 * @internal
 */
class DecreaseCommand extends ScriptCommand
{
    /**
     * @param string $key
     * @param array|RateLimitInterface[] $rateLimits
     */
    public function __construct($key, array $rateLimits)
    {
        $this->setArguments([$key, $this->formatRateLimits($rateLimits)]);
    }

    /**
     * @param array|RateLimitInterface[] $rateLimits
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
        return file_get_contents(__DIR__ . '/../scripts/decrease.lua');
    }
}
