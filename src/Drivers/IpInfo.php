<?php

namespace Stevebauman\Location\Drivers;

use Stevebauman\Location\Objects\Location;
use Stevebauman\Location\Location as LocationInstance;

/**
 * The IpInfo driver.
 *
 * Class IpInfo
 */
class IpInfo implements DriverInterface
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
     */
    private $url;

    /**
     * @param LocationInstance $instance
     */
    public function __construct(LocationInstance $instance)
    {
        $this->instance = $instance;

        $this->config = $this->instance->getConfig();

        $this->url = $this->config->get('location'.$this->instance->getConfigSeparator().'drivers.IpInfo.url');
    }

    /**
     * Retrieves the location from the driver IpInfo and returns a location object.
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
                $location->countryCode = $contents->country;
            }

            if (property_exists($contents, 'postal')) {
                $location->postalCode = $contents->postal;
            }

            if (property_exists($contents, 'region')) {
                $location->regionName = $contents->region;
            }

            if (property_exists($contents, 'city')) {
                $location->cityName = $contents->city;
            }

            if (property_exists($contents, 'loc')) {
                $coords = explode(',', $contents->loc);

                if (array_key_exists(0, $coords)) {
                    $location->latitude = $coords[0];
                }

                if (array_key_exists(1, $coords)) {
                    $location->longitude = $coords[1];
                }
            }

            $countries = $this->config->get('location'.$this->instance->getConfigSeparator().'country_codes');

            /*
             * See if we can convert the country code to the country name
             */
            if (is_array($countries) && array_key_exists($location->countryCode, $countries)) {
                $location->countryName = $countries[$location->countryCode];
            }

            $location->driver = get_class($this);
        } catch (\Exception $e) {
            $location->error = true;
        }

        return $location;
    }
}
