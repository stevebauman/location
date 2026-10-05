<?php

namespace Stevebauman\Location\Tests\Drivers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Fluent;
use Mockery as m;
use Stevebauman\Location\Drivers\IpGeolocation;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

it('it can process fluent response', function () {
    $driver = m::mock(IpGeolocation::class)->makePartial();

    $response = new Fluent([
        'ip' => '66.102.0.0',
        'location' => [
            'continent_code' => 'NA',
            'continent_name' => 'North America',
            'country_code2' => 'US',
            'country_code3' => 'USA',
            'country_name' => 'United States',
            'state_prov' => 'California',
            'state_code' => 'US-CA',
            'district' => 'Santa Clara County',
            'city' => 'Mountain View',
            'zipcode' => '94043',
            'latitude' => '37.42240',
            'longitude' => '-122.08421',
        ],
        'currency' => ['code' => 'USD'],
        'time_zone' => ['name' => 'America/Los_Angeles'],
    ]);

    $driver
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('process')->once()->andReturn($response);

    Location::setDriver($driver);

    $position = Location::get();

    expect($position)->toBeInstanceOf(Position::class);

    expect($position->toArray())->toEqual([
        'countryName' => 'United States',
        'currencyCode' => 'USD',
        'countryCode' => 'US',
        'regionCode' => 'US-CA',
        'regionName' => 'California',
        'cityName' => 'Mountain View',
        'zipCode' => '94043',
        'isoCode' => null,
        'postalCode' => '94043',
        'latitude' => '37.42240',
        'longitude' => '-122.08421',
        'metroCode' => null,
        'areaCode' => null,
        'ip' => '66.102.0.0',
        'timezone' => 'America/Los_Angeles',
        'driver' => get_class($driver),
    ]);
});

it('requests the v3 endpoint with the api key and a field filter', function () {
    config(['location.ipgeolocation.token' => 'secret']);

    expect((new IpGeolocation)->url('2001:4860:4860::8888'))->toBe(
        'https://api.ipgeolocation.io/v3/ipgeo?apiKey=secret&ip=2001%3A4860%3A4860%3A%3A8888&fields=location%2Ccurrency.code%2Ctime_zone.name'
    );
});

it('returns false when the api responds with an error', function () {
    Http::fake([
        'api.ipgeolocation.io/*' => Http::response(['message' => "'10.0.0.1' is a bogon IP address."], 423),
    ]);

    Location::setDriver(new IpGeolocation);

    expect(Location::get('10.0.0.1'))->toBeFalse();
});
