<?php

namespace Stevebauman\Location\Drivers;

use Exception;
use GeoIp2\Database\Reader;
use GeoIp2\Model\City;
use GeoIp2\Model\Country;
use GeoIp2\WebService\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
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
        $database = $this->getLocalDatabase();

        File::ensureDirectoryExists(
            $root = $database->directory()
        );

        $storage = Storage::build([
            'driver' => 'local',
            'root' => $root,
        ]);

        $tarFilePath = $storage->path(
            $tarFileName = 'maxmind.tar.gz'
        );

        $response = Http::withOptions([
            'sink' => $tarFilePath,
        ])->get(
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

        try {
            $database->install($storage->path($relativePath));
        } finally {
            $storage->delete($tarFileName);
            $storage->deleteDirectory($directory);
        }
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
        $position->zipCode = $location->postal;
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
                    'latitude' => $this->toNullableString($location->location->latitude),
                    'longitude' => $this->toNullableString($location->location->longitude),
                    'metro_code' => $this->toNullableString($location->location->metroCode),
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
            : $this->newReader($this->getLocalDatabase()->path(), $this->getLocales());

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
    protected function newReader(string $path, array $locales = ['en']): Reader
    {
        return new Reader($path, $locales);
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
     * Get the configured MaxMind locales array.
     *
     * English is appended when missing, since it is the only
     * locale MaxMind guarantees a name for.
     */
    protected function getLocales(): array
    {
        $locales = array_values((array) config('location.maxmind.web.locales', ['en']));

        if (! in_array('en', $locales, true)) {
            $locales[] = 'en';
        }

        return $locales;
    }

    /**
     * Get the local MaxMind database.
     */
    protected function getLocalDatabase(): LocalMaxMindDatabase
    {
        return app(LocalMaxMindDatabase::class);
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
