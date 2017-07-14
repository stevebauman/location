<?php

namespace Stevebauman\Location\Drivers;

use Illuminate\Support\Fluent;
use Stevebauman\Location\Position;

class GeoPlugin extends Driver
{
    /**
     * {@inheritdoc}
     */
    protected function url()
    {
        return 'http://www.geoplugin.net/php.gp?ip=';
    }

    /**
     * {@inheritdoc}
     */
    protected function hydrate(Position $position, Fluent $location)
    {
        $position->countryCode = $location->geoplugin_countryCode;
        $position->countryName = $location->geoplugin_countryName;
        $position->regionName = $location->geoplugin_regionName;
        $position->regionCode = $location->geoplugin_regionCode;
        $position->cityName = $location->geoplugin_city;
        $position->latitude = $location->geoplugin_latitude;
        $position->longitude = $location->geoplugin_longitude;
        $position->areaCode = $location->geoplugin_areaCode;

        return $position;
    }

    /**
     * {@inheritdoc}
     */
    protected function process($ip)
    {
        try {
            $response = unserialize($this->getUrlContent($this->url().$ip));

            return new Fluent($response);
        } catch (\Exception $e) {
            return false;
        }
    }
}
