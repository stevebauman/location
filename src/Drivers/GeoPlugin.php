<?php

namespace Stevebauman\Location\Drivers;

use Illuminate\Support\Fluent;
use Stevebauman\Location\Position;

class GeoPlugin extends HttpDriver
{
    /**
     * {@inheritdoc}
     */
    public function url(string $ip): string
    {
        return "http://www.geoplugin.net/json.gp?ip=$ip";
    }

    /**
     * {@inheritdoc}
     */
    protected function hydrate(Position $position, Fluent $location): Position
    {
        $position->countryCode = $location->geoplugin_countryCode;
        $position->countryName = $location->geoplugin_countryName;
        $position->regionName = $location->geoplugin_regionName;
        $position->regionCode = $location->geoplugin_regionCode;
        $position->cityName = $location->geoplugin_city;
        $position->latitude = $location->geoplugin_latitude;
        $position->longitude = $location->geoplugin_longitude;
        $position->areaCode = $location->geoplugin_areaCode;
        $position->timezone = $location->geoplugin_timezone;

        return $position;
    }
}
