<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Selected Driver
    |--------------------------------------------------------------------------
    |
    | The first driver you would like to use for location retrieval.
    |
    */

    'driver' => Stevebauman\Location\Drivers\FreeGeoIp::class,

    /*
    |--------------------------------------------------------------------------
    | Select Driver Fallbacks
    |--------------------------------------------------------------------------
    |
    | The drivers you want to use to grab location if the selected driver
    | is unavailable (in order).
    |
    */

    'fallbacks' => [

        Stevebauman\Location\Drivers\IpInfo::class,

        Stevebauman\Location\Drivers\GeoPlugin::class,

        Stevebauman\Location\Drivers\MaxMind::class,

    ],

    /*
    |--------------------------------------------------------------------------
    | Localhost Testing
    |--------------------------------------------------------------------------
    |
    | If your running your website locally and want to test different
    | IP addresses to see location detection set to true.
    |
    */

    'localhost_testing' => true,

    /*
    |--------------------------------------------------------------------------
    | Localhost Forget Location
    |--------------------------------------------------------------------------
    |
    | Removes the location key from the session so it is retrieved
    | on every request for testing purposes.
    |
    */

    'localhost_forget_location' => true,

    /*
    |--------------------------------------------------------------------------
    | Localhost Testing IP Address
    |--------------------------------------------------------------------------
    |
    | The IP address to use for localhost testing.
    |
    | The default IP is a Google Host in US.
    |
    */

    'localhost_testing_ip' => '66.102.0.0',

];
