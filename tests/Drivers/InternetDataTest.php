<?php

namespace Stevebauman\Location\Tests\Drivers;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Fluent;
use Mockery as m;
use Stevebauman\Location\Drivers\InternetData;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

it('it can process fluent response', function () {
    $driver = m::mock(InternetData::class);

    $attributes = [
        'ip' => '66.102.0.0',
        'location' => [
            'country' => 'US',
            'country_name' => 'United States',
            'timezone' => 'America/Los_Angeles',
            'currency_code' => 'USD',
            'region' => 'California',
            'region_code' => 'US-CA',
            'city' => 'Long Beach',
            'postal_code' => '55555',
            'latitude' => 50.5,
            'longitude' => 50.5,
        ],
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
        'regionCode' => 'CA',
        'regionName' => 'California',
        'cityName' => 'Long Beach',
        'zipCode' => '55555',
        'isoCode' => null,
        'postalCode' => '55555',
        'latitude' => '50.5',
        'longitude' => '50.5',
        'metroCode' => null,
        'areaCode' => null,
        'ip' => '66.102.0.0',
        'currencyCode' => 'USD',
        'timezone' => 'America/Los_Angeles',
        'driver' => get_class($driver),
    ]);
});

it('sends the token as a bearer header', function () {
    config(['location.internetdata.token' => 'secret']);

    Http::fake([
        'api.internetdata.io/*' => Http::response(['location' => ['country' => 'US']]),
    ]);

    Location::setDriver(new InternetData);

    expect(Location::get()->countryCode)->toBe('US');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.internetdata.io/66.102.0.0'
        && $request->hasHeader('Authorization', 'Bearer secret'));
});

it('sends no token without one', function () {
    Http::fake([
        'api.internetdata.io/*' => Http::response(['location' => ['country' => 'US']]),
    ]);

    Location::setDriver(new InternetData);

    expect(Location::get()->countryCode)->toBe('US');

    Http::assertSent(fn (Request $request) => ! $request->hasHeader('Authorization'));
});
