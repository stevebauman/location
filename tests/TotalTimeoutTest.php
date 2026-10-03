<?php

namespace Stevebauman\Location\Tests;

use ArrayObject;
use Illuminate\Support\Facades\Http;
use Stevebauman\Location\Drivers\GeoPlugin;
use Stevebauman\Location\Drivers\HttpDriver;
use Stevebauman\Location\Drivers\IpApi;
use Stevebauman\Location\Drivers\IpInfo;
use Stevebauman\Location\Facades\Location;

$captured = new ArrayObject;

beforeEach(function () use ($captured) {
    $captured->exchangeArray([]);

    HttpDriver::resolveHttpBy(function ($http) use ($captured) {
        $captured[] = $http->getOptions();

        return $http;
    });

    config([
        'location.driver' => IpApi::class,
        'location.fallbacks' => [IpInfo::class, GeoPlugin::class],
        'location.http' => ['timeout' => 3, 'connect_timeout' => 3],
        'location.total_timeout' => null,
    ]);

    Http::fake(function () {
        usleep(200_000);

        return Http::response('', 500);
    });
});

afterEach(fn () => HttpDriver::resolveHttpBy(fn ($http) => $http));

it('leaves the configured timeouts alone without a total timeout', function () use ($captured) {
    expect(Location::get('8.8.8.8'))->toBeFalse();

    foreach ($captured as $options) {
        expect($options['timeout'])->toBe(3)->and($options['connect_timeout'])->toBe(3);
    }

    expect($captured)->toHaveCount(3);
});

it('caps each driver to the time left in the total budget', function () use ($captured) {
    config(['location.total_timeout' => 2]);

    expect(Location::get('8.8.8.8'))->toBeFalse();
    expect($captured)->toHaveCount(3);

    // Each driver burns roughly 200ms, so the budget keeps shrinking.
    expect($captured[0]['timeout'])->toBeLessThanOrEqual(2.0)
        ->and($captured[1]['timeout'])->toBeLessThan($captured[0]['timeout'])
        ->and($captured[2]['timeout'])->toBeLessThan($captured[1]['timeout']);
});

it('caps timeouts that are unset or disabled', function () use ($captured) {
    config(['location.http' => ['timeout' => 0], 'location.total_timeout' => 1]);

    expect(Location::get('8.8.8.8'))->toBeFalse();

    expect($captured[0]['timeout'])->toBeGreaterThan(0)->toBeLessThanOrEqual(1.0)
        ->and($captured[0]['connect_timeout'])->toBeGreaterThan(0)->toBeLessThanOrEqual(1.0);
});

it('stops calling drivers once the total budget is spent', function () use ($captured) {
    // The first driver alone burns more than the budget, so nothing follows it.
    config(['location.total_timeout' => 0.05]);

    expect(Location::get('8.8.8.8'))->toBeFalse();
    expect($captured)->toHaveCount(1);

    // The budget belongs to the lookup, not to the process.
    Location::get('8.8.4.4');

    expect($captured)->toHaveCount(2);
});
