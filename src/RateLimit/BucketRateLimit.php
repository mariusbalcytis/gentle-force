<?php

namespace Maba\GentleForce\RateLimit;

class BucketRateLimit implements RateLimitInterface
{
    /**
     * Bucket size, measured in tokens.
     *
     * @var float
     */
    private $bucketSize;

    /**
     * Tokens needed for every usage.
     *
     * @var float
     */
    private $tokensPerUsage;

    /**
     * Tokens issed every second.
     *
     * @var float
     */
    private $tokensPerSecond = 1;

    public function __construct($bucketSize, $tokensPerUsage)
    {
        $this->bucketSize = $bucketSize;
        $this->tokensPerUsage = $tokensPerUsage;
    }

    /**
     * @return float
     */
    public function getBucketSize()
    {
        return $this->bucketSize;
    }

    /**
     * @param float $bucketSize
     * @return $this
     */
    public function setBucketSize($bucketSize)
    {
        $this->bucketSize = $bucketSize;

        return $this;
    }

    /**
     * @return float
     */
    public function getTokensPerUsage()
    {
        return $this->tokensPerUsage;
    }

    /**
     * @param float $tokensPerUsage
     * @return $this
     */
    public function setTokensPerUsage($tokensPerUsage)
    {
        $this->tokensPerUsage = $tokensPerUsage;

        return $this;
    }

    /**
     * @return float
     */
    public function getTokensPerSecond()
    {
        return $this->tokensPerSecond;
    }

    /**
     * @param float $tokensPerSecond
     * @return $this
     */
    public function setTokensPerSecond($tokensPerSecond)
    {
        $this->tokensPerSecond = $tokensPerSecond;

        return $this;
    }

    public function calculateBucketSize()
    {
        return $this->bucketSize / $this->tokensPerSecond;
    }

    public function calculateTokensPerUsage()
    {
        return $this->tokensPerUsage / $this->tokensPerSecond;
    }
}
