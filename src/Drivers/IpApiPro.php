<?php

namespace Stevebauman\Location\Drivers;

class IpApiPro extends IpApi
{
    /**
     * {@inheritDoc}
     */
    protected function url($ip)
    {
        $key = config('location.ip_api.token');

        return "https://pro.ip-api.com/json/$ip?key=$key";
    }
}
