<?php

namespace Stevebauman\Location\Drivers;

use GeoIp2\Database\Reader;
use GeoIp2\WebService\Client;
use Stevebauman\Location\Objects\Location;

class MaxMind extends Driver
{
    /**
     * {@inheritdoc}
     */
    protected function process($ip)
    {
        $location = new Location();

        $settings = config('location.drivers.MaxMind.configuration');

        try {
            if ($settings['web_service']) {
                $maxmind = new Client($settings['user_id'], $settings['license_key']);
            } else {
                $path = database_path('maxmind/GeoLite2-City.mmdb');

                $maxmind = new Reader($path);
            }

            $record = $maxmind->city($ip);

            $location->ip = $ip;

            $location->isoCode = $record->country->isoCode;

            $location->countryName = $record->country->name;

            $location->cityName = $record->city->name;

            $location->postalCode = $record->postal->code;

            $location->latitude = $record->location->latitude;

            $location->longitude = $record->location->longitude;

            $location->driver = get_class($this);

            return $location;
        } catch (\Exception $e) {
            return false;
        }
    }
}
