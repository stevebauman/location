<?php

namespace Stevebauman\Location\Drivers;

use Illuminate\Support\Fluent;
use Stevebauman\Location\Position;

class FreeGeoIp extends Driver
{
    /**
     * {@inheritdoc}
     */
    protected function url()
    {
        return 'http://freegeoip.net/json/';
    }

    /**
     * {@inheritdoc}
     */
    protected function hydrate(Position $position, Fluent $location)
    {
        $position->countryCode = $location->country_code;
        $position->regionName = $location->region_name;
        $position->cityName = $location->city;
        $position->zipCode = $location->zip_code;
        $position->latitude = (string) $location->latitude;
        $position->longitude = (string) $location->longitude;
        $position->metroCode = (string) $location->metro_code;
        $position->areaCode = $location->area_code;

        return $position;
    }

    /**
     * {@inheritdoc}
     */
    protected function process($ip)
    {
        try {
            $response = json_decode($this->getUrlContent($this->url().$ip), true);

            return new Fluent($response);
        } catch (\Exception $e) {
            return false;
        }
    }
}
