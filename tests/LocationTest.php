<?php

namespace Stevebauman\Location\Tests;

use Mockery as m;
use Illuminate\Support\Fluent;
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

    public function test_driver_does_not_exist()
    {
        config(['location.driver' => 'Test']);

        $this->expectException(DriverDoesNotExistException::class);

        Location::get();
    }

    public function test_free_geo_ip()
    {
        Location::setDriver(new FreeGeoIp());

        $this->assertInstanceOf(Position::class, Location::get());
    }

    public function test_geo_plugin()
    {
        Location::setDriver(new GeoPlugin());

        $this->assertInstanceOf(Position::class, Location::get());
    }

    public function test_ip_info()
    {
        Location::setDriver(new IpInfo());

        $this->assertInstanceOf(Position::class, Location::get());
    }
}
