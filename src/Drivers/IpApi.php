<?php

namespace Stevebauman\Location\Drivers;

use Exception;
use Illuminate\Support\Fluent;
use Stevebauman\Location\Position;

class IpApi extends Driver
{
    /**
     * {@inheritdoc}
     */
    protected function url($ip)
    {
        return "http://ip-api.com/json/$ip";
    }

    /**
     * {@inheritdoc}
     */
    protected function hydrate(Position $position, Fluent $location)
    {
        $position->countryName = $location->country;
        $position->countryCode = $location->countryCode;
        $position->regionCode = $location->region;
        $position->regionName = $location->regionName;
        $position->cityName = $location->city;
        $position->zipCode = $location->zip;
        $position->latitude = (string) $location->lat;
        $position->longitude = (string) $location->lon;
        $position->areaCode = $location->region;
        $position->timezone = $location->timezone;

        return $position;
    }

    /**
     * {@inheritdoc}
     */
    protected function process($ip)
    {
        try {
            $response = json_decode($this->getUrlContent($this->url($ip)), true);

            return new Fluent($response);
        } catch (Exception $e) {
            return false;
        }
    }
}
