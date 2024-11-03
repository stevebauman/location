<?php

use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

it('can fake position', function () {
    $position = Position::make();

    Location::fake(['127.0.0.1' => $position]);

    expect(Location::get())->toBe($position);
    expect(Location::get('127.0.0.1'))->toBe($position);
});

it('can handle multiple fake positions', function () {
    Location::fake([
        '127.0.0.1' => $position1 = Position::make(),
        '192.168.1.1' => $position2 = Position::make(),
    ]);

    expect(Location::get('127.0.0.1'))->toBe($position1);
    expect(Location::get('192.168.1.1'))->toBe($position2);
    expect(Location::get('192.168.1.0'))->toBeFalse();
});

it('can fake position with asterisks', function () {
    $position = Position::make();

    Location::fake(['127.*.*.1' => $position]);

    expect(Location::get('127.0.0.1'))->toBe($position);
});

it('returns false when no fake position is given', function () {
    Location::fake();

    expect(Location::get('127.0.0.1'))->toBeFalse();
});

it('forwards missing method calls onto location manager', function () {
    Location::fake();

    expect(Location::drivers())->not->toBeEmpty();
});
