<?php

namespace Stevebauman\Location\Drivers;

use GeoIp2\Database\Reader;
use GeoIp2\WebService\Client;
use Illuminate\Support\Fluent;
use Stevebauman\Location\Position;

class MaxMind extends Driver
{
    /**
     * {@inheritdoc}
     */
    public function url()
    {
        return;
    }

    /**
     * {@inheritdoc}
     */
    protected function hydrate(Position $position, Fluent $location)
    {
        $position->countryName = $location->country;
        $position->cityName = $location->city;
        $position->postalCode = $location->postal;
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
            $maxmind = $this->isWebServiceEnabled() ?
                $this->newClient($this->getUserId(), $this->getLicenseKey()) :
                $this->newReader($this->getDatabasePath());

            $record = $maxmind->city($ip);

            return new Fluent([
                'country' => $record->country->name,
                'city' => $record->city->name,
                'postal' => $record->postal->code,
                'latitude' => $record->location->latitude,
                'longitude' => $record->location->longitude,
            ]);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Returns a new MaxMind web service client.
     *
     * @param string $userId
     * @param string $licenseKey
     *
     * @return Client
     */
    protected function newClient($userId, $licenseKey)
    {
        return new Client($userId, $licenseKey);
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
     * Returns the configured MaxMinds web user ID.
     *
     * @return string
     */
    protected function getUserId()
    {
        return config('location.maxmind.web.user_id');
    }

    /**
     * Returns the configured MaxMinds web license key.
     *
     * @return string
     */
    protected function getLicenseKey()
    {
        return config('location.maxmind.web.license_key');
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
}
