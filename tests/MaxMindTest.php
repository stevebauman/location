<?php

namespace Stevebauman\Location\Tests;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Fluent;
use Mockery as m;
use Stevebauman\Location\Commands\Update;
use Stevebauman\Location\Drivers\MaxMind;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

it('can update database', function () {
    config([
        'location.maxmind.license_key' => '123',
        'location.maxmind.local.url' => 'http://example.com',
    ]);

    Http::fake([
        'http://example.com' => Http::response(file_get_contents(__DIR__.'/fixtures/maxmind.tar.gz')),
    ]);

    $this->artisan(Update::class)->assertSuccessful();

    expect(database_path('maxmind/GeoLite2-City.mmdb'))->toBeFile();
});

it('can process fluent response', function () {
    $driver = m::mock(MaxMind::class);

    $attributes = [
        'country' => 'United States',
        'country_code' => 'US',
        'city' => 'Long Beach',
        'postal' => '55555',
        'metro_code' => '5555',
        'latitude' => '50',
        'longitude' => '50',
        'timezone' => 'America/Toronto',
    ];

    $driver
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('process')->once()->andReturn(new Fluent($attributes));

    Location::setDriver($driver);

    $position = Location::get();

    expect($position)->toBeInstanceOf(Position::class);

    expect($position->toArray())->toEqual([
        'countryName' => 'United States',
        'countryCode' => 'US',
        'regionCode' => null,
        'regionName' => null,
        'cityName' => 'Long Beach',
        'zipCode' => null,
        'isoCode' => 'US',
        'postalCode' => '55555',
        'latitude' => '50',
        'longitude' => '50',
        'metroCode' => '5555',
        'areaCode' => null,
        'ip' => '66.102.0.0',
        'timezone' => 'America/Toronto',
        'driver' => get_class($driver),
    ]);
});

it('can use city database', function () {
    config(['location.testing.enabled' => false]);
    config(['location.driver' => MaxMind::class]);
    config(['location.maxmind.local.type' => 'city']);
    config(['location.maxmind.local.path' => __DIR__.'/fixtures/GeoLite2-City-Test.mmdb']);

    $position = Location::get('2.125.160.216');

    expect($position)->toBeInstanceOf(Position::class);

    expect($position->toArray())->toEqual([
        'ip' => '2.125.160.216',
        'countryName' => 'United Kingdom',
        'countryCode' => 'GB',
        'regionCode' => 'WBK',
        'regionName' => 'West Berkshire',
        'cityName' => 'Boxford',
        'zipCode' => null,
        'isoCode' => 'GB',
        'postalCode' => 'OX1',
        'latitude' => '51.75',
        'longitude' => '-1.25',
        'metroCode' => '',
        'areaCode' => null,
        'timezone' => 'Europe/London',
        'driver' => "Stevebauman\Location\Drivers\MaxMind",
    ]);
});

it('can use country database', function () {
    config(['location.testing.enabled' => false]);
    config(['location.driver' => MaxMind::class]);
    config(['location.maxmind.local.type' => 'country']);
    config(['location.maxmind.local.path' => __DIR__.'/fixtures/GeoLite2-Country-Test.mmdb']);

    $position = Location::get('2.125.160.216');

    expect($position)->toBeInstanceOf(Position::class);

    expect($position->toArray())->toEqual([
        'ip' => '2.125.160.216',
        'countryName' => 'United Kingdom',
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
        'driver' => "Stevebauman\Location\Drivers\MaxMind",
    ]);
});
