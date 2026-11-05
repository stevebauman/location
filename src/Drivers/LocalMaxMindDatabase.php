<?php

namespace Stevebauman\Location\Drivers;

use Closure;
use GeoIp2\Database\Reader;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\LockableFile;
use Illuminate\Support\Facades\File;
use RuntimeException;

class LocalMaxMindDatabase
{
    /**
     * Constructor.
     */
    public function __construct(
        protected string $path,
        protected ?Filesystem $disk,
        protected string $storagePath,
        protected int $ttl,
    ) {}

    /**
     * Get the directory containing the local database.
     */
    public function directory(): string
    {
        return dirname($this->path);
    }

    /**
     * Get the local path to the MaxMind database.
     */
    public function path(): string
    {
        if ($this->disk && $this->expired()) {
            rescue(fn () => $this->refresh(), null, false);
        }

        return $this->path;
    }

    /**
     * Install and publish a MaxMind database.
     */
    public function install(string $source): void
    {
        $this->withLock(function () use ($source) {
            $this->store(fopen($source, 'rb'));

            if (! $this->disk) {
                return;
            }

            $stream = fopen($this->path, 'rb');

            try {
                throw_unless(
                    $this->disk->put($this->storagePath, $stream),
                    new RuntimeException('Failed to publish MaxMind database.')
                );
            } finally {
                fclose($stream);
            }

            $this->writeVersion($this->disk->lastModified($this->storagePath));
            touch($this->path.'.checked', now()->timestamp);
        });
    }

    /**
     * Refresh the local database from shared storage.
     */
    protected function refresh(): void
    {
        $this->withLock(function () {
            if (! $this->expired()) {
                return;
            }

            try {
                $version = $this->disk->lastModified($this->storagePath);

                throw_if($version === false, new RuntimeException('Failed to read shared MaxMind database timestamp.'));

                if ($this->shouldDownload($version)) {
                    $this->store($this->disk->readStream($this->storagePath));
                }

                $this->writeVersion($version);
            } finally {
                if (is_file($this->path)) {
                    touch($this->path.'.checked', now()->timestamp);
                }
            }
        }, wait: ! is_file($this->path));
    }

    /**
     * Coordinate database writes across local workers.
     */
    protected function withLock(Closure $callback, bool $wait = true): void
    {
        $lock = new LockableFile($this->path.'.lock', 'c');

        try {
            $lock->getExclusiveLock($wait);
            $callback();
        } finally {
            $lock->close();
        }
    }

    /**
     * Store the MaxMind database locally.
     *
     * @param  resource  $contents
     */
    protected function store(mixed $contents): void
    {
        throw_unless(is_resource($contents), new RuntimeException('Failed to read MaxMind database.'));

        $temporary = null;

        try {
            clearstatcache(true, $this->path);

            $path = realpath($this->path) ?: $this->path;
            $temporary = tempnam(dirname($path), 'maxmind-');

            throw_if($temporary === false, new RuntimeException('Failed to create temporary MaxMind database.'));
            throw_if(file_put_contents($temporary, $contents) === false, new RuntimeException('Failed to write MaxMind database.'));

            (new Reader($temporary))->close();

            chmod($temporary, 0666 & ~umask());

            throw_unless(rename($temporary, $path), new RuntimeException('Failed to replace MaxMind database.'));
        } finally {
            fclose($contents);

            if ($temporary && is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    /**
     * Determine if the local database cache has expired.
     */
    protected function expired(): bool
    {
        clearstatcache(true, $this->path);
        clearstatcache(true, $checked = $this->path.'.checked');

        return ! is_file($this->path)
            || ! is_file($checked)
            || filemtime($checked) + $this->ttl <= now()->timestamp;
    }

    /**
     * Determine if the shared database should be downloaded.
     */
    protected function shouldDownload(int $version): bool
    {
        return ! is_file($this->path)
            || ! is_file($this->versionPath())
            || (int) file_get_contents($this->versionPath()) !== $version;
    }

    /**
     * Write the shared database version to the local cache.
     */
    protected function writeVersion(int $version): void
    {
        File::put($this->versionPath(), (string) $version);
    }

    /**
     * Get the local database version path.
     */
    protected function versionPath(): string
    {
        return $this->path.'.version';
    }
}
