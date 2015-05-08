<?php

namespace Stevebauman\Location\Drivers;

use Stevebauman\Location\Objects\Location;
use Stevebauman\Location\Location as LocationInstance;

/**
 * The Telize driver.
 *
 * Class Telize
 */
class Telize implements DriverInterface
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

    /*
     * Holds the drivers URL
     *
     * @var string
     */
    private $url;

    /**
     * @param LocationInstance $instance
     */
    public function __construct(LocationInstance $instance)
    {
        $this->instance = $instance;

        $this->config = $this->instance->getConfig();

        $this->url = $this->config->get('location'.$this->instance->getConfigSeparator().'drivers.Telize.url');
    }

    /**
     * Retrieves the location from the driver Telize and returns a location object.
     *
     * @param string $ip
     *
     * @return Location
     */
    public function get($ip)
    {
        $location = new Location();

        try {
            $contents = json_decode(file_get_contents($this->url.$ip));

            $location->ip = $ip;

            if (property_exists($contents, 'country')) {
                $location->countryName = $contents->country;
            }

            if (property_exists($contents, 'country_code')) {
                $location->countryCode = $contents->country_code;
            }

            if (property_exists($contents, 'region')) {
                $location->regionName = $contents->region;
            }

            if (property_exists($contents, 'region_code')) {
                $location->regionCode = $contents->region_code;
            }

            if (property_exists($contents, 'city')) {
                $location->cityName = $contents->city;
            }

            if (property_exists($contents, 'postal_code')) {
                $location->postalCode = $contents->postal_code;
            }

            if (property_exists($contents, 'longitude')) {
                $location->longitude = $contents->longitude;
            }

            if (property_exists($contents, 'latitude')) {
                $location->latitude = $contents->latitude;
            }

            if (property_exists($contents, 'isp')) {
                $location->isp = $contents->isp;
            }

            $location->driver = get_class($this);
        } catch (\Exception $e) {
            $location->error = true;
        }

        return $location;
    }
}
