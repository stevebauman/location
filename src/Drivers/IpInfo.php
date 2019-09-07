<?php

namespace Stevebauman\Location\Drivers;

use Illuminate\Support\Fluent;
use Stevebauman\Location\Position;

class IpInfo extends Driver
{
    /**
     * {@inheritdoc}
     */
    protected function url()
    {
        return 'http://ipinfo.io/';
    }

    /**
     * {@inheritdoc}
     */
    protected function hydrate(Position $position, Fluent $location)
    {
        $position->countryCode = $location->country;
        $position->regionName = $location->region;
        $position->cityName = $location->city;
        $position->zipCode = $location->postal;

        if ($location->loc) {
            $coords = explode(',', $location->loc);

            if (array_key_exists(0, $coords)) {
                $position->latitude = $coords[0];
            }

            if (array_key_exists(1, $coords)) {
                $position->longitude = $coords[1];
            }
        }

        return $position;
    }

    /**
     * {@inheritdoc}
     */
    protected function process($ip)
    {
        try {
            $url = $this->url().$ip;

            if ($token = config('location.ipinfo.token')) {
                $url .= '?token='.$token;
            }

            return new Fluent(
                json_decode($this->getUrlContent($url))
            );
        } catch (\Exception $e) {
            return false;
        }
    }
}
