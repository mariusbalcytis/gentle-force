<?php

namespace Maba\GentleForce;

use Maba\GentleForce\Exception\RateLimitReachedException;
use Maba\GentleForce\Redis\Command\CheckAndIncreaseCommand;
use Maba\GentleForce\Redis\Command\DecreaseCommand;
use Maba\GentleForce\Redis\Result\CheckAndIncreaseResult;
use Predis\Client;

class Throttler
{
    private $client;
    private $rateLimitProvider;
    private $prefix;

    public function __construct(Client $client, RateLimitProvider $rateLimitProvider, $prefix = '')
    {
        $this->client = $client;
        $this->rateLimitProvider = $rateLimitProvider;
        $this->prefix = $prefix;
    }

    /**
     * @param string $useCaseKey configured key for this use case, like "credentials_error_ip"
     * @param string $identifier rate-limiting group, like IP address or username
     * @return IncreaseResult
     * @throws RateLimitReachedException
     */
    public function checkAndIncrease($useCaseKey, $identifier)
    {
        $rateLimits = $this->rateLimitProvider->getRateLimits($useCaseKey);
        $key = $this->buildKey($useCaseKey, $identifier);

        /** @var CheckAndIncreaseResult $redisResult */
        $redisResult = $this->client->executeCommand(
            new CheckAndIncreaseCommand($key, $rateLimits, microtime(true))
        );

        if ($redisResult->isLimitReached()) {
            throw new RateLimitReachedException($redisResult->getWaitForInSeconds());
        }

        return new IncreaseResult($this, $redisResult->getUsagesAvailable(), $useCaseKey, $identifier);
    }

    /**
     * @param string $useCaseKey configured key for this use case, like "credentials_error_ip"
     * @param string $identifier rate-limiting group, like IP address or username
     */
    public function decrease($useCaseKey, $identifier)
    {
        $rateLimits = $this->rateLimitProvider->getRateLimits($useCaseKey);
        $key = $this->buildKey($useCaseKey, $identifier);

        $this->client->executeCommand(
            new DecreaseCommand($key, $rateLimits)
        );
    }

    /**
     * @param string $useCaseKey configured key for this use case, like "credentials_error_ip"
     * @param string $identifier rate-limiting group, like IP address or username
     */
    public function reset($useCaseKey, $identifier)
    {
        $key = $this->buildKey($useCaseKey, $identifier);
        $this->client->del([$key]);
    }

    private function buildKey($useCaseKey, $identifier)
    {
        return $this->prefix . $useCaseKey . ':' . $identifier;
    }
}
