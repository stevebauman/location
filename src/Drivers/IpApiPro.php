<?php

namespace Stevebauman\Location\Drivers;

class IpApiPro extends IpApi
{
    /**
     * {@inheritDoc}
     */
    public function url(string $ip): string
    {
        $key = config('location.ip_api.token');

        return "https://pro.ip-api.com/json/$ip?key=$key";
    }
}
