<?php

namespace Stevebauman\Location\Drivers;

use GeoIp2\Database\Reader;
use GeoIp2\WebService\Client;
use Stevebauman\Location\Objects\Location;
use Stevebauman\Location\Location as LocationInstance;

class MaxMind implements DriverInterface
{
    /**
     * Holds the current Location class instance.
     *
     * @var LocationInstance
     */
    private $instance;

    /**
     * Holds the configuration instance.
     *
     * @var \Illuminate\Config\Repository
     */
    private $config;

    /**
     * Constructor.
     *
     * @param LocationInstance $instance
     */
    public function __construct(LocationInstance $instance)
    {
        $this->instance = $instance;

        $this->config = $this->instance->getConfig();
    }

    /**
     * Retrieves the location from the driver MaxMind and returns a location object.
     *
     * @param string $ip
     *
     * @return Location
     */
    public function get($ip)
    {
        $location = new Location();

        $settings = $this->config->get('location.drivers.MaxMind.configuration');

        try {
            if ($settings['web_service']) {
                $maxmind = new Client($settings['user_id'], $settings['license_key']);
            } else {
                $path = database_path('maxmind/GeoLite2-City.mmdb');

                $maxmind = new Reader($path);
            }

            $record = $maxmind->city($ip);

            $location->ip = $ip;

            $location->isoCode = $record->country->isoCode;

            $location->countryName = $record->country->name;

            $location->cityName = $record->city->name;

            $location->postalCode = $record->postal->code;

            $location->latitude = $record->location->latitude;

            $location->driver = get_class($this);
        } catch (\Exception $e) {
            $location->error = true;
        }

        return $location;
    }
}
