<?php

namespace Stevebauman\Location\Tests;

use Illuminate\Support\Fluent;
use Mockery as m;
use Stevebauman\Location\Drivers\IpInfo;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

it('it can process fluent response', function () {
    $driver = m::mock(IpInfo::class)->makePartial();

    $attributes = [
        'country' => 'US',
        'region' => 'California',
        'city' => 'Long Beach',
        'postal' => '55555',
        'loc' => '50,50',
        'timezone' => 'America/Toronto',
    ];

    $driver
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('process')->once()->andReturn(new Fluent($attributes));

    Location::setDriver($driver);

    $position = Location::get();

    expect($position)->toBeInstanceOf(Position::class);

    expect($position->toArray())->toEqual([
        'countryName' => null,
        'countryCode' => 'US',
        'regionCode' => null,
        'regionName' => 'California',
        'cityName' => 'Long Beach',
        'zipCode' => '55555',
        'isoCode' => null,
        'postalCode' => null,
        'latitude' => '50',
        'longitude' => '50',
        'metroCode' => null,
        'areaCode' => null,
        'ip' => '66.102.0.0',
        'timezone' => 'America/Toronto',
        'driver' => get_class($driver),
    ]);
});
