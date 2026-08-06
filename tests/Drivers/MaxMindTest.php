<?php

namespace Stevebauman\Location\Tests\Drivers;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Mockery as m;
use Stevebauman\Location\Commands\Update;
use Stevebauman\Location\Drivers\MaxMind;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

it('can update database', function () {
    config([
        'location.maxmind.license_key' => '123',
        'location.maxmind.local.url' => 'http://example.com',
    ]);

    Http::fake([
        'http://example.com' => Http::response(file_get_contents(__DIR__.'/../fixtures/maxmind.tar.gz')),
    ]);

    $this->artisan(Update::class)->assertSuccessful();

    expect(database_path('maxmind/GeoLite2-City.mmdb'))->toBeFile();
});

it('can update database on configured storage disk', function () {
    Storage::fake('maxmind');
    Storage::fake('maxmind-cache');

    $localPath = Storage::disk('maxmind-cache')->path('GeoLite2-City.mmdb');

    config([
        'location.maxmind.license_key' => '123',
        'location.maxmind.local.path' => $localPath,
        'location.maxmind.local.url' => 'http://example.com',
        'location.maxmind.storage.disk' => 'maxmind',
        'location.maxmind.storage.path' => 'databases/GeoLite2-City.mmdb',
    ]);

    Http::fake([
        'http://example.com' => Http::response(file_get_contents(__DIR__.'/../fixtures/maxmind.tar.gz')),
    ]);

    app(MaxMind::class)->update(m::mock(Command::class));

    Storage::disk('maxmind')->assertExists('databases/GeoLite2-City.mmdb');

    expect($localPath)->toBeFile()
        ->and($localPath.'.version')->toBeFile();
});

it('can use database from configured storage disk', function () {
    Storage::fake('maxmind');
    Storage::fake('maxmind-cache');

    $localPath = Storage::disk('maxmind-cache')->path('GeoLite2-City.mmdb');

    config([
        'location.testing.enabled' => false,
        'location.driver' => MaxMind::class,
        'location.fallbacks' => [],
        'location.maxmind.local.path' => $localPath,
        'location.maxmind.local.type' => 'city',
        'location.maxmind.storage.disk' => 'maxmind',
        'location.maxmind.storage.path' => 'databases/GeoLite2-City.mmdb',
        'location.maxmind.storage.ttl' => 3600,
    ]);

    Storage::disk('maxmind')->put('databases/GeoLite2-City.mmdb', file_get_contents(
        __DIR__.'/../fixtures/GeoLite2-City-Test.mmdb'
    ));

    $position = Location::get('2.125.160.216');

    expect($position)->toBeInstanceOf(Position::class)
        ->and($position->cityName)->toBe('Boxford')
        ->and($localPath)->toBeFile()
        ->and($localPath.'.version')->toBeFile();
});

it('refreshes database from configured storage disk after ttl expires', function () {
    Storage::fake('maxmind');
    Storage::fake('maxmind-cache');

    $storagePath = 'databases/GeoLite2.mmdb';
    $localPath = Storage::disk('maxmind-cache')->path('GeoLite2.mmdb');
    $cityDatabase = __DIR__.'/../fixtures/GeoLite2-City-Test.mmdb';
    $countryDatabase = __DIR__.'/../fixtures/GeoLite2-Country-Test.mmdb';

    config([
        'location.testing.enabled' => false,
        'location.driver' => MaxMind::class,
        'location.fallbacks' => [],
        'location.maxmind.local.path' => $localPath,
        'location.maxmind.local.type' => 'city',
        'location.maxmind.storage.disk' => 'maxmind',
        'location.maxmind.storage.path' => $storagePath,
        'location.maxmind.storage.ttl' => 3600,
    ]);

    Storage::disk('maxmind')->put($storagePath, file_get_contents($cityDatabase));

    Location::get('2.125.160.216');

    expect(md5_file($localPath))->toBe(md5_file($cityDatabase));

    Storage::disk('maxmind')->put($storagePath, file_get_contents($countryDatabase));
    touch(Storage::disk('maxmind')->path($storagePath), time() + 10);

    Location::get('2.125.160.216');

    expect(md5_file($localPath))->toBe(md5_file($cityDatabase));

    touch($localPath.'.version', time() - 3601);
    clearstatcache(true, $localPath.'.version');

    Location::get('2.125.160.216');

    expect(md5_file($localPath))->toBe(md5_file($countryDatabase));
});

it('can process fluent response', function () {
    $driver = m::mock(MaxMind::class);

    $attributes = [
        'country' => 'United States',
        'country_code' => 'US',
        'city' => 'Long Beach',
        'postal' => 'W7W5L1',
        'metro_code' => '5555',
        'latitude' => '50',
        'longitude' => '50',
        'timezone' => 'America/Toronto',
    ];

    $driver
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('process')->once()->andReturn(new Fluent($attributes));

    Location::setDriver($driver);

    $position = Location::get();

    expect($position)->toBeInstanceOf(Position::class);

    expect($position->toArray())->toEqual([
        'countryName' => 'United States',
        'currencyCode' => null,
        'countryCode' => 'US',
        'regionCode' => null,
        'regionName' => null,
        'cityName' => 'Long Beach',
        'zipCode' => null,
        'isoCode' => 'US',
        'postalCode' => 'W7W5L1',
        'latitude' => '50',
        'longitude' => '50',
        'metroCode' => '5555',
        'areaCode' => null,
        'ip' => '66.102.0.0',
        'timezone' => 'America/Toronto',
        'driver' => get_class($driver),
    ]);
});

it('can use city database', function () {
    config(['location.testing.enabled' => false]);
    config(['location.driver' => MaxMind::class]);
    config(['location.maxmind.local.type' => 'city']);
    config(['location.maxmind.local.path' => __DIR__.'/../fixtures/GeoLite2-City-Test.mmdb']);

    $position = Location::get('2.125.160.216');

    expect($position)->toBeInstanceOf(Position::class);

    expect($position->toArray())->toEqual([
        'ip' => '2.125.160.216',
        'countryName' => 'United Kingdom',
        'currencyCode' => null,
        'countryCode' => 'GB',
        'regionCode' => 'WBK',
        'regionName' => 'West Berkshire',
        'cityName' => 'Boxford',
        'zipCode' => null,
        'isoCode' => 'GB',
        'postalCode' => 'OX1',
        'latitude' => '51.75',
        'longitude' => '-1.25',
        'metroCode' => '',
        'areaCode' => null,
        'timezone' => 'Europe/London',
        'driver' => "Stevebauman\Location\Drivers\MaxMind",
    ]);
});

it('can use country database', function () {
    config(['location.testing.enabled' => false]);
    config(['location.driver' => MaxMind::class]);
    config(['location.maxmind.local.type' => 'country']);
    config(['location.maxmind.local.path' => __DIR__.'/../fixtures/GeoLite2-Country-Test.mmdb']);

    $position = Location::get('2.125.160.216');

    expect($position)->toBeInstanceOf(Position::class);

    expect($position->toArray())->toEqual([
        'ip' => '2.125.160.216',
        'countryName' => 'United Kingdom',
        'currencyCode' => null,
        'countryCode' => 'GB',
        'regionCode' => null,
        'regionName' => null,
        'cityName' => null,
        'zipCode' => null,
        'isoCode' => 'GB',
        'postalCode' => null,
        'latitude' => null,
        'longitude' => null,
        'metroCode' => null,
        'areaCode' => null,
        'timezone' => null,
        'driver' => "Stevebauman\Location\Drivers\MaxMind",
    ]);
});
