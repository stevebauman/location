<?php

namespace Stevebauman\Location\Drivers;

use Illuminate\Support\Facades\File;

class LocalMaxMindDatabase
{
    /**
     * Constructor.
     */
    public function __construct(
        protected string $path,
        protected ?SharedMaxMindDatabase $shared = null,
        protected int $ttl = 3600,
    ) {}

    /**
     * Get the local path to the MaxMind database.
     */
    public function path(): string
    {
        if ($this->shared && $this->expired()) {
            $this->refresh();
        }

        return $this->path;
    }

    /**
     * Install and publish a MaxMind database.
     */
    public function install(string $source): void
    {
        $this->shared?->put($source);

        $this->store(fopen($source, 'rb'));

        if ($this->shared) {
            $this->writeVersion($this->shared->lastModified());
        }
    }

    /**
     * Refresh the local database from shared storage.
     */
    protected function refresh(): void
    {
        $version = $this->shared->lastModified();

        if ($this->shouldDownload($version)) {
            $this->store($this->shared->readStream());
        }

        $this->writeVersion($version);
    }

    /**
     * Store the MaxMind database locally.
     *
     * @param  resource  $contents
     */
    protected function store(mixed $contents): void
    {
        File::ensureDirectoryExists(dirname($this->path));
        File::replace($this->path, $contents);
    }

    /**
     * Determine if the local database cache has expired.
     */
    protected function expired(): bool
    {
        return ! is_file($this->path)
            || ! is_file($this->versionPath())
            || filemtime($this->versionPath()) + $this->ttl <= time();
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
