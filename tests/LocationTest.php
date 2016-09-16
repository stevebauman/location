<?php

namespace Stevebauman\Location\Tests;

use Illuminate\Support\Fluent;
use Mockery as m;
use Stevebauman\Location\Position;
use Stevebauman\Location\Drivers\Driver;
use Stevebauman\Location\Facades\Location;

class LocationTest extends TestCase
{
    public function test_retrieval()
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
}
