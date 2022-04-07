<?php

namespace Stevebauman\Location\Tests;

use Illuminate\Support\Fluent;
use Mockery as m;
use Stevebauman\Location\Drivers\IpData;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

it('it can process fluent response', function () {
    $driver = m::mock(IpData::class);

    $attributes = [
        'country_name' => 'United States',
        'country_code' => 'US',
        'region_code' => 'CA',
        'region' => 'California',
        'city' => 'Long Beach',
        'postal' => '55555',
        'latitude' => '50',
        'longitude' => '50',
        'time_zone' => ['name' => 'America/Toronto'],
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
        'latitude' => '50',
        'longitude' => '50',
        'metroCode' => null,
        'areaCode' => null,
        'ip' => '66.102.0.0',
        'timezone' => 'America/Toronto',
        'driver' => get_class($driver),
    ]);
});
