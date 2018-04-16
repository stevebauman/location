<?php

namespace Stevebauman\Location\Tests;

use Mockery as m;
use Illuminate\Support\Fluent;
use Stevebauman\Location\Drivers\MaxMind;
use Stevebauman\Location\Position;
use Stevebauman\Location\Drivers\IpInfo;
use Stevebauman\Location\Drivers\Driver;
use Stevebauman\Location\Drivers\FreeGeoIp;
use Stevebauman\Location\Drivers\GeoPlugin;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Exceptions\DriverDoesNotExistException;

class LocationTest extends TestCase
{
    public function test_driver_process()
    {
        $driver = m::mock(Driver::class);

        Location::setDriver($driver);

        $driver
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('process')->once()->andReturn(new Fluent())
            ->shouldReceive('hydrate')->once()->andReturn(new Position());

        $this->assertInstanceOf(Position::class, Location::get());
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

    public function test_free_geo_ip()
    {
        $driver = m::mock(FreeGeoIp::class)->makePartial();

        $driver
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('process')->once()->andReturn(new Fluent());

        Location::setDriver($driver);

        $this->assertInstanceOf(Position::class, Location::get());
    }

    public function test_geo_plugin()
    {
        $driver = m::mock(GeoPlugin::class)->makePartial();

        $driver
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('process')->once()->andReturn(new Fluent());

        Location::setDriver($driver);

        $this->assertInstanceOf(Position::class, Location::get());
    }

    public function test_ip_info()
    {
        $driver = m::mock(IpInfo::class)->makePartial();

        $driver
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('process')->once()->andReturn(new Fluent());

        Location::setDriver($driver);

        $this->assertInstanceOf(Position::class, Location::get());
    }

    public function test_max_mind()
    {
        $driver = m::mock(MaxMind::class);

        $driver
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('process')->once()->andReturn(new Fluent())
            ->shouldReceive('hydrate')->once()->andReturn(new Position());

        Location::setDriver($driver);

        $this->assertInstanceOf(Position::class, Location::get());
    }
}
