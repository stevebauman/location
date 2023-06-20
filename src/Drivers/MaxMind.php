<?php

namespace Stevebauman\Location\Drivers;

use Exception;
use GeoIp2\Database\Reader;
use GeoIp2\Model\City;
use GeoIp2\Model\Country;
use GeoIp2\WebService\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use PharData;
use PharFileInfo;
use Stevebauman\Location\Position;
use Stevebauman\Location\Request;

class MaxMind extends Driver implements Updatable
{
    /**
     * Update the MaxMind database.
     */
    public function update(Command $command): void
    {
        $storage = Storage::build([
            'driver' => 'local',
            'root' => sys_get_temp_dir(),
        ]);

        $storage->put(
            $tar = 'maxmind.tar.gz',
            fopen($this->getDatabaseUrl(), 'r')
        );

        $file = $this->discoverDatabaseFile(
            $archive = new PharData($storage->path($tar))
        );

        $relativePath = implode('/', [
            Str::afterLast($file->getPath(), DIRECTORY_SEPARATOR),
            $file->getFilename(),
        ]);

        $archive->extractTo($storage->path('/'), $relativePath, true);

        @mkdir(
            Str::beforeLast($this->getDatabasePath(), DIRECTORY_SEPARATOR)
        );

        file_put_contents(
            $this->getDatabasePath(),
            fopen($storage->path($relativePath), 'r')
        );
    }

    /**
     * Attempt to discover the database file inside the archive.
     *
     * @throws Exception
     */
    protected function discoverDatabaseFile(PharData $archive): PharFileInfo
    {
        /** @var \FilesystemIterator $file */
        foreach ($archive as $file) {
            if ($file->isDir()) {
                return $this->discoverDatabaseFile(
                    new PharData($file->getPathName())
                );
            }

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
        $position->timezone = $location->time_zone;
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
            $record = $this->fetchLocation($request->getIp());

            if ($record instanceof City) {
                return new Fluent([
                    'country' => $record->country->name,
                    'country_code' => $record->country->isoCode,
                    'city' => $record->city->name,
                    'regionCode' => $record->mostSpecificSubdivision->isoCode,
                    'regionName' => $record->mostSpecificSubdivision->name,
                    'postal' => $record->postal->code,
                    'timezone' => $record->location->timeZone,
                    'latitude' => (string) $record->location->latitude,
                    'longitude' => (string) $record->location->longitude,
                    'metro_code' => (string) $record->location->metroCode,
                ]);
            }

            return new Fluent([
                'country' => $record->country->name,
                'country_code' => $record->country->isoCode,
            ]);
        }, false);
    }

    /**
     * Attempt to fetch the location model from Maxmind.
     *
     * @throws \Exception
     */
    protected function fetchLocation(string $ip): City|Country
    {
        $maxmind = $this->isWebServiceEnabled()
            ? $this->newClient($this->getUserId(), $this->getLicenseKey(), $this->getOptions())
            : $this->newReader($this->getDatabasePath());

        if ($this->isWebServiceEnabled() || $this->getLocationType() === 'city') {
            return $maxmind->city($ip);
        }

        return $maxmind->country($ip);
    }

    /**
     * Get a new MaxMind web service client.
     */
    protected function newClient(string $userId, string $licenseKey, array $options = []): Client
    {
        return new Client($userId, $licenseKey, $options);
    }

    /**
     * Get a new MaxMind reader client.
     */
    protected function newReader(string $path): Reader
    {
        return new Reader($path);
    }

    /**
     * Returns true / false if the MaxMind web service is enabled.
     */
    protected function isWebServiceEnabled(): bool
    {
        return (bool) config('location.maxmind.web.enabled', false);
    }

    /**
     * Returns the configured MaxMind web user ID.
     */
    protected function getUserId(): string
    {
        return config('location.maxmind.web.user_id');
    }

    /**
     * Returns the configured MaxMind web license key.
     */
    protected function getLicenseKey(): string
    {
        return config('location.maxmind.web.license_key');
    }

    /**
     * Returns the configured MaxMind web option array.
     */
    protected function getOptions(): array
    {
        return config('location.maxmind.web.options', []);
    }

    /**
     * Returns the MaxMind database file path.
     */
    protected function getDatabasePath(): string
    {
        return config('location.maxmind.local.path', database_path('maxmind/GeoLite2-City.mmdb'));
    }

    /**
     * Get the database URL to download.
     */
    protected function getDatabaseUrl(): string
    {
        return config(
            'location.maxmind.local.url',
            sprintf('https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key=%s&suffix=tar.gz', $this->getLicenseKey()),
        );
    }

    /**
     * Returns the MaxMind location type.
     */
    protected function getLocationType(): string
    {
        return config('location.maxmind.local.type', 'city');
    }
}
