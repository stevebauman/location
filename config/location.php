<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Driver
    |--------------------------------------------------------------------------
    |
    | The default driver you would like to use for location retrieval.
    |
    */

    'driver' => Stevebauman\Location\Drivers\IpApi::class,

    /*
    |--------------------------------------------------------------------------
    | Driver Fallbacks
    |--------------------------------------------------------------------------
    |
    | The drivers you want to use to retrieve the user's location
    | if the above selected driver is unavailable.
    |
    | These will be called upon in order (first to last).
    |
    */

    'fallbacks' => [
        Stevebauman\Location\Drivers\Ip2locationio::class,
        Stevebauman\Location\Drivers\IpInfo::class,
        Stevebauman\Location\Drivers\GeoPlugin::class,
        Stevebauman\Location\Drivers\MaxMind::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Position
    |--------------------------------------------------------------------------
    |
    | Here you may configure the position instance that is created
    | and returned from the above drivers. The instance you
    | create must extend the built-in Position class.
    |
    */

    'position' => Stevebauman\Location\Position::class,

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    |
    | Here you may configure the options used by the underlying
    | Laravel HTTP client. This will be used in drivers that
    | request info via HTTP requests through API services.
    |
    */

    'http' => [
        'timeout' => 3,
        'connect_timeout' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Localhost Testing
    |--------------------------------------------------------------------------
    |
    | If your running your website locally and want to test different
    | IP addresses to see location detection, set 'enabled' to true.
    |
    | The testing IP address is a Google host in the United-States.
    |
    */

    'testing' => [
        'ip' => '66.102.0.0',
        'enabled' => env('LOCATION_TESTING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | MaxMind Configuration
    |--------------------------------------------------------------------------
    |
    | If web service is enabled, you must fill in your user ID and license key.
    |
    | If web service is disabled, it will try and retrieve the user's location
    | from the MaxMind database file located in the local path below.
    |
    | The MaxMind database file can be either City (default) or Country (smaller).
    |
    */

    'maxmind' => [
        'license_key' => env('MAXMIND_LICENSE_KEY'),

        'web' => [
            'enabled' => false,
            'user_id' => env('MAXMIND_USER_ID'),
            'options' => ['host' => 'geoip.maxmind.com'],
        ],

        'local' => [
            'type' => 'city',
            'path' => database_path('maxmind/GeoLite2-City.mmdb'),
            'url' => sprintf('https://download.maxmind.com/app/geoip_download_by_token?edition_id=GeoLite2-City&license_key=%s&suffix=tar.gz', env('MAXMIND_LICENSE_KEY')),
        ],
    ],

    'ip_api' => [
        'token' => env('IP_API_TOKEN'),
    ],

    'ipinfo' => [
        'token' => env('IPINFO_TOKEN'),
    ],

    'ipdata' => [
        'token' => env('IPDATA_TOKEN'),
    ],

    'ip2locationio' => [
        'token' => env('IP2LOCATIONIO_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Kloudend ~ ipapi.co Configuration
    |--------------------------------------------------------------------------
    |
    | The configuration for the Kloudend driver.
    |
    */

    'kloudend' => [

        'token' => env('KLOUDEND_TOKEN'),

    ],

];
