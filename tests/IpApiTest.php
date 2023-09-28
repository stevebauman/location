<?php

namespace Stevebauman\Location\Tests;

use Illuminate\Support\Fluent;
use Mockery as m;
use Stevebauman\Location\Drivers\IpApi;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

it('it can process fluent response', function () {
    $driver = m::mock(IpApi::class)->makePartial();

    $response = new Fluent([
        'country' => 'United States',
        'countryCode' => 'US',
        'region' => 'CA',
        'regionName' => 'California',
        'city' => 'Long Beach',
        'zip' => '55555',
        'lat' => '50',
        'lon' => '50',
        'currency' => 'USD',
        'timezone' => 'America/Toronto',
    ]);

    $driver
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('process')->once()->andReturn($response);

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
        'postalCode' => null,
        'latitude' => '50',
        'longitude' => '50',
        'metroCode' => null,
        'areaCode' => 'CA',
        'ip' => '66.102.0.0',
        'currencyCode' => 'USD',
        'timezone' => 'America/Toronto',
        'driver' => get_class($driver),
    ]);
});
