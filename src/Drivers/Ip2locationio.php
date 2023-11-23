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
        $position->postalCode = $location->zip_code;
        $position->latitude = (string) $location->latitude;
        $position->longitude = (string) $location->longitude;
        $position->timezone = $location->time_zone;
        $position->currencyCode = $location->country['currency']['code'] ?? null;
        $position->metroCode = $location->geotargeting['metro'] ?? null;
        $position->areaCode = $location->area_code ?? null;
        $position->isp = $location->isp ?? null;
        $position->asn = $location->asn ?? null;
        $position->asName = $location->as ?? null;
        $position->domain = $location->domain ?? null;
        $position->netSpeed = $location->net_speed ?? null;
        $position->iddCode = $location->idd_code ?? null;
        $position->weatherStationCode = $location->weather_station_code ?? null;
        $position->weatherStationName = $location->weather_station_name ?? null;
        $position->mcc = $location->mcc ?? null;
        $position->mnc = $location->mnc ?? null;
        $position->mobileBrand = $location->mobile_brand ?? null;
        $position->elevation = $location->elevation ?? null;
        $position->usageType = $location->usage_type ?? null;
        $position->addressType = $location->address_type ?? null;
        $position->isProxy = $location->is_proxy ?? null;

        return $position;
    }
}
