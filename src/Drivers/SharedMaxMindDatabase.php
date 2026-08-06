<?php

namespace Stevebauman\Location\Drivers;

use Illuminate\Contracts\Filesystem\Filesystem;
use RuntimeException;

class SharedMaxMindDatabase
{
    /**
     * Constructor.
     */
    public function __construct(
        protected Filesystem $disk,
        protected string $path,
    ) {}

    /**
     * Store the MaxMind database on the shared disk.
     */
    public function put(string $source): void
    {
        throw_unless(
            $this->disk->put($this->path, fopen($source, 'rb')),
            new RuntimeException('Failed to store MaxMind database.')
        );
    }

    /**
     * Read the MaxMind database from the shared disk.
     *
     * @return resource
     */
    public function readStream(): mixed
    {
        $stream = $this->disk->readStream($this->path);

        throw_unless(is_resource($stream), new RuntimeException('Failed to read MaxMind database from storage.'));

        return $stream;
    }

    /**
     * Get the last modified time of the shared database.
     */
    public function lastModified(): int
    {
        return $this->disk->lastModified($this->path);
    }
}
