<?php

namespace Maba\GentleForce\RateLimit;

use InvalidArgumentException;

class UsageRateLimit implements RateLimitInterface
{
    /**
     * Count of usages available in specified period.
     *
     * @var float
     */
    private $maxUsages;

    /**
     * Period in seconds for max usages to be "spent".
     *
     * @var float
     */
    private $period;

    /**
     * Maximum usage count to collect for later extra bursts.
     * Not available together with bucketed period.
     *
     * @var float
     */
    private $bucketedUsages;

    /**
     * Maximum time in seconds to collect available usages for later extra bursts.
     * Not available together with bucketed usages.
     *
     * @var float
     */
    private $bucketedPeriod;

    public function __construct($maxUsages, $period)
    {
        $this->maxUsages = $maxUsages;
        $this->period = $period;
    }

    /**
     * @return float
     */
    public function getMaxUsages()
    {
        return $this->maxUsages;
    }

    /**
     * @param float $maxUsages
     * @return $this
     */
    public function setMaxUsages($maxUsages)
    {
        $this->maxUsages = $maxUsages;

        return $this;
    }

    /**
     * @return float
     */
    public function getPeriod()
    {
        return $this->period;
    }

    /**
     * @param float $period
     * @return $this
     */
    public function setPeriod($period)
    {
        $this->period = $period;

        return $this;
    }

    /**
     * @return float
     */
    public function getBucketedUsages()
    {
        return $this->bucketedUsages;
    }

    /**
     * @param float $bucketedUsages
     * @return $this
     */
    public function setBucketedUsages($bucketedUsages)
    {
        if ($bucketedUsages !== null && $this->bucketedPeriod !== null) {
            throw new InvalidArgumentException('Cannot set both bucketed usages and bucketed period');
        }
        $this->bucketedUsages = $bucketedUsages;

        return $this;
    }

    /**
     * @return float
     */
    public function getBucketedPeriod()
    {
        return $this->bucketedPeriod;
    }

    /**
     * @param float $bucketedPeriod
     * @return $this
     */
    public function setBucketedPeriod($bucketedPeriod)
    {
        if ($bucketedPeriod !== null && $this->bucketedUsages !== null) {
            throw new InvalidArgumentException('Cannot set both bucketed usages and bucketed period');
        }
        $this->bucketedPeriod = $bucketedPeriod;

        return $this;
    }

    public function calculateBucketSize()
    {
        if ($this->bucketedPeriod !== null) {
            return $this->bucketedPeriod + $this->period;
        } elseif ($this->bucketedUsages !== null) {
            return ($this->bucketedUsages + $this->maxUsages) / $this->maxUsages * $this->period;
        }

        return $this->period;
    }

    public function calculateTokensPerUsage()
    {
        return $this->period / $this->maxUsages;
    }
}
