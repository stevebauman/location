<?php

namespace Stevebauman\Location\Drivers;

use Exception;
use Illuminate\Support\Fluent;
use Stevebauman\Location\Position;

class IpInfo extends Driver
{
    /**
     * {@inheritdoc}
     */
    protected function url($ip)
    {
        $url = "http://ipinfo.io/$ip";

        if ($token = config('location.ipinfo.token')) {
            $url .= '?token='.$token;
        }

        return $url;
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
        $position->timezone = $location->timezone;
        $position->org = $location->org;

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
            $response = json_decode($this->getUrlContent($this->url($ip)));

            return new Fluent($response);
        } catch (Exception $e) {
            return false;
        }
    }
}
