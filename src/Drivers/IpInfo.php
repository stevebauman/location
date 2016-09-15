<?php

namespace Stevebauman\Location\Drivers;

use Stevebauman\Location\Objects\Location;

class IpInfo extends Driver
{
    /**
     * {@inheritdoc}
     */
    protected function process($ip)
    {
        $url = config('location.drivers.IpInfo.url');

        $location = new Location();

        try {
            $contents = json_decode(file_get_contents($url.$ip));

            $location->ip = $ip;

            if (property_exists($contents, 'country')) {
                $location->countryCode = $contents->country;
            }

            if (property_exists($contents, 'postal')) {
                $location->postalCode = $contents->postal;
            }

            if (property_exists($contents, 'region')) {
                $location->regionName = $contents->region;
            }

            if (property_exists($contents, 'city')) {
                $location->cityName = $contents->city;
            }

            if (property_exists($contents, 'loc')) {
                $coords = explode(',', $contents->loc);

                if (array_key_exists(0, $coords)) {
                    $location->latitude = $coords[0];
                }

                if (array_key_exists(1, $coords)) {
                    $location->longitude = $coords[1];
                }
            }

            $countries = config('location.country_codes', []);

            // We'll see if we can convert the country
            // code to the country name.
            if (array_key_exists($location->countryCode, $countries)) {
                $location->countryName = $countries[$location->countryCode];
            }

            $location->driver = get_class($this);

            return $location;
        } catch (\Exception $e) {
            return false;
        }
    }
}
