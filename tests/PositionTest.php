<?php

namespace Stevebauman\Location\Tests;

use Illuminate\Support\Arr;
use Stevebauman\Location\Position;

it('can be created with attributes array', function (array $attributes, array $expected) {
    $position = Position::make($attributes);

    expect(Arr::only($position->toArray(), array_keys($expected)))->toBe($expected);
})->with([
    // Camel casing attributes:
    [
        [
            'ip' => '127.0.0.1',
            'driver' => 'local',
            'countryName' => 'foo',
            'countryCode' => 'bar',
        ],
        [
            'ip' => '127.0.0.1',
            'driver' => 'local',
            'countryName' => 'foo',
            'countryCode' => 'bar',
        ],
    ],
    // Snake casing attributes:
    [
        [
            'ip' => '127.0.0.1',
            'driver' => 'local',
            'country_name' => 'foo',
            'country_code' => 'bar',
        ],
        [
            'ip' => '127.0.0.1',
            'driver' => 'local',
            'countryName' => 'foo',
            'countryCode' => 'bar',
        ],
    ],
]);

it('returns empty', function () {
    $position = new Position;

    $position->ip = '127.0.0.1';
    $position->driver = 'foo';

    expect($position->isEmpty())->toBeTrue();
});

it('does not return empty', function () {
    $position = new Position;

    $position->isoCode = 'foo';

    expect($position->isEmpty())->toBeFalse();
});
