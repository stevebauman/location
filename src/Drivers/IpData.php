<?php

namespace Stevebauman\Location\Drivers;

use Illuminate\Support\Fluent;
use Stevebauman\Location\Position;

class IpData extends HttpDriver
{
    /**
     * {@inheritdoc}
     */
    public function url(string $ip): string
    {
        $token = config('location.ipdata.token');

        return "https://api.ipdata.co/{$ip}?api-key={$token}";
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
        $position->timezone = $location->time_zone['name'] ?? null;
        $position->currencyCode = $location->currency['code'] ?? null;

        return $position;
    }
}
