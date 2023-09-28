<?php

namespace Stevebauman\Location\Drivers;

use Illuminate\Support\Fluent;
use Stevebauman\Location\Position;

class Kloudend extends HttpDriver
{
    /**
     * {@inheritdoc}
     */
    public function url(string $ip): string
    {
        $token = config('location.kloudend.token');

        return "https://ipapi.co/$ip/json".(empty($token) ? '' : "?key={$token}");
    }

    /**
     * {@inheritdoc}
     */
    protected function hydrate(Position $position, Fluent $location): Position
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
        $position->currencyCode = $location->currency;

        return $position;
    }
}
