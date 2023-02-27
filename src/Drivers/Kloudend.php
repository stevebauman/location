<?php

namespace Stevebauman\Location\Drivers;

use Exception;
use Illuminate\Support\Fluent;
use Stevebauman\Location\Position;

class Kloudend extends Driver
{
    /**
     * {@inheritdoc}
     */
    protected function url($ip)
    {
        $token = config('location.kloudend.token', '');

        return "https://ipapi.co/{$ip}/json".(empty($token) ? '' : "?key={$token}");
    }

    /**
     * {@inheritdoc}
     */
    protected function hydrate(Position $position, Fluent $location)
    {
        $position->countryName = $location->country_name;
        $position->countryCode = $location->country_code;
        $position->regionCode = $location->region_code;
        $position->regionName = $location->region;
        $position->cityName = $location->city;
        $position->zipCode = $location->postal;
        $position->postalCode = $location->postal;
        $position->latitude = (string) $location->latitude;
        $position->longitude = (string) $location->longitude;
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
