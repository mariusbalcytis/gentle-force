<?php

namespace Maba\GentleForce\Tests\RateLimit;

use Maba\GentleForce\RateLimit\BucketRateLimit;
use PHPUnit\Framework\TestCase;

class BucketRateLimitTest extends TestCase
{
    /**
     * @param float $expectedTokensPerUsage
     * @param float $expectedBucketSize
     * @param float $bucketSize
     * @param float $tokensPerUsage
     *
     * @dataProvider providerForTestWithoutTokensPerSecond
     */
    public function testWithoutTokensPerSecond(
        $expectedTokensPerUsage,
        $expectedBucketSize,
        $tokensPerUsage,
        $bucketSize
    ) {
        $bucketRateLimit = new BucketRateLimit($bucketSize, $tokensPerUsage);
        $this->assertEquals($expectedBucketSize, $bucketRateLimit->calculateBucketSize());
        $this->assertEquals($expectedTokensPerUsage, $bucketRateLimit->calculateTokensPerUsage());
    }

    public function providerForTestWithoutTokensPerSecond()
    {
        return [
            [1, 1, 1, 1],
            [1, 2, 1, 2],
            [2, 1, 2, 1],
            [0.2, 19.3, 0.2, 19.3],
        ];
    }

    /**
     * @param float $expectedTokensPerUsage
     * @param float $expectedBucketSize
     * @param float $bucketSize
     * @param float $tokensPerUsage
     * @param float $tokensPerSecond
     *
     * @dataProvider providerForTestWithTokensPerSecond
     */
    public function testWithTokensPerSecond(
        $expectedTokensPerUsage,
        $expectedBucketSize,
        $tokensPerUsage,
        $bucketSize,
        $tokensPerSecond
    ) {
        $bucketRateLimit = new BucketRateLimit($bucketSize, $tokensPerUsage);
        $bucketRateLimit->setTokensPerSecond($tokensPerSecond);
        $this->assertEquals($expectedBucketSize, $bucketRateLimit->calculateBucketSize());
        $this->assertEquals($expectedTokensPerUsage, $bucketRateLimit->calculateTokensPerUsage());
    }

    public function providerForTestWithTokensPerSecond()
    {
        return [
            [1, 1, 1, 1, 1],
            [1, 2, 1, 2, 1],
            [2, 1, 2, 1, 1],
            [0.2, 19.3, 0.2, 19.3, 1],
            [0.6, 2.2, 1.2, 4.4, 2],
            [2.4, 8.8, 1.2, 4.4, 0.5],
        ];
    }
}
