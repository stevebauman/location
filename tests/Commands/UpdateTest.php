<?php

namespace Stevebauman\Location\Tests\Commands;

use Mockery as m;
use Stevebauman\Location\Commands\Update;
use Stevebauman\Location\Drivers\Driver;
use Stevebauman\Location\Drivers\Updatable;
use Stevebauman\Location\Facades\Location;

it('calls update on each updatable driver', function () {
    $updatable = m::mock(Driver::class, Updatable::class);
    $updatable->shouldReceive('update')->once();

    Location::shouldReceive('drivers')->once()->andReturn([$updatable]);

    $this->artisan(Update::class)->assertSuccessful();
});

it('skips drivers that are not updatable', function () {
    $nonUpdatable = m::mock(Driver::class);
    $nonUpdatable->shouldNotReceive('update');

    $updatable = m::mock(Driver::class, Updatable::class);
    $updatable->shouldReceive('update')->once();

    Location::shouldReceive('drivers')->once()->andReturn([$nonUpdatable, $updatable]);

    $this->artisan(Update::class)->assertSuccessful();
});

it('outputs progress for each updatable driver', function () {
    $updatable = m::mock(Driver::class, Updatable::class);
    $updatable->shouldReceive('update')->once();

    Location::shouldReceive('drivers')->once()->andReturn([$updatable]);

    $this->artisan(Update::class)
        ->expectsOutputToContain(sprintf('Updating driver [%s]...', $updatable::class))
        ->expectsOutputToContain(sprintf('Successfully updated driver [%s].', $updatable::class))
        ->expectsOutputToContain('All configured drivers have been updated.')
        ->assertSuccessful();
});

it('does not output driver progress when no updatable drivers exist', function () {
    $nonUpdatable = m::mock(Driver::class);
    $nonUpdatable->shouldNotReceive('update');

    Location::shouldReceive('drivers')->once()->andReturn([$nonUpdatable]);

    $this->artisan(Update::class)
        ->doesntExpectOutputToContain('Updating driver')
        ->expectsOutputToContain('All configured drivers have been updated.')
        ->assertSuccessful();
});

it('succeeds when no drivers are configured', function () {
    Location::shouldReceive('drivers')->once()->andReturn([]);

    $this->artisan(Update::class)
        ->expectsOutputToContain('All configured drivers have been updated.')
        ->assertSuccessful();
});

it('passes the command instance to the driver update method', function () {
    $updatable = m::mock(Driver::class, Updatable::class);
    $updatable->shouldReceive('update')
        ->once()
        ->with(m::type(Update::class));

    Location::shouldReceive('drivers')->once()->andReturn([$updatable]);

    $this->artisan(Update::class)->assertSuccessful();
});
