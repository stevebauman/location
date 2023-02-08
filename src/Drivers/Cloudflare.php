<?php

namespace Stevebauman\Location\Drivers;

use Illuminate\Support\Fluent;
use Stevebauman\Location\Position;

class Cloudflare extends Driver
{
    /**
     * {@inheritDoc}
     */
    protected function process($ip)
    {
        // This is available both from CloudFlare's dashboard and Managed Transforms.
        $countryCode = request()->header('cf-ipcountry');

        // Unknown and Tor values
        if (! $countryCode || in_array($countryCode, ['XX', 'T1'])) {
            return false;
        }

        // These are only available if the relevant Managed Transform is configured.
        // https://developers.cloudflare.com/rules/transform/managed-transforms/reference/#http-request-headers
        $cityName = request()->header('cf-ipcity');
        $longitude = request()->header('cf-iplongitude');
        $latitude = request()->header('cf-iplatitude');

        return new Fluent(compact('countryCode', 'cityName', 'longitude', 'latitude'));
    }

    /**
     * {@inheritDoc}
     */
    protected function hydrate(Position $position, Fluent $location)
    {
        $position->countryCode = $location->countryCode;
        $position->isoCode = $location->countryCode;
        $position->cityName = $location->cityName;
        $position->longitude = $location->longitude;
        $position->latitude = $location->latitude;

        return $position;
    }
}
