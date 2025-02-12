<?php

namespace Stevebauman\Location\Drivers;

use Illuminate\Support\Fluent;
use Stevebauman\Location\Position;

class Ip2locationio extends HttpDriver
{
    /**
     * {@inheritdoc}
     */
    public function url(string $ip): string
    {
        $token = config('location.ip2locationio.token');

        return "https://api.ip2location.io/?key={$token}&ip={$ip}";
    }

    /**
     * {@inheritdoc}
     */
    protected function hydrate(Position $position, Fluent $location): Position
    {
        $position->countryName = $location->country_name;
        $position->countryCode = $location->country_code;
        $position->regionCode = $location->region['code'] ?? null;
        $position->regionName = $location->region_name;
        $position->cityName = $location->city_name;
        $position->zipCode = $location->zip_code;
        $position->latitude = (string) $location->latitude;
        $position->longitude = (string) $location->longitude;
        $position->timezone = $location->time_zone;
        $position->currencyCode = $location->country['currency']['code'] ?? null;
        $position->metroCode = $location->geotargeting['metro'] ?? null;
        $position->areaCode = $location->area_code;

        return $position;
    }
}
