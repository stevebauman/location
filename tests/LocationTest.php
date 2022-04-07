<?php

namespace Stevebauman\Location\Tests;

use Mockery as m;
use Stevebauman\Location\Drivers\Driver;
use Stevebauman\Location\Exceptions\DriverDoesNotExistException;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

it('can fallback to other drivers', function () {
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
});

it('throws an exception when the driver does not exist', function () {
    config(['location.driver' => 'Test']);

    Location::get();
})->throws(DriverDoesNotExistException::class);
