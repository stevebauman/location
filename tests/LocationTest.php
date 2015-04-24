<?php

namespace Stevebauman\Location\Tests;

use Mockery as m;
use Stevebauman\Location\Location;

class LocationTest extends \PHPUnit_Framework_TestCase
{
    protected $app;

    protected $config;

    protected $session;

    protected $location;

    protected function setUp()
    {
        parent::setUp();

        $this->app = m::mock('Illuminate\Foundation\Application');

        $this->config = m::mock('Illuminate\Config\Repository');

        $this->session = m::mock('Illuminate\Session\SessionManager');
    }

    public function testLocationDriverFreeGeoIp()
    {
        $this->config->shouldReceive('get')->andReturnValues([
            'FreeGeoIp',
            'Stevebauman\Location\Drivers\\',
            'http://freegeoip.net/json/',
            '66.102.0.0'
        ]);

        $this->location = new Location($this->app, $this->config, $this->session);

        $this->session
            ->shouldReceive('forget')->once()->andReturn(true)
            ->shouldReceive('has')->once()->andReturn(false)
            ->shouldReceive('set')->once()->andReturn(true);

        $location = $this->location->get();

        $this->assertFalse($location->error);
        $this->assertEquals('66.102.0.0', $location->ip);
        $this->assertEquals('Stevebauman\Location\Drivers\FreeGeoIp', $location->driver);
    }

    public function testLocationDriverGeoPlugin()
    {
        $this->config->shouldReceive('get')->andReturnValues([
            'GeoPlugin',
            'Stevebauman\Location\Drivers\\',
            'http://www.geoplugin.net/php.gp?ip=',
            '66.102.0.0'
        ]);

        $this->location = new Location($this->app, $this->config, $this->session);

        $this->session
            ->shouldReceive('forget')->once()->andReturn(true)
            ->shouldReceive('has')->once()->andReturn(false)
            ->shouldReceive('set')->once()->andReturn(true);

        $location = $this->location->get();

        $this->assertFalse($location->error);
        $this->assertEquals('66.102.0.0', $location->ip);
        $this->assertEquals('Stevebauman\Location\Drivers\GeoPlugin', $location->driver);
    }

    public function testLocationDriverIpInfo()
    {
        $this->config->shouldReceive('get')->andReturnValues([
            'IpInfo',
            'Stevebauman\Location\Drivers\\',
            'http://ipinfo.io/',
            '66.102.0.0'
        ]);

        $this->location = new Location($this->app, $this->config, $this->session);

        $this->session
            ->shouldReceive('forget')->once()->andReturn(true)
            ->shouldReceive('has')->once()->andReturn(false)
            ->shouldReceive('set')->once()->andReturn(true);

        $location = $this->location->get();

        $this->assertFalse($location->error);
        $this->assertEquals('66.102.0.0', $location->ip);
        $this->assertEquals('Stevebauman\Location\Drivers\IpInfo', $location->driver);
    }

    public function testLocationDriverTelize()
    {
        $this->config->shouldReceive('get')->andReturnValues([
            'Telize',
            'Stevebauman\Location\Drivers\\',
            'http://www.telize.com/geoip/',
            '66.102.0.0'
        ]);

        $this->location = new Location($this->app, $this->config, $this->session);

        $this->session
            ->shouldReceive('forget')->once()->andReturn(true)
            ->shouldReceive('has')->once()->andReturn(false)
            ->shouldReceive('set')->once()->andReturn(true);

        $location = $this->location->get();

        $this->assertFalse($location->error);
        $this->assertEquals('66.102.0.0', $location->ip);
        $this->assertEquals('Stevebauman\Location\Drivers\Telize', $location->driver);
    }

    public function testLocationDriverNotFoundException()
    {
        $this->config->shouldReceive('get')->andReturnValues([
            'test',
            'test',
        ]);

        $this->setExpectedException('Stevebauman\Location\Exceptions\DriverDoesNotExistException');

        $this->location = new Location($this->app, $this->config, $this->session);
    }

}