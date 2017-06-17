# Gentle-force: brute-force, error and request rate limiting

This is a library for rate-limiting both brute-force attempts
(like invalid credentials) and ordinary requests.

## Features

- can be used to limit brute-force attempts;
- can be used for request rate limiting;
- uses leaky / token bucket algorithm. This means that user does not have to wait
for next hour or day - additional attempts are possible as time goes by. This
also means that requests does not come in big batches when every hour starts;
- handles race-conditions. This is important for brute-force limiting. For example,
if 1000 requests are issued at the same time to check same user's password, only
configured number of attempts will be possible;
- can have several limits configured for single use-case (for example maximum of
100 requests per minute and 200 per hour);
- does not make assumptions about where and what it's used for - it can be used
with user identifiers, API tokens, IP addresses or any other data to group usages.

## Installation

```
composer require maba/gentle-force
```

## Usage

```php
<?php

use Maba\GentleForce\RateLimit\UsageRateLimit;
use Maba\GentleForce\RateLimitProvider;
use Maba\GentleForce\Throttler;
use Maba\GentleForce\Exception\RateLimitReachedException;

$rateLimitProvider = new RateLimitProvider();
$rateLimitProvider->registerRateLimits('credentials_error', [
    // allow 3 errors per hour; 2 additional errors if no errors were made during last hour
    (new UsageRateLimit(3, 3600))->setBucketedUsages(2),
    // allow 10 errors per day
    new UsageRateLimit(10, 3600 * 24),
]);
$rateLimitProvider->registerRateLimits('api_request', [
    // - allow 10 requests each minute;
    // - user can "save up" hour of usage if not using API.
    //   This means up to 610 requests at once, after that - 10 requests per minute,
    //   which could again save-up up to 610.
    (new UsageRateLimit(10, 60))->setBucketedPeriod(3600),
]);

$throttler = new Throttler(new \Predis\Client([
    'host' => '127.0.0.1',
]), $rateLimitProvider);

// rate limiting:
try {
    $result = $throttler->checkAndIncrease('api_request', $_SERVER['REMOTE_ADDR']);
    header('Requests-Available', $result->getUsagesAvailable());
    
} catch (RateLimitReachedException $exception) {
    header('Wait-For', $exception->getWaitForInSeconds(), 429);
    return;
}

// brute-force limiting:
try {
    // we must increase error count in-advance before even checking credentials
    // this avoids race-conditions with lots of requests
    $credentialsResult = $throttler->checkAndIncrease('credentials_error', $_POST['username']);
} catch (RateLimitReachedException $exception) {
    echo sprintf('Too much password tries for user. Please try after %s seconds', $exception->getWaitForInSeconds());
    return;
}

$credentialsValid = checkCredentials($_POST['username'], $_POST['password']);

if ($credentialsValid) {
    // as we've increased error count in advance, we need to decrease it if everything went fine
    $credentialsResult->decrease();
    
    // log user into system
}
```

## Alternatives

Actually, there are quite many of them.

Unfortunately, as some provide additional features (like different storage methods: file, memcached etc.),
none were found with these criteria:
- usable for brute-forcing (only on errors), not for all requests;
- abstract, so that limiting by user, IP and other identifiers would be possible;
- rate limiting algorithm that would not block for too long for a legitimate user;
- free of race-conditions where actual limit would not work correctly on high load.

Some of reviewed alternatives:
[RateLimitInterface](https://github.com/touhonoob/RateLimitInterface),
[rate-limiter](https://github.com/codeages/rate-limiter),
[LosRateLimit](https://github.com/Lansoweb/LosRateLimit),
[Rate-limit](https://github.com/Prezto/Rate-limit),
[rate-limit](https://github.com/nikolaposa/rate-limit),
[php-ratelimiter](https://github.com/EvolutedNewMedia/php-ratelimiter),
[tokenbucket](https://github.com/fustundag/tokenbucket),
[brute-force](https://github.com/ArmorGames/brute-force),
[LoginGateBundle](https://github.com/anyx/LoginGateBundle),
[tresholds-governor](https://github.com/metaclass-nl/tresholds-governor),
[throttle](https://github.com/sideshowcecil/throttle),
[PeerjUserSecurityBundle](https://github.com/PeerJ/PeerjUserSecurityBundle),
[php-ratelimiter](https://github.com/MyOnlineStore/php-ratelimiter),
[RateLimitBundle](https://github.com/PQstudio/RateLimitBundle),
[CybBotDetectBunble](https://github.com/Dean79000/CybBotDetectBunble),
[CCDNUserSecurityBundle](https://github.com/codeconsortium/CCDNUserSecurityBundle),
[limit-number-calls-bundle](https://github.com/Avtonom/limit-number-calls-bundle),
[rate-limiter-php](https://github.com/perimeter/rate-limiter-php),
[flaps](https://github.com/beheh/flaps),
[token-bucket](https://github.com/bandwidth-throttle/token-bucket)

## Semantic versioning

This library follows [semantic versioning](http://semver.org/spec/v2.0.0.html).

See [Symfony BC rules](http://symfony.com/doc/current/contributing/code/bc.html) for basic
information about what can be changed and what not in the API.

## Running tests

[![Travis status](https://travis-ci.org/mariusbalcytis/gentle-force.svg?branch=master)](https://travis-ci.org/mariusbalcytis/gentle-force)

Functional tests require Redis and several PHP extensions for forking,
so that behaviour on high traffic could be tested. So, generally,
it's easier to run them in docker.

```
composer update
cd docker
docker-compose up -d
docker exec -it gentle_force_test_php vendor/bin/phpunit
docker-compose down
```

## Contributing

Feel free to create issues and give pull requests.

You can fix any code style issues using this command:
```
vendor/bin/php-cs-fixer fix --config=.php_cs
```
