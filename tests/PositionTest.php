<?php

namespace Stevebauman\Location\Tests;

use Stevebauman\Location\Position;

it('returns empty', function () {
    $position = new Position();

    $position->ip = '192.168.1.1';
    $position->driver = 'foo';

    expect($position->isEmpty())->toBeTrue();
});

it('does not return empty', function () {
    $position = new Position();

    $position->isoCode = 'foo';

    expect($position->isEmpty())->toBeFalse();
});
