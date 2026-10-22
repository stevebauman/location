<?php

namespace Stevebauman\Location\Drivers;

use Exception;
use GeoIp2\Database\Reader;
use GeoIp2\Model\City;
use GeoIp2\Model\Country;
use GeoIp2\WebService\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use PharData;
use PharFileInfo;
use RecursiveIteratorIterator;
use RuntimeException;
use Stevebauman\Location\Position;
use Stevebauman\Location\Request;

class MaxMind extends Driver implements Updatable
{
    /**
     * Update the MaxMind database.
     */
    public function update(Command $command): void
    {
        @mkdir(
            $root = Str::of($this->getDatabasePath())->dirname()
        );

        $storage = Storage::build([
            'driver' => 'local',
            'root' => $root,
        ]);

        $tarFilePath = $storage->path(
            $tarFileName = 'maxmind.tar.gz'
        );

        $response = Http::withOptions(['sink' => $tarFilePath])->get(
            $this->getDatabaseUrl()
        );

        throw_if(
            $response->failed(),
            new RuntimeException('Failed to download MaxMind database. Response: '.$response->body())
        );

        $archive = new PharData($tarFilePath);

        $file = $this->discoverDatabaseFile($archive);

        $directory = Str::of($file->getPath())->basename();

        $relativePath = implode('/', [$directory, $file->getFilename()]);

        $archive->extractTo($storage->path('/'), $relativePath, true);

        file_put_contents(
            $this->getDatabasePath(),
            fopen($storage->path($relativePath), 'r')
        );

        try {
            $this->publishDatabase($storage->path($relativePath));
        } finally {
            $storage->delete($tarFileName);
            $storage->deleteDirectory($directory);
        }
    }

    /**
     * Publish the database to the configured filesystem disk.
     */
    protected function publishDatabase(string $path): void
    {
        if (! ($disk = $this->getStorageDisk())) {
            return;
        }

        $stream = fopen($path, 'r');

        throw_if(! is_resource($stream), new RuntimeException('Failed to read local MaxMind database.'));

        try {
            throw_if(
                ! Storage::disk($disk)->put($this->getStoragePath(), $stream),
                new RuntimeException('Failed to publish MaxMind database.')
            );
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * Refresh the local database from the configured filesystem disk.
     */
    protected function refreshDatabase(): void
    {
        if (! ($disk = $this->getStorageDisk())) {
            return;
        }

        $path = $this->getDatabasePath();

        clearstatcache(true, $path);
        clearstatcache(true, $checked = $path.'.checked');

        if (is_file($path) && is_file($checked) && filemtime($checked) + $this->getStorageTtl() > now()->timestamp) {
            return;
        }

        $storage = Storage::disk($disk);

        $modified = $storage->lastModified($this->getStoragePath());

        throw_if($modified === false, new RuntimeException('Failed to read shared MaxMind database timestamp.'));

        if (! is_file($path) || filemtime($path) !== $modified) {
            @mkdir(dirname($path), 0777, true);

            $temporary = tempnam(dirname($path), 'maxmind-');

            throw_if($temporary === false, new RuntimeException('Failed to create temporary MaxMind database.'));

            $stream = null;

            try {
                $stream = $storage->readStream($this->getStoragePath());

                throw_if(! is_resource($stream), new RuntimeException('Failed to read shared MaxMind database.'));

                throw_if(
                    file_put_contents($temporary, $stream) === false,
                    new RuntimeException('Failed to write local MaxMind database.')
                );

                // Validate the download before replacing a usable local database.
                (new Reader($temporary))->close();

                touch($temporary, $modified);
                chmod($temporary, 0666 & ~umask());

                throw_if(! rename($temporary, $path), new RuntimeException('Failed to replace local MaxMind database.'));
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }

                if (is_file($temporary)) {
                    unlink($temporary);
                }
            }
        }

        touch($checked, now()->timestamp);
    }

    /**
     * Attempt to discover the database file inside the archive.
     *
     * @throws Exception
     */
    protected function discoverDatabaseFile(PharData $archive): PharFileInfo
    {
        foreach (new RecursiveIteratorIterator($archive) as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'mmdb') {
                return $file;
            }
        }

        throw new Exception('Unable to locate database file inside of MaxMind archive.');
    }

    /**
     * {@inheritdoc}
     */
    protected function hydrate(Position $position, Fluent $location): Position
    {
        $position->countryName = $location->country;
        $position->countryCode = $location->country_code;
        $position->isoCode = $location->country_code;
        $position->regionCode = $location->regionCode;
        $position->regionName = $location->regionName;
        $position->cityName = $location->city;
        $position->postalCode = $location->postal;
        $position->metroCode = $location->metro_code;
        $position->timezone = $location->timezone;
        $position->latitude = $location->latitude;
        $position->longitude = $location->longitude;

        return $position;
    }

    /**
     * {@inheritdoc}
     */
    protected function process(Request $request): Fluent|false
    {
        return rescue(function () use ($request) {
            $location = $this->fetchLocation($request->getIp());

            if ($location instanceof City) {
                return new Fluent([
                    'country' => $location->country->name,
                    'country_code' => $location->country->isoCode,
                    'city' => $location->city->name,
                    'regionCode' => $location->mostSpecificSubdivision->isoCode,
                    'regionName' => $location->mostSpecificSubdivision->name,
                    'postal' => $location->postal->code,
                    'timezone' => $location->location->timeZone,
                    'latitude' => (string) $location->location->latitude,
                    'longitude' => (string) $location->location->longitude,
                    'metro_code' => (string) $location->location->metroCode,
                ]);
            }

            return new Fluent([
                'country' => $location->country->name,
                'country_code' => $location->country->isoCode,
            ]);
        }, false, false);
    }

    /**
     * Attempt to fetch the location model from Maxmind.
     *
     * @throws Exception
     */
    protected function fetchLocation(string $ip): City|Country
    {
        $maxmind = $this->isWebServiceEnabled()
            ? $this->newClient($this->getUserId(), $this->getLicenseKey(), $this->getLocales(), $this->getOptions())
            : $this->newReader($this->getDatabasePath());

        if ($this->isWebServiceEnabled() || $this->getLocationType() === 'city') {
            return $maxmind->city($ip);
        }

        return $maxmind->country($ip);
    }

    /**
     * Get a new MaxMind web service client.
     */
    protected function newClient(string|int $userId, string $licenseKey, array $locales, array $options = []): Client
    {
        return new Client((int) $userId, $licenseKey, $locales, $options);
    }

    /**
     * Get a new MaxMind reader client.
     */
    protected function newReader(string $path): Reader
    {
        rescue(fn () => $this->refreshDatabase(), null, false);

        return new Reader($path);
    }

    /**
     * Determine if the MaxMind web service is enabled.
     */
    protected function isWebServiceEnabled(): bool
    {
        return (bool) config('location.maxmind.web.enabled', false);
    }

    /**
     * Get the configured MaxMind web user ID.
     */
    protected function getUserId(): string|int
    {
        return config('location.maxmind.web.user_id');
    }

    /**
     * Get the configured MaxMind web license key.
     */
    protected function getLicenseKey(): string
    {
        return config('location.maxmind.license_key', config('location.maxmind.web.license_key'));
    }

    /**
     * Get the configured MaxMind web option array.
     */
    protected function getOptions(): array
    {
        return config('location.maxmind.web.options', []);
    }

    /**
     * Get the configured MaxMind web locales array.
     */
    protected function getLocales(): array
    {
        return config('location.maxmind.web.locales', ['en']);
    }

    /**
     * Get the MaxMind database file path.
     */
    protected function getDatabasePath(): string
    {
        return config('location.maxmind.local.path', database_path('maxmind/GeoLite2-City.mmdb'));
    }

    /**
     * Get the shared MaxMind filesystem disk.
     */
    protected function getStorageDisk(): ?string
    {
        return config('location.maxmind.storage.disk');
    }

    /**
     * Get the shared MaxMind database path.
     */
    protected function getStoragePath(): string
    {
        return config('location.maxmind.storage.path', 'maxmind/GeoLite2-City.mmdb');
    }

    /**
     * Get the number of seconds between shared database checks.
     */
    protected function getStorageTtl(): int
    {
        return (int) config('location.maxmind.storage.ttl', 3600);
    }

    /**
     * Get the database URL to download.
     */
    protected function getDatabaseUrl(): string
    {
        return config(
            'location.maxmind.local.url',
            sprintf('https://download.maxmind.com/app/geoip_download_by_token?edition_id=GeoLite2-City&license_key=%s&suffix=tar.gz', $this->getLicenseKey()),
        );
    }

    /**
     * Get the MaxMind location type.
     */
    protected function getLocationType(): string
    {
        return config('location.maxmind.local.type', 'city');
    }
}
