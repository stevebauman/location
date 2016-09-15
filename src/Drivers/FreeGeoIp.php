<?php

namespace Stevebauman\Location\Drivers;

use Stevebauman\Location\Objects\Location;

class FreeGeoIp extends Driver
{
    /**
     * {@inheritdoc}
     */
    protected function process($ip)
    {
        $url = config('location.drivers.FreeGeoIp.url');

        $location = new Location();

        try {
            $contents = json_decode(file_get_contents($url.$ip), true);

            $location->ip = $ip;

            if (array_key_exists('country_code', $contents)) {
                $location->countryCode = $contents['country_code'];
            }

            if (array_key_exists('country_name', $contents)) {
                $location->countryName = $contents['country_name'];
            }

            if (array_key_exists('region_code', $contents)) {
                $location->regionCode = $contents['region_code'];
            }

            if (array_key_exists('region_name', $contents)) {
                $location->regionName = $contents['region_name'];
            }

            if (array_key_exists('city', $contents)) {
                $location->cityName = $contents['city'];
            }

            if (array_key_exists('zip_code', $contents)) {
                $location->zipCode = $contents['zip_code'];
            }

            if (array_key_exists('latitude', $contents)) {
                $location->latitude = $contents['latitude'];
            }

            if (array_key_exists('longitude', $contents)) {
                $location->longitude = $contents['longitude'];
            }

            if (array_key_exists('metro_code', $contents)) {
                $location->metroCode = $contents['metro_code'];
            }

            if (array_key_exists('area_code', $contents)) {
                $location->areaCode = $contents['area_code'];
            }

            $location->driver = get_class($this);

            return $location;
        } catch (\Exception $e) {
            return false;
        }
    }
}
