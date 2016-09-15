<?php

namespace Stevebauman\Location\Drivers;

use Stevebauman\Location\Objects\Location;

class GeoPlugin extends Driver
{
    /**
     * {@inheritdoc}
     */
    protected function process($ip)
    {
        $url = config('location.drivers.GeoPlugin.url');

        $location = new Location();

        try {
            $contents = unserialize(file_get_contents($url.$ip));

            $location->ip = $ip;

            if (array_key_exists('geoplugin_countryCode', $contents)) {
                $location->countryCode = $contents['geoplugin_countryCode'];
            }

            if (array_key_exists('geoplugin_countryName', $contents)) {
                $location->countryName = $contents['geoplugin_countryName'];
            }

            if (array_key_exists('geoplugin_regionName', $contents)) {
                $location->regionName = $contents['geoplugin_regionName'];
            }

            if (array_key_exists('geoplugin_city', $contents)) {
                $location->regionName = $contents['geoplugin_city'];
            }

            if (array_key_exists('geoplugin_longitude', $contents)) {
                $location->longitude = $contents['geoplugin_longitude'];
            }

            if (array_key_exists('geoplugin_latitude', $contents)) {
                $location->latitude = $contents['geoplugin_latitude'];
            }

            if (array_key_exists('geoplugin_areaCode', $contents)) {
                $location->areaCode = $contents['geoplugin_areaCode'];
            }

            if (array_key_exists('geoplugin_regionCode', $contents)) {
                $location->regionCode = $contents['geoplugin_regionCode'];
            }

            if (array_key_exists('geoplugin_regionName', $contents)) {
                $location->regionName = $contents['geoplugin_regionName'];
            }

            $location->driver = get_class($this);

            return $location;
        } catch (\Exception $e) {
            return false;
        }
    }
}
