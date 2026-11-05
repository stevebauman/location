<?php

namespace Stevebauman\Location\Tests;

use Closure;
use GeoIp2\Database\Reader;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use MaxMind\Db\Reader\InvalidDatabaseException;
use Mockery as m;
use RuntimeException;
use Stevebauman\Location\Drivers\LocalMaxMindDatabase;

it('resolves a fresh database using the current configuration', function () {
    $local = Storage::fake('local');

    config([
        'location.maxmind.local.path' => $local->path('first/database.mmdb'),
        'location.maxmind.storage.disk' => null,
    ]);

    $first = app(LocalMaxMindDatabase::class);

    config(['location.maxmind.local.path' => $local->path('second/database.mmdb')]);

    $second = app(LocalMaxMindDatabase::class);

    expect($first)->not->toBe($second)
        ->and($first->directory())->toBe($local->path('first'))
        ->and($second->directory())->toBe($local->path('second'));
});

it('installs and publishes a validated database', function () {
    $local = Storage::fake('local');
    $shared = Storage::fake('shared');
    $path = $local->path('nested/database.mmdb');

    $source = __DIR__.'/fixtures/GeoLite2-City-Test.mmdb';

    (new LocalMaxMindDatabase($path, $shared, 'database.mmdb', 3600))->install($source);

    expect(file_get_contents($path))->toBe(file_get_contents($source))
        ->and($shared->get('database.mmdb'))->toBe(file_get_contents($source))
        ->and((int) file_get_contents($path.'.version'))->toBe($shared->lastModified('database.mmdb'))
        ->and($path.'.checked')->toBeFile();
});

it('preserves a symlink when replacing the local database', function () {
    $local = Storage::fake('local');
    $local->makeDirectory('target');
    $path = $local->path('database.mmdb');
    $target = $local->path('target/database.mmdb');
    $source = __DIR__.'/fixtures/GeoLite2-Country-Test.mmdb';
    copy(__DIR__.'/fixtures/GeoLite2-City-Test.mmdb', $target);
    symlink($target, $path);

    (new LocalMaxMindDatabase($path, null, 'database.mmdb', 3600))->install($source);

    expect(is_link($path))->toBeTrue()
        ->and(file_get_contents($target))->toBe(file_get_contents($source))
        ->and(glob($local->path('target/maxmind-*')))->toBe([]);
})->skip(PHP_OS_FAMILY === 'Windows', 'Creating symbolic links requires additional privileges on Windows.');

it('rejects an invalid installation before replacing or publishing the database', function () {
    $local = Storage::fake('local');
    $path = $local->path('database.mmdb');

    $source = __DIR__.'/fixtures/GeoLite2-City-Test.mmdb';
    copy($source, $path);
    $local->put('invalid.mmdb', 'invalid database');

    $shared = m::mock(Filesystem::class);
    $shared->shouldNotReceive('put');

    expect(fn () => (new LocalMaxMindDatabase($path, $shared, 'database.mmdb', 3600))->install($local->path('invalid.mmdb')))
        ->toThrow(InvalidDatabaseException::class);

    expect(file_get_contents($path))->toBe(file_get_contents($source))
        ->and(glob($local->path('maxmind-*')))->toBe([]);
});

it('reports a publication failure and closes the upload stream', function () {
    $path = Storage::fake('local')->path('database.mmdb');

    $stream = null;
    $shared = m::mock(Filesystem::class);
    $shared->shouldReceive('put')->once()->andReturnUsing(function ($path, $contents) use (&$stream) {
        $stream = $contents;

        return false;
    });

    expect(fn () => (new LocalMaxMindDatabase($path, $shared, 'database.mmdb', 3600))->install(__DIR__.'/fixtures/GeoLite2-City-Test.mmdb'))
        ->toThrow(RuntimeException::class, 'Failed to publish MaxMind database.');

    expect($path)->toBeFile()
        ->and(is_resource($stream))->toBeFalse();
});

it('keeps freshness across instances and checks unchanged versions after the ttl', function () {
    $path = Storage::fake('local')->path('database.mmdb');

    $stream = fopen(__DIR__.'/fixtures/GeoLite2-City-Test.mmdb', 'rb');
    $shared = m::mock(Filesystem::class);
    $shared->shouldReceive('lastModified')->twice()->with('database.mmdb')->andReturn(1000);
    $shared->shouldReceive('readStream')->once()->with('database.mmdb')->andReturn($stream);

    expect((new LocalMaxMindDatabase($path, $shared, 'database.mmdb', 3600))->path())->toBe($path);
    expect((new LocalMaxMindDatabase($path, $shared, 'database.mmdb', 3600))->path())->toBe($path);

    Carbon::setTestNow(now()->addSeconds(3600));

    expect((new LocalMaxMindDatabase($path, $shared, 'database.mmdb', 3600))->path())->toBe($path)
        ->and(is_resource($stream))->toBeFalse();
});

it('throttles failed checks while keeping the installed version and local database', function () {
    $path = Storage::fake('local')->path('database.mmdb');

    $source = __DIR__.'/fixtures/GeoLite2-City-Test.mmdb';
    copy($source, $path);
    file_put_contents($path.'.version', '1000');

    $shared = m::mock(Filesystem::class);
    $shared->shouldReceive('lastModified')->twice()->andThrow(new RuntimeException('Unavailable'));
    $shared->shouldNotReceive('readStream');

    expect((new LocalMaxMindDatabase($path, $shared, 'database.mmdb', 3600))->path())->toBe($path);
    expect((new LocalMaxMindDatabase($path, $shared, 'database.mmdb', 3600))->path())->toBe($path);

    Carbon::setTestNow(now()->addSeconds(3599));

    expect((new LocalMaxMindDatabase($path, $shared, 'database.mmdb', 3600))->path())->toBe($path);

    Carbon::setTestNow(now()->addSecond());

    expect((new LocalMaxMindDatabase($path, $shared, 'database.mmdb', 3600))->path())->toBe($path)
        ->and(file_get_contents($path))->toBe(file_get_contents($source))
        ->and(file_get_contents($path.'.version'))->toBe('1000');
});

it('preserves a local database when the shared timestamp is unavailable', function () {
    $path = Storage::fake('local')->path('database.mmdb');

    $source = __DIR__.'/fixtures/GeoLite2-City-Test.mmdb';
    copy($source, $path);

    $shared = m::mock(Filesystem::class);
    $shared->shouldReceive('lastModified')->once()->andReturn(false);
    $shared->shouldNotReceive('readStream');

    expect((new LocalMaxMindDatabase($path, $shared, 'database.mmdb', 3600))->path())->toBe($path)
        ->and(file_get_contents($path))->toBe(file_get_contents($source))
        ->and($path.'.checked')->toBeFile();
});

it('throttles failed stream reads without replacing a local database', function () {
    $local = Storage::fake('local');
    $path = $local->path('database.mmdb');

    $source = __DIR__.'/fixtures/GeoLite2-City-Test.mmdb';
    copy($source, $path);

    $shared = m::mock(Filesystem::class);
    $shared->shouldReceive('lastModified')->once()->andReturn(1000);
    $shared->shouldReceive('readStream')->once()->andReturn(null);

    expect((new LocalMaxMindDatabase($path, $shared, 'database.mmdb', 3600))->path())->toBe($path);
    expect((new LocalMaxMindDatabase($path, $shared, 'database.mmdb', 3600))->path())->toBe($path)
        ->and(file_get_contents($path))->toBe(file_get_contents($source))
        ->and(glob($local->path('maxmind-*')))->toBe([]);
});

it('preserves a database and open readers after an invalid download then retries after the ttl', function () {
    $local = Storage::fake('local');
    $shared = Storage::fake('shared');
    $path = $local->path('database.mmdb');

    $source = __DIR__.'/fixtures/GeoLite2-City-Test.mmdb';
    copy($source, $path);
    file_put_contents($path.'.version', '1000');
    $shared->put('database.mmdb', 'invalid database');
    touch($shared->path('database.mmdb'), 2000);

    $reader = new Reader($path);

    expect((new LocalMaxMindDatabase($path, $shared, 'database.mmdb', 3600))->path())->toBe($path)
        ->and(file_get_contents($path))->toBe(file_get_contents($source))
        ->and(file_get_contents($path.'.version'))->toBe('1000')
        ->and(glob($local->path('maxmind-*')))->toBe([]);

    $replacement = __DIR__.'/fixtures/GeoLite2-Country-Test.mmdb';
    $shared->put('database.mmdb', file_get_contents($replacement));
    touch($shared->path('database.mmdb'), 3000);

    (new LocalMaxMindDatabase($path, $shared, 'database.mmdb', 3600))->path();

    expect(file_get_contents($path))->toBe(file_get_contents($source));

    Carbon::setTestNow(now()->addSeconds(3600));

    (new LocalMaxMindDatabase($path, $shared, 'database.mmdb', 3600))->path();

    expect(file_get_contents($path))->toBe(file_get_contents($replacement))
        ->and(file_get_contents($path.'.version'))->toBe('3000')
        ->and($reader->city('2.125.160.216')->city->name)->toBe('Boxford');

    $reader->close();
});

it('downloads a missing local database even when the last check is fresh', function () {
    $path = Storage::fake('local')->path('database.mmdb');

    touch($path.'.checked', now()->timestamp);
    file_put_contents($path.'.version', '1000');

    $shared = m::mock(Filesystem::class);
    $shared->shouldReceive('lastModified')->once()->andReturn(1000);
    $shared->shouldReceive('readStream')->once()->andReturn(fopen(__DIR__.'/fixtures/GeoLite2-City-Test.mmdb', 'rb'));

    expect((new LocalMaxMindDatabase($path, $shared, 'database.mmdb', 3600))->path())->toBe($path)
        ->and($path)->toBeFile();
});

it('lets other workers use the local database during a refresh', function () {
    $path = Storage::fake('local')->path('database.mmdb');

    $source = __DIR__.'/fixtures/GeoLite2-City-Test.mmdb';
    $replacement = __DIR__.'/fixtures/GeoLite2-Country-Test.mmdb';
    copy($source, $path);
    file_put_contents($path.'.version', '1000');

    $shared = m::mock(Filesystem::class);
    $shared->shouldReceive('lastModified')->once()->andReturn(2000);
    $shared->shouldReceive('readStream')->once()->andReturnUsing(function () use ($path, $shared, $source, $replacement) {
        expect((new LocalMaxMindDatabase($path, $shared, 'database.mmdb', 3600))->path())->toBe($path)
            ->and(file_get_contents($path))->toBe(file_get_contents($source));

        return fopen($replacement, 'rb');
    });

    (new LocalMaxMindDatabase($path, $shared, 'database.mmdb', 3600))->path();

    expect(file_get_contents($path))->toBe(file_get_contents($replacement))
        ->and(file_get_contents($path.'.version'))->toBe('2000');
});

it('rechecks freshness after acquiring the lock', function () {
    $path = Storage::fake('local')->path('database.mmdb');

    $shared = m::mock(Filesystem::class);
    $shared->shouldNotReceive('lastModified');
    $shared->shouldNotReceive('readStream');

    $database = new class($path, $shared, 'database.mmdb', 3600) extends LocalMaxMindDatabase
    {
        protected function withLock(Closure $callback, bool $wait = true): void
        {
            // Simulate another worker finishing before this worker acquires the lock.
            copy(__DIR__.'/fixtures/GeoLite2-City-Test.mmdb', $this->path);
            file_put_contents($this->path.'.version', '2000');
            touch($this->path.'.checked', now()->timestamp);

            parent::withLock($callback, $wait);
        }
    };

    expect($database->path())->toBe($path)
        ->and(file_get_contents($path.'.version'))->toBe('2000');
});

it('releases the refresh lock after a failed download', function () {
    $path = Storage::fake('local')->path('database.mmdb');

    $shared = m::mock(Filesystem::class);
    $shared->shouldReceive('lastModified')->twice()->andReturn(1000);
    $shared->shouldReceive('readStream')->once()->andThrow(new RuntimeException('Unavailable'));
    $shared->shouldReceive('readStream')->once()->andReturn(fopen(__DIR__.'/fixtures/GeoLite2-City-Test.mmdb', 'rb'));

    expect((new LocalMaxMindDatabase($path, $shared, 'database.mmdb', 3600))->path())->toBe($path)
        ->and(is_file($path))->toBeFalse();

    expect((new LocalMaxMindDatabase($path, $shared, 'database.mmdb', 3600))->path())->toBe($path)
        ->and($path)->toBeFile();
});
