<?php

namespace Stevebauman\Location\Tests;

use Illuminate\Support\Fluent;
use Mockery as m;
use Stevebauman\Location\Drivers\Ip2locationio;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

it('it can process fluent response', function () {
    $driver = m::mock(Ip2locationio::class)->makePartial();

    $response = new Fluent([
        'country_name' => 'United States of America',
        'country_code' => 'US',
        'region_name' => 'California',
        'city_name' => 'Mountain View',
        'zip_code' => '94043',
        'latitude' => '37.405992',
        'longitude' => '-122.078515',
        'time_zone' => '-07:00',
    ]);

    $driver
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('process')->once()->andReturn($response);

    Location::setDriver($driver);

    $position = Location::get();

    expect($position)->toBeInstanceOf(Position::class);

    expect($position->toArray())->toEqual([
        'countryName' => 'United States of America',
        'countryCode' => 'US',
        'regionCode' => null,
        'regionName' => 'California',
        'cityName' => 'Mountain View',
        'zipCode' => '94043',
        'isoCode' => null,
        'postalCode' => '94043',
        'latitude' => '37.405992',
        'longitude' => '-122.078515',
        'metroCode' => null,
        'areaCode' => null,
        'ip' => '66.102.0.0',
        'currencyCode' => null,
        'timezone' => '-07:00',
        'isp' => null,
        'asn' => null,
        'asName' => null,
        'domain' => null,
        'netSpeed' => null,
        'iddCode' => null,
        'weatherStationCode' => null,
        'weatherStationName' => null,
        'mcc' => null,
        'mnc' => null,
        'mobileBrand' => null,
        'elevation' => null,
        'usageType' => null,
        'addressType' => null,
        'isProxy' => null,
        'driver' => get_class($driver),
    ]);
});
