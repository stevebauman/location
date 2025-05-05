<?php

namespace Stevebauman\Location\Drivers;

use Illuminate\Support\Fluent;
use Stevebauman\Location\Position;

class IpInfoLite extends HttpDriver
{
    /**
     * {@inheritdoc}
     */
    public function url(string $ip): string
    {
        $url = "https://api.ipinfo.io/lite/$ip";

        if ($token = config('location.ipinfo.token')) {
            $url .= '?token='.$token;
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    protected function hydrate(Position $position, Fluent $location): Position
    {
        $position->countryCode = $location->country_code;
        $position->countryName = $location->country;

        return $position;
    }
}
