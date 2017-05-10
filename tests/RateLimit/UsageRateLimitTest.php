<?php

namespace Maba\GentleForce\Tests\RateLimit;

use InvalidArgumentException;
use Maba\GentleForce\RateLimit\UsageRateLimit;
use PHPUnit_Framework_TestCase as TestCase;

class UsageRateLimitTest extends TestCase
{
    /**
     * @param float $expectedTokensPerUsage
     * @param float $expectedBucketSize
     * @param float $maxUsages
     * @param float $period
     *
     * @dataProvider providerForTestWithoutBucket
     */
    public function testWithoutBucket($expectedTokensPerUsage, $expectedBucketSize, $maxUsages, $period)
    {
        $usageRateLimit = new UsageRateLimit($maxUsages, $period);
        $this->assertEquals($expectedBucketSize, $usageRateLimit->calculateBucketSize());
        $this->assertEquals($expectedTokensPerUsage, $usageRateLimit->calculateTokensPerUsage());
    }

    public function providerForTestWithoutBucket()
    {
        return [
            [1, 1, 1, 1],
            [1, 2, 2, 2],
            [0.5, 1.5, 3, 1.5],
            [2, 3, 1.5, 3],
            [0.2, 2, 10, 2],
        ];
    }

    /**
     * @param float $expectedTokensPerUsage
     * @param float $expectedBucketSize
     * @param float $maxUsages
     * @param float $period
     * @param float $bucketedUsages
     *
     * @dataProvider providerForTestWithBucketedUsages
     */
    public function testWithBucketedUsages(
        $expectedTokensPerUsage,
        $expectedBucketSize,
        $maxUsages,
        $period,
        $bucketedUsages
    ) {
        $usageRateLimit = new UsageRateLimit($maxUsages, $period);
        $usageRateLimit->setBucketedUsages($bucketedUsages);
        $this->assertEquals($expectedBucketSize, $usageRateLimit->calculateBucketSize());
        $this->assertEquals($expectedTokensPerUsage, $usageRateLimit->calculateTokensPerUsage());
    }

    public function providerForTestWithBucketedUsages()
    {
        return [
            [1, 2, 1, 1, 1],
            [12, 180, 5, 60, 10],
            [3, 18, 1, 3, 5],
            [0.02, 2.2, 100, 2, 10],
        ];
    }

    /**
     * @param float $expectedTokensPerUsage
     * @param float $expectedBucketSize
     * @param float $maxUsages
     * @param float $period
     * @param float $bucketedPeriod
     *
     * @dataProvider providerForTestWithBucketedPeriod
     */
    public function testWithBucketedPeriod(
        $expectedTokensPerUsage,
        $expectedBucketSize,
        $maxUsages,
        $period,
        $bucketedPeriod
    ) {
        $usageRateLimit = new UsageRateLimit($maxUsages, $period);
        $usageRateLimit->setBucketedPeriod($bucketedPeriod);
        $this->assertEquals($expectedBucketSize, $usageRateLimit->calculateBucketSize());
        $this->assertEquals($expectedTokensPerUsage, $usageRateLimit->calculateTokensPerUsage());
    }

    public function providerForTestWithBucketedPeriod()
    {
        return [
            [1, 2, 1, 1, 1],
            [1, 30, 10, 10, 20],
            [0.05, 65, 100, 5, 60],
        ];
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithBucketedPeriodAndUsages()
    {
        $usageRateLimit = new UsageRateLimit(1, 1);
        $usageRateLimit->setBucketedPeriod(1);
        $usageRateLimit->setBucketedUsages(1);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithBucketedUsagesAndPeriod()
    {
        $usageRateLimit = new UsageRateLimit(1, 1);
        $usageRateLimit->setBucketedUsages(1);
        $usageRateLimit->setBucketedPeriod(1);
    }
}
