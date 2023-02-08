<?php

namespace Stevebauman\Location\Drivers;

use Exception;
use GeoIp2\Database\Reader;
use GeoIp2\Model\City;
use GeoIp2\WebService\Client;
use Illuminate\Support\Fluent;
use Stevebauman\Location\Position;

class MaxMind extends Driver
{
    /**
     * {@inheritdoc}
     */
    protected function hydrate(Position $position, Fluent $location)
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
    protected function process($ip)
    {
        try {
            $record = $this->fetchLocation($ip);

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
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Attempt to fetch the location model from Maxmind.
     *
     * @param string $ip
     *
     * @return \GeoIp2\Model\City
     *
     * @throws \Exception
     */
    protected function fetchLocation($ip)
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
     * Returns a new MaxMind web service client.
     *
     * @param string $userId
     * @param string $licenseKey
     * @param array  $options
     *
     * @return Client
     */
    protected function newClient($userId, $licenseKey, array $options = [])
    {
        return new Client($userId, $licenseKey, $options);
    }

    /**
     * Returns a new MaxMind reader client with
     * the specified database file path.
     *
     * @param string $path
     *
     * @return \GeoIp2\Database\Reader
     */
    protected function newReader($path)
    {
        return new Reader($path);
    }

    /**
     * Returns true / false if the MaxMind web service is enabled.
     *
     * @return mixed
     */
    protected function isWebServiceEnabled()
    {
        return config('location.maxmind.web.enabled', false);
    }

    /**
     * Returns the configured MaxMind web user ID.
     *
     * @return string
     */
    protected function getUserId()
    {
        return config('location.maxmind.web.user_id');
    }

    /**
     * Returns the configured MaxMind web license key.
     *
     * @return string
     */
    protected function getLicenseKey()
    {
        return config('location.maxmind.web.license_key');
    }

    /**
     * Returns the configured MaxMind web option array.
     *
     * @return array
     */
    protected function getOptions()
    {
        return config('location.maxmind.web.options', []);
    }

    /**
     * Returns the MaxMind database file path.
     *
     * @return string
     */
    protected function getDatabasePath()
    {
        return config('location.maxmind.local.path', database_path('maxmind/GeoLite2-City.mmdb'));
    }

    /**
     * Returns the MaxMind location type.
     *
     * @return string
     */
    protected function getLocationType()
    {
        return config('location.maxmind.local.type', 'city');
    }
}
