<?php

namespace Stevebauman\Location\Drivers;

use Illuminate\Support\Fluent;
use Stevebauman\Location\Position;

class IpGeolocation extends HttpDriver
{
    /**
     * {@inheritdoc}
     */
    public function url(string $ip): string
    {
        return 'https://api.ipgeolocation.io/v3/ipgeo?'.http_build_query([
            'apiKey' => config('location.ipgeolocation.token'),
            'ip' => $ip,
            'fields' => 'location,currency.code,time_zone.name',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function hydrate(Position $position, Fluent $location): Position
    {
        $position->countryName = $location['location']['country_name'] ?? null;
        $position->countryCode = $location['location']['country_code2'] ?? null;
        $position->regionCode = $location['location']['state_code'] ?? null;
        $position->regionName = $location['location']['state_prov'] ?? null;
        $position->cityName = $location['location']['city'] ?? null;
        $position->zipCode = $location['location']['zipcode'] ?? null;
        $position->postalCode = $location['location']['zipcode'] ?? null;
        $position->latitude = $location['location']['latitude'] ?? null;
        $position->longitude = $location['location']['longitude'] ?? null;
        $position->timezone = $location['time_zone']['name'] ?? null;
        $position->currencyCode = $location['currency']['code'] ?? null;

        return $position;
    }
}
