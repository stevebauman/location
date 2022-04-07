<?php

namespace Stevebauman\Location\Tests;

use Illuminate\Support\Fluent;
use Mockery as m;
use Stevebauman\Location\Drivers\GeoPlugin;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

it('it can process fluent response', function () {
    $driver = m::mock(GeoPlugin::class)->makePartial();

    $attributes = [
        'geoplugin_countryCode' => 'US',
        'geoplugin_countryName' => 'United States',
        'geoplugin_regionName' => 'California',
        'geoplugin_regionCode' => 'CA',
        'geoplugin_city' => 'Long Beach',
        'geoplugin_latitude' => '50',
        'geoplugin_longitude' => '50',
        'geoplugin_areaCode' => '555',
        'geoplugin_timezone' => 'America/Toronto',
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
        'regionCode' => 'CA',
        'regionName' => 'California',
        'cityName' => 'Long Beach',
        'zipCode' => null,
        'isoCode' => null,
        'postalCode' => null,
        'latitude' => '50',
        'longitude' => '50',
        'metroCode' => null,
        'areaCode' => '555',
        'ip' => '66.102.0.0',
        'timezone' => 'America/Toronto',
        'driver' => get_class($driver),
    ]);
});
