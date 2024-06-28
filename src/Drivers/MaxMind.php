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

        Http::withOptions(['sink' => $tarFilePath])->throw()->get(
            $this->getDatabaseUrl()
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

        $storage->delete($tarFileName);
        $storage->deleteDirectory($directory);
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
        return config('location.maxmind.license_key', config('location.maxmind.web.license_key'));
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
            sprintf('https://download.maxmind.com/app/geoip_download_by_token?edition_id=GeoLite2-City&license_key=%s&suffix=tar.gz', $this->getLicenseKey()),
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
