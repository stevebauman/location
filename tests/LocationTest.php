<?php

namespace Stevebauman\Location\Tests;

use Stevebauman\Location\Facades\Location;

class LocationTest extends FunctionalTestCase
{
    public function testLocationDriverTelize()
    {
        app('config')->set('location.selected_driver', 'Telize');

        $location = Location::get();

        $this->assertInstanceOf('Stevebauman\Location\Objects\Location', $location);
        $this->assertEquals('Stevebauman\Location\Drivers\Telize', $location->driver);
        $this->assertFalse($location->error);
    }

    public function testLocationDriverFreeGeoIp()
    {
        app('config')->set('location.selected_driver', 'FreeGeoIp');

        $location = Location::get();

        $this->assertInstanceOf('Stevebauman\Location\Objects\Location', $location);
        $this->assertEquals('Stevebauman\Location\Drivers\FreeGeoIp', $location->driver);
        $this->assertFalse($location->error);
    }

    public function testLocationDriverIpInfo()
    {
        app('config')->set('location.selected_driver', 'IpInfo');

        $location = Location::get();

        $this->assertInstanceOf('Stevebauman\Location\Objects\Location', $location);
        $this->assertEquals('Stevebauman\Location\Drivers\IpInfo', $location->driver);
        $this->assertFalse($location->error);
    }

    public function testLocationDriverGeoPlugin()
    {
        app('config')->set('location.selected_driver', 'GeoPlugin');

        $location = Location::get();

        $this->assertInstanceOf('Stevebauman\Location\Objects\Location', $location);
        $this->assertEquals('Stevebauman\Location\Drivers\GeoPlugin', $location->driver);
        $this->assertFalse($location->error);
    }
}
