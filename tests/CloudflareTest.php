<?php

namespace Stevebauman\Location\Tests;

use Illuminate\Support\Facades\Request;
use Stevebauman\Location\Drivers\Cloudflare;
use Stevebauman\Location\Drivers\Driver;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

it('can use CF-injected full headers', function () {
    config(['location.testing.enabled' => false]);
    config(['location.driver' => Cloudflare::class]);
    config(['location.fallbacks' => []]);

    Request::instance()->headers->replace([
        'CF-IPCountry' => 'GB',
        'CF-IPCity' => 'Boxford',
        'CF-IPLatitude' => '51.75',
        'CF-IPLongitude' => '-1.25',
        'CF-Region' => 'Plymouth',
        'CF-Region-Code' => 'PLY',
        'CF-Postal-Code' => 'PL5',
        'CF-Timezone' => 'Europe/London',
    ]);

    $position = Location::get('2.125.160.216');

    expect($position)->toBeInstanceOf(Position::class);

    expect($position->toArray())->toEqual([
        'ip' => '2.125.160.216',
        'countryName' => null,
        'countryCode' => 'GB',
        'regionCode' => 'PLY',
        'regionName' => 'Plymouth',
        'cityName' => 'Boxford',
        'zipCode' => null,
        'isoCode' => 'GB',
        'postalCode' => 'PL5',
        'latitude' => '51.75',
        'longitude' => '-1.25',
        'metroCode' => null,
        'areaCode' => null,
        'timezone' => 'Europe/London',
        'driver' => Cloudflare::class,
    ]);
});

it('can use CF-injected simple header', function () {
    config(['location.testing.enabled' => false]);
    config(['location.driver' => Cloudflare::class]);

    Request::instance()->headers->replace([
        'CF-IPCountry' => 'GB',
    ]);

    $position = Location::get('2.125.160.216');

    expect($position)->toBeInstanceOf(Position::class);

    expect($position->toArray())->toEqual([
        'ip' => '2.125.160.216',
        'countryName' => null,
        'countryCode' => 'GB',
        'regionCode' => null,
        'regionName' => null,
        'cityName' => null,
        'zipCode' => null,
        'isoCode' => 'GB',
        'postalCode' => null,
        'latitude' => null,
        'longitude' => null,
        'metroCode' => null,
        'areaCode' => null,
        'timezone' => null,
        'driver' => Cloudflare::class,
    ]);
});

it('will gracefully fall back if CF header returns falsey value', function () {
    config(['location.testing.enabled' => false]);
    config(['location.driver' => Cloudflare::class]);
    config(['location.fallbacks' => [Driver::class]]);

    Request::instance()->headers->replace([
        'CF-IPCountry' => 'XX',
    ]);

    $position = new Position();
    $position->driver = Driver::class;

    $fallback = $this->mock(Driver::class);
    $fallback->shouldReceive('get')->andReturn($position);

    $position = Location::get('2.125.160.216');

    expect($position)->toBeInstanceOf(Position::class);
    expect($position->driver)->toEqual(Driver::class);

    Request::instance()->headers->replace([
        'CF-IPCountry' => 'T1',
    ]);

    $position = Location::get('2.125.160.216');

    expect($position)->toBeInstanceOf(Position::class);
    expect($position->driver)->toEqual(Driver::class);
});

it('will gracefully fall back if CF headers are not present', function () {
    config(['location.testing.enabled' => false]);
    config(['location.driver' => Cloudflare::class]);
    config(['location.fallbacks' => [Driver::class]]);

    $position = new Position();
    $position->driver = Driver::class;

    $fallback = $this->mock(Driver::class);
    $fallback->shouldReceive('get')->andReturn($position);

    $position = Location::get('2.125.160.216');

    expect($position)->toBeInstanceOf(Position::class);
    expect($position->driver)->toEqual(Driver::class);
});
