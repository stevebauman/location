<?php

namespace Stevebauman\Location\Tests\Drivers;

use GeoIp2\Database\Reader;
use GeoIp2\Model\City;
use GeoIp2\WebService\Client;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery as m;
use RuntimeException;
use Stevebauman\Location\Commands\Update;
use Stevebauman\Location\Drivers\MaxMind;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

beforeEach(function () {
    Storage::fake('maxmind');

    $local = Storage::fake('maxmind-local');

    config([
        'location.driver' => MaxMind::class,
        'location.fallbacks' => [],
        'location.testing.enabled' => false,
        'location.maxmind.license_key' => '123',
        'location.maxmind.local.url' => 'http://example.com',
        'location.maxmind.local.path' => $local->path('maxmind/GeoLite2-City.mmdb'),
        'location.maxmind.storage.disk' => 'maxmind',
        'location.maxmind.storage.path' => 'databases/city.mmdb',
        'location.maxmind.storage.ttl' => 3600,
    ]);

    Http::fake([
        'http://example.com' => fn () => Http::response(file_get_contents(__DIR__.'/../fixtures/maxmind.tar.gz')),
        '*' => Http::response('', 500),
    ]);
});

it('publishes the updated database and installs it locally', function () {
    $this->artisan(Update::class)->assertSuccessful();

    expect(hash_file('sha256', Storage::disk('maxmind')->path('databases/city.mmdb')))
        ->toBe(hash_file('sha256', config('location.maxmind.local.path')));

    Http::assertSentCount(1);
});

it('publishes the downloaded database when a lookup replaces the local copy', function () {
    $expected = null;

    $driver = m::mock(MaxMind::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $driver->shouldReceive('getStorageDisk')->once()->andReturnUsing(function () use (&$expected) {
        $path = config('location.maxmind.local.path');

        $expected = hash_file('sha256', $path);

        copy(__DIR__.'/../fixtures/GeoLite2-City-Test.mmdb', $path.'.replacement');
        rename($path.'.replacement', $path);

        return 'maxmind';
    });

    $driver->update(new Update);

    expect(hash_file('sha256', Storage::disk('maxmind')->path('databases/city.mmdb')))->toBe($expected);
});

it('fails the update when the shared database cannot be published', function () {
    $storage = m::mock(Filesystem::class);
    $storage->shouldReceive('put')->once()
        ->with('databases/city.mmdb', m::on(fn ($stream) => is_resource($stream)))
        ->andReturn(false);

    Storage::partialMock()->shouldReceive('disk')->with('maxmind')->andReturn($storage);

    $this->artisan(Update::class)->run();
})->throws(RuntimeException::class, 'Failed to publish MaxMind database.');

it('downloads the shared database on the first local lookup', function () {
    Storage::disk('maxmind')->put('databases/city.mmdb', file_get_contents(__DIR__.'/../fixtures/GeoLite2-City-Test.mmdb'));

    $position = Location::get('2.125.160.216');

    expect($position)->toBeInstanceOf(Position::class);
    expect($position->cityName)->toBe('Boxford');
    expect(config('location.maxmind.local.path'))->toBeFile();

    Http::assertNothingSent();
});

it('keeps the freshness check between driver instances', function () {
    $stream = fopen(__DIR__.'/../fixtures/GeoLite2-City-Test.mmdb', 'r');

    $storage = m::mock(Filesystem::class);
    $storage->shouldReceive('lastModified')->once()->with('databases/city.mmdb')->andReturn(1000);
    $storage->shouldReceive('readStream')->once()->with('databases/city.mmdb')
        ->andReturn($stream);

    Storage::shouldReceive('disk')->once()->with('maxmind')->andReturn($storage);

    expect(Location::get('2.125.160.216')->cityName)->toBe('Boxford');

    Location::setDriver(new MaxMind);

    expect(Location::get('2.125.160.216')->cityName)->toBe('Boxford');

    expect(is_resource($stream))->toBeFalse();

    Http::assertNothingSent();
});

it('checks after the ttl without downloading an unchanged database', function () {
    $storage = m::mock(Filesystem::class);
    $storage->shouldReceive('lastModified')->twice()->with('databases/city.mmdb')->andReturn(1000);
    $storage->shouldReceive('readStream')->once()->with('databases/city.mmdb')
        ->andReturn(fopen(__DIR__.'/../fixtures/GeoLite2-City-Test.mmdb', 'r'));

    Storage::shouldReceive('disk')->twice()->with('maxmind')->andReturn($storage);

    expect(Location::get('2.125.160.216')->cityName)->toBe('Boxford');

    $this->travel(3600)->seconds();

    Location::setDriver(new MaxMind);

    expect(Location::get('2.125.160.216')->cityName)->toBe('Boxford');
    expect(Location::get('2.125.160.216')->cityName)->toBe('Boxford');
});

it('downloads a changed database after the configured ttl', function () {
    config(['location.maxmind.storage.ttl' => 60]);

    $storage = m::mock(Filesystem::class);
    $storage->shouldReceive('lastModified')->twice()->with('databases/city.mmdb')->andReturn(1000, 2000);
    $storage->shouldReceive('readStream')->twice()->with('databases/city.mmdb')->andReturn(
        fopen(__DIR__.'/../fixtures/GeoLite2-City-Test.mmdb', 'r'),
        fopen(__DIR__.'/../fixtures/GeoLite2-Country-Test.mmdb', 'r')
    );

    Storage::shouldReceive('disk')->twice()->with('maxmind')->andReturn($storage);

    expect(Location::get('2.125.160.216')->countryCode)->toBe('GB');

    $this->travel(59)->seconds();

    expect(Location::get('2.125.160.216')->countryCode)->toBe('GB');
    expect(file_get_contents(config('location.maxmind.local.path')))
        ->toBe(file_get_contents(__DIR__.'/../fixtures/GeoLite2-City-Test.mmdb'));

    $this->travel(1)->seconds();

    config(['location.maxmind.local.type' => 'country']);

    expect(Location::get('2.125.160.216')->countryCode)->toBe('GB');
    expect(file_get_contents(config('location.maxmind.local.path')))
        ->toBe(file_get_contents(__DIR__.'/../fixtures/GeoLite2-Country-Test.mmdb'));
});

it('downloads a missing local database even when its last check is fresh', function () {
    Storage::disk('maxmind')->put('databases/city.mmdb', file_get_contents(__DIR__.'/../fixtures/GeoLite2-City-Test.mmdb'));

    expect(Location::get('2.125.160.216')->cityName)->toBe('Boxford');

    unlink(config('location.maxmind.local.path'));

    Location::setDriver(new MaxMind);

    expect(Location::get('2.125.160.216')->cityName)->toBe('Boxford');
    expect(config('location.maxmind.local.path'))->toBeFile();

    Http::assertNothingSent();
});

it('keeps a usable local database when the shared disk is unavailable', function () {
    $local = Storage::disk('maxmind-local');
    $local->put('maxmind/GeoLite2-City.mmdb', file_get_contents(__DIR__.'/../fixtures/GeoLite2-City-Test.mmdb'));

    $storage = m::mock(Filesystem::class);
    $storage->shouldReceive('lastModified')->once()->andThrow(new RuntimeException('Unavailable'));

    Storage::shouldReceive('disk')->with('maxmind')->andReturn($storage);

    expect(Location::get('2.125.160.216')->cityName)->toBe('Boxford');

    Http::assertNothingSent();
});

it('keeps a usable local database when the shared timestamp cannot be read', function () {
    Storage::disk('maxmind-local')->put('maxmind/GeoLite2-City.mmdb', file_get_contents(__DIR__.'/../fixtures/GeoLite2-City-Test.mmdb'));

    $storage = m::mock(Filesystem::class);
    $storage->shouldReceive('lastModified')->once()->andReturn(false);
    $storage->shouldNotReceive('readStream');

    Storage::shouldReceive('disk')->with('maxmind')->andReturn($storage);

    expect(Location::get('2.125.160.216')->cityName)->toBe('Boxford');
});

it('keeps a usable local database when the shared stream is unavailable', function () {
    Storage::disk('maxmind-local')->put('maxmind/GeoLite2-City.mmdb', file_get_contents(__DIR__.'/../fixtures/GeoLite2-City-Test.mmdb'));

    $storage = m::mock(Filesystem::class);
    $storage->shouldReceive('lastModified')->once()->andReturn(1000);
    $storage->shouldReceive('readStream')->once()->andReturn(null);

    Storage::shouldReceive('disk')->with('maxmind')->andReturn($storage);

    expect(Location::get('2.125.160.216')->cityName)->toBe('Boxford');
    expect(glob(dirname(config('location.maxmind.local.path')).'/maxmind-*'))->toBe([]);

    Http::assertNothingSent();
});

it('preserves the local database after an invalid download and retries the refresh', function () {
    $storage = Storage::disk('maxmind');
    $storage->put('databases/city.mmdb', file_get_contents(__DIR__.'/../fixtures/GeoLite2-City-Test.mmdb'));

    expect(Location::get('2.125.160.216')->cityName)->toBe('Boxford');

    $reader = new Reader(config('location.maxmind.local.path'));

    $storage->put('databases/city.mmdb', 'incomplete database');
    touch($storage->path('databases/city.mmdb'), now()->timestamp + 1);

    $this->travel(3600)->seconds();

    expect(Location::get('2.125.160.216')->cityName)->toBe('Boxford');
    expect(file_get_contents(config('location.maxmind.local.path')))
        ->toBe(file_get_contents(__DIR__.'/../fixtures/GeoLite2-City-Test.mmdb'));
    expect(glob(dirname(config('location.maxmind.local.path')).'/maxmind-*'))->toBe([]);

    $storage->put('databases/city.mmdb', file_get_contents(__DIR__.'/../fixtures/GeoLite2-Country-Test.mmdb'));
    touch($storage->path('databases/city.mmdb'), now()->timestamp);
    config(['location.maxmind.local.type' => 'country']);

    expect(Location::get('2.125.160.216')->countryCode)->toBe('GB');
    expect(file_get_contents(config('location.maxmind.local.path')))
        ->toBe(file_get_contents(__DIR__.'/../fixtures/GeoLite2-Country-Test.mmdb'));
    expect($reader->city('2.125.160.216')->city->name)->toBe('Boxford');

    $reader->close();
});

it('returns false when neither a local nor shared database is available', function () {
    expect(Location::get('2.125.160.216'))->toBeFalse();

    Http::assertNothingSent();
});

it('does not access shared storage when it is disabled', function () {
    config([
        'location.maxmind.storage.disk' => null,
        'location.maxmind.local.path' => __DIR__.'/../fixtures/GeoLite2-City-Test.mmdb',
    ]);

    Storage::shouldReceive('disk')->never();

    expect(Location::get('2.125.160.216')->cityName)->toBe('Boxford');
});

it('does not access shared storage when using the web service', function () {
    config(['location.maxmind.web.enabled' => true, 'location.maxmind.web.user_id' => 123]);

    $client = m::mock(Client::class);
    $client->shouldReceive('city')->with('2.125.160.216')->once()->andReturn(new City([
        'country' => ['iso_code' => 'GB'],
    ]));

    $driver = m::mock(MaxMind::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $driver->shouldReceive('newClient')->once()->andReturn($client);

    Storage::shouldReceive('disk')->never();

    Location::setDriver($driver);

    expect(Location::get('2.125.160.216')->countryCode)->toBe('GB');
});
