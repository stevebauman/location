<?php

namespace Stevebauman\Location\Tests;

use Mockery as m;
use Illuminate\Support\Fluent;
use Stevebauman\Location\Position;
use Stevebauman\Location\Drivers\IpApi;
use Stevebauman\Location\Drivers\IpInfo;
use Stevebauman\Location\Drivers\Driver;
use Stevebauman\Location\Drivers\MaxMind;
use Stevebauman\Location\Drivers\GeoPlugin;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Exceptions\DriverDoesNotExistException;

class LocationTest extends TestCase
{
    public function test_driver_process()
    {
        $driver = m::mock(Driver::class);

        Location::setDriver($driver);

        $position = new Position();
        $position->cityName = 'foo';

        $driver
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('process')->once()->andReturn(new Fluent(['foo']))
            ->shouldReceive('hydrate')->once()->andReturn($position);

        $this->assertEquals($position, Location::get());
    }

    public function test_driver_fallback()
    {
        $fallback = m::mock(Driver::class)
            ->shouldAllowMockingProtectedMethods();

        $fallback
            ->shouldReceive('get')->once()->andReturn(new Position());

        $driver = m::mock(Driver::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $driver
            ->shouldReceive('process')->once()->andReturn(false);

        $driver->fallback($fallback);

        Location::setDriver($driver);

        $this->assertInstanceOf(Position::class, Location::get());
    }

    public function test_driver_does_not_exist()
    {
        config(['location.driver' => 'Test']);

        $this->expectException(DriverDoesNotExistException::class);

        Location::get();
    }

    public function test_ip_api()
    {
        $driver = m::mock(IpApi::class)->makePartial();

        $attributes = [
            'country' => 'United States',
            'countryCode' => 'US',
            'region' => 'CA',
            'regionName' => 'California',
            'city' => 'Long Beach',
            'zip' => '55555',
            'lat' => '50',
            'lon' => '50',
        ];

        $driver
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('process')->once()->andReturn(new Fluent($attributes));

        Location::setDriver($driver);

        $position = Location::get();

        $this->assertInstanceOf(Position::class, $position);
        $this->assertEquals([
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
            'driver' => get_class($driver),
        ], $position->toArray());
    }

    public function test_geo_plugin()
    {
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
        ];

        $driver
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('process')->once()->andReturn(new Fluent($attributes));

        Location::setDriver($driver);

        $position = Location::get();

        $this->assertInstanceOf(Position::class, $position);
        $this->assertEquals([
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
            'driver' => get_class($driver),
        ], $position->toArray());
    }

    public function test_ip_info()
    {
        $driver = m::mock(IpInfo::class)->makePartial();

        $attributes = [
            'country' => 'US',
            'region' => 'California',
            'city' => 'Long Beach',
            'postal' => '55555',
            'loc' => '50,50',
        ];

        $driver
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('process')->once()->andReturn(new Fluent($attributes));

        Location::setDriver($driver);

        $position = Location::get();

        $this->assertInstanceOf(Position::class, $position);
        $this->assertEquals([
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
            'driver' => get_class($driver),
        ], $position->toArray());
    }

    public function test_max_mind()
    {
        $driver = m::mock(MaxMind::class);

        $attributes = [
            'country' => 'United States',
            'country_code' => 'US',
            'city' => 'Long Beach',
            'postal' => '55555',
            'metro_code' => '5555',
            'latitude' => '50',
            'longitude' => '50',
        ];

        $driver
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('process')->once()->andReturn(new Fluent($attributes));

        Location::setDriver($driver);

        $position = Location::get();

        $this->assertInstanceOf(Position::class, $position);
        $this->assertEquals([
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
            'driver' => get_class($driver),
        ], $position->toArray());
    }

    public function test_position_is_empty()
    {
        $position = new Position();
        $position->ip = '192.168.1.1';
        $position->driver = 'foo';

        $this->assertTrue($position->isEmpty());

        $position = new Position();
        $position->isoCode = 'foo';
        $this->assertFalse($position->isEmpty());
    }
}
