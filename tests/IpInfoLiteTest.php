<?php

namespace Stevebauman\Location\Tests;

use Illuminate\Support\Fluent;
use Mockery as m;
use Stevebauman\Location\Drivers\IpInfoLite;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

it('it can process fluent response', function () {
    $driver = m::mock(IpInfoLite::class)->makePartial();

    $attributes = [
        'asn' => 'AS15169',
        'as_name' => 'Google LLC',
        'as_domain' => 'google.com',
        'country' => 'United States',
        'country_code' => 'US',
        'continent_code' => 'NA',
        'continent' => 'North America',
    ];

    $driver
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
        'cityName' => null,
        'zipCode' => null,
        'isoCode' => null,
        'postalCode' => null,
        'latitude' => null,
        'longitude' => null,
        'metroCode' => null,
        'areaCode' => null,
        'ip' => '66.102.0.0',
        'timezone' => null,
        'driver' => get_class($driver),
    ]);
});
