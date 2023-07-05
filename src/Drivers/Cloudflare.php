<?php

namespace Stevebauman\Location\Drivers;

use Illuminate\Support\Fluent;
use Stevebauman\Location\Position;
use Stevebauman\Location\Request;

class Cloudflare extends Driver
{
    /**
     * {@inheritDoc}
     */
    protected function process(Request $request): Fluent|false
    {
        // This is available both from CloudFlare's dashboard and Managed Transforms.
        $countryCode = $request->getHeader('cf-ipcountry');

        // Unknown and Tor values
        if (! $countryCode || in_array($countryCode, ['XX', 'T1'])) {
            return false;
        }

        // These are only available if the relevant Managed Transform is configured.
        // https://developers.cloudflare.com/rules/transform/managed-transforms/reference/#http-request-headers
        return new Fluent([
            'countryCode' => $countryCode,
            'cityName' => $request->getHeader('cf-ipcity'),
            'longitude' => $request->getHeader('cf-iplongitude'),
            'latitude' =>  $request->getHeader('cf-iplatitude'),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function hydrate(Position $position, Fluent $location): Position
    {
        $position->countryCode = $location->countryCode;
        $position->isoCode = $location->countryCode;
        $position->cityName = $location->cityName;
        $position->longitude = $location->longitude;
        $position->latitude = $location->latitude;

        return $position;
    }
}
