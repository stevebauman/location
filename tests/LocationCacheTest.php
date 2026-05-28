<?php

namespace Stevebauman\Location\Tests;

use Illuminate\Support\Facades\Cache;
use Mockery as m;
use Stevebauman\Location\Drivers\Driver;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

beforeEach(function () {
    config([
        'location.cache.enabled' => true,
        'location.cache.ttl' => 3600,
        'location.cache.store' => 'array',
        'location.cache.prefix' => 'location',
    ]);

    Cache::store(config('location.cache.store'))->flush();
});

/**
 * Get the configured cache store.
 */
function cacheStore(): \Illuminate\Cache\Repository
{
    return Cache::store(config('location.cache.store'));
}

it('does not use the cache when cache is disabled', function () {
    config(['location.cache.enabled' => false]);

    $driver = m::mock(Driver::class)
        ->shouldAllowMockingProtectedMethods();

    $driver->shouldReceive('get')
        ->twice()
        ->andReturn(new Position);

    Location::setDriver($driver);

    Location::get('1.2.3.4');
    Location::get('1.2.3.4');
});

it('caches a resolved position', function () {
    $position = new Position;
    $position->ip = '1.2.3.4';
    $position->countryCode = 'US';

    $driver = m::mock(Driver::class)
        ->shouldAllowMockingProtectedMethods();

    $driver->shouldReceive('get')
        ->once()
        ->andReturn($position);

    Location::setDriver($driver);

    $first = Location::get('1.2.3.4');
    $second = Location::get('1.2.3.4');

    expect($first)->toBeInstanceOf(Position::class);
    expect($second)->toBeInstanceOf(Position::class);
    expect(cacheStore()->has('location_1.2.3.4'))->toBeTrue();
});

it('returns the cached position on subsequent calls', function () {
    $position = new Position;
    $position->ip = '5.6.7.8';

    cacheStore()->put('location_5.6.7.8', $position, 3600);

    $driver = m::mock(Driver::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $driver->shouldReceive('process')->never();

    Location::setDriver($driver);

    $result = Location::get('5.6.7.8');

    expect($result)->toBeInstanceOf(Position::class);
    expect($result->ip)->toBe('5.6.7.8');
});

it('does not cache a failed lookup by default', function () {
    $driver = m::mock(Driver::class)
        ->shouldAllowMockingProtectedMethods();

    $driver->shouldReceive('get')
        ->twice()
        ->andReturn(false);

    Location::setDriver($driver);

    Location::get('9.9.9.9');
    Location::get('9.9.9.9');

    expect(cacheStore()->has('location_9.9.9.9'))->toBeFalse();
});

it('caches a failed lookup when ignore_failed is false', function () {
    config(['location.cache.ignore_failed' => false]);

    $driver = m::mock(Driver::class)
        ->shouldAllowMockingProtectedMethods();

    $driver->shouldReceive('get')
        ->once()
        ->andReturn(false);

    Location::setDriver($driver);

    Location::get('9.9.9.9');
    Location::get('9.9.9.9');

    expect(cacheStore()->has('location_9.9.9.9'))->toBeTrue();
    expect(cacheStore()->get('location_9.9.9.9'))->toBeFalse();
});

it('uses a custom cache prefix', function () {
    config(['location.cache.prefix' => 'geo']);

    $position = new Position;
    $position->ip = '1.2.3.4';
    $position->countryCode = 'US';

    $driver = m::mock(Driver::class)
        ->shouldAllowMockingProtectedMethods();

    $driver->shouldReceive('get')
        ->once()
        ->andReturn($position);

    Location::setDriver($driver);

    Location::get('1.2.3.4');

    expect(cacheStore()->has('geo_1.2.3.4'))->toBeTrue();
    expect(cacheStore()->has('location_1.2.3.4'))->toBeFalse();
});

it('caches different IPs independently', function () {
    $position = new Position;
    $position->countryCode = 'US';

    $driver = m::mock(Driver::class)
        ->shouldAllowMockingProtectedMethods();

    $driver->shouldReceive('get')
        ->twice()
        ->andReturn($position);

    Location::setDriver($driver);

    Location::get('1.1.1.1');
    Location::get('2.2.2.2');

    expect(cacheStore()->has('location_1.1.1.1'))->toBeTrue();
    expect(cacheStore()->has('location_2.2.2.2'))->toBeTrue();
});
