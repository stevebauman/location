<?php

namespace Stevebauman\Location\Tests\Drivers;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Mockery as m;
use RuntimeException;
use Stevebauman\Location\Commands\Update;
use Stevebauman\Location\Drivers\IpApi;
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

    touch($localPath.'.checked', time() - 3601);

    Location::get('2.125.160.216');

    expect(md5_file($localPath))->toBe(md5_file($countryDatabase));
});

it('uses the local database when shared storage is unavailable', function () {
    $local = Storage::fake('maxmind-cache');
    $path = $local->path('database.mmdb');
    copy(__DIR__.'/../fixtures/GeoLite2-City-Test.mmdb', $path);

    config([
        'location.testing.enabled' => false,
        'location.driver' => MaxMind::class,
        'location.fallbacks' => [],
        'location.maxmind.local.path' => $path,
        'location.maxmind.storage.disk' => 'maxmind',
    ]);

    $storage = m::mock(Filesystem::class);
    $storage->shouldReceive('lastModified')->once()->andThrow(new RuntimeException('Unavailable'));
    Storage::shouldReceive('disk')->with('maxmind')->andReturn($storage);
    Http::fake();

    expect(Location::get('2.125.160.216')->cityName)->toBe('Boxford');
    expect(Location::get('2.125.160.216')->cityName)->toBe('Boxford');

    Http::assertNothingSent();
});

it('uses a fallback driver when neither a local nor shared database is available', function () {
    $local = Storage::fake('maxmind-cache');
    Storage::fake('maxmind');

    config([
        'location.testing.enabled' => false,
        'location.driver' => MaxMind::class,
        'location.fallbacks' => [IpApi::class],
        'location.maxmind.local.path' => $local->path('database.mmdb'),
        'location.maxmind.storage.disk' => 'maxmind',
    ]);

    Http::fake([
        'http://ip-api.com/*' => Http::response(['city' => 'Fallback city']),
    ]);

    $position = Location::get('2.125.160.216');

    expect($position->cityName)->toBe('Fallback city')
        ->and($position->driver)->toBe(IpApi::class);

    Http::assertSentCount(1);
});

it('returns false when no database or fallback is available', function () {
    $local = Storage::fake('maxmind-cache');
    Storage::fake('maxmind');

    config([
        'location.testing.enabled' => false,
        'location.driver' => MaxMind::class,
        'location.fallbacks' => [],
        'location.maxmind.local.path' => $local->path('database.mmdb'),
        'location.maxmind.storage.disk' => 'maxmind',
    ]);

    Http::fake();

    expect(Location::get('2.125.160.216'))->toBeFalse();

    Http::assertNothingSent();
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
        'zipCode' => 'W7W5L1',
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
        'zipCode' => 'OX1',
        'isoCode' => 'GB',
        'postalCode' => 'OX1',
        'latitude' => '51.75',
        'longitude' => '-1.25',
        'metroCode' => null,
        'areaCode' => null,
        'timezone' => 'Europe/London',
        'driver' => "Stevebauman\Location\Drivers\MaxMind",
    ]);

    // toEqual() treats '' and null as equal.
    expect($position->metroCode)->toBeNull();
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

it('returns localized names from the city database using configured locales', function () {
    config(['location.testing.enabled' => false]);
    config(['location.driver' => MaxMind::class]);
    config(['location.maxmind.local.type' => 'city']);
    config(['location.maxmind.local.path' => __DIR__.'/../fixtures/GeoLite2-City-Test.mmdb']);
    config(['location.maxmind.web.locales' => ['de']]);

    $position = Location::get('89.160.20.112');

    expect($position->cityName)->toBe('Linköping')
        ->and($position->countryName)->toBe('Schweden')
        ->and($position->countryCode)->toBe('SE');

    expect(Location::get('81.2.69.142')->countryName)->toBe('Vereinigtes Königreich');
});

it('falls back to english names when the configured locale has no name', function () {
    config(['location.testing.enabled' => false]);
    config(['location.driver' => MaxMind::class]);
    config(['location.maxmind.local.type' => 'city']);
    config(['location.maxmind.local.path' => __DIR__.'/../fixtures/GeoLite2-City-Test.mmdb']);
    config(['location.maxmind.web.locales' => ['de']]);

    $position = Location::get('2.125.160.216');

    expect($position->cityName)->toBe('Boxford')
        ->and($position->regionName)->toBe('West Berkshire')
        ->and($position->countryName)->toBe('Vereinigtes Königreich');
});

it('returns localized names from the country database using configured locales', function () {
    config(['location.testing.enabled' => false]);
    config(['location.driver' => MaxMind::class]);
    config(['location.maxmind.local.type' => 'country']);
    config(['location.maxmind.local.path' => __DIR__.'/../fixtures/GeoLite2-Country-Test.mmdb']);
    config(['location.maxmind.web.locales' => ['de']]);

    $position = Location::get('89.160.20.112');

    expect($position->countryName)->toBe('Schweden')
        ->and($position->countryCode)->toBe('SE');
});

it('passes configured locales to the web service client with english appended', function (mixed $configured, array $expected) {
    config(['location.testing.enabled' => false]);
    config(['location.fallbacks' => []]);
    config(['location.maxmind.license_key' => 'key']);
    config(['location.maxmind.web.enabled' => true]);
    config(['location.maxmind.web.user_id' => 123]);
    config(['location.maxmind.web.locales' => $configured]);

    $driver = m::mock(MaxMind::class)->makePartial()->shouldAllowMockingProtectedMethods();

    $driver->shouldReceive('newClient')
        ->once()
        ->with(123, 'key', $expected, m::any())
        ->andThrow(new RuntimeException('No web service in tests'));

    Location::setDriver($driver);

    expect(Location::get('81.2.69.142'))->toBeFalse();
})->with([
    'single locale' => [['de'], ['de', 'en']],
    'english already last' => [['de', 'en'], ['de', 'en']],
    'english already first' => [['en', 'de'], ['en', 'de']],
    'empty list' => [[], ['en']],
    'string' => ['de', ['de', 'en']],
    'null' => [null, ['en']],
]);
