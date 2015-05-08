<?php

namespace Stevebauman\Location\Drivers;

use Stevebauman\Location\Objects\Location;
use Stevebauman\Location\Location as LocationInstance;

/**
 * The FreeGeoIp driver.
 *
 * Class FreeGeoIp
 */
class FreeGeoIp implements DriverInterface
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

        $this->url = $this->config->get('location'.$this->instance->getConfigSeparator().'drivers.FreeGeoIp.url');
    }

    /**
     * Retrieves the location from the driver FreeGeoIp and returns a location object.
     *
     * @param string $ip
     *
     * @return Location
     */
    public function get($ip)
    {
        $location = new Location();

        try {
            $contents = json_decode(file_get_contents($this->url.$ip), true);

            $location->ip = $ip;

            if (array_key_exists('country_code', $contents)) {
                $location->countryCode = $contents['country_code'];
            }

            if (array_key_exists('country_name', $contents)) {
                $location->countryName = $contents['country_name'];
            }

            if (array_key_exists('region_code', $contents)) {
                $location->regionCode = $contents['region_code'];
            }

            if (array_key_exists('region_name', $contents)) {
                $location->regionName = $contents['region_name'];
            }

            if (array_key_exists('city', $contents)) {
                $location->cityName = $contents['city'];
            }

            if (array_key_exists('zip_code', $contents)) {
                $location->zipCode = $contents['zip_code'];
            }

            if (array_key_exists('latitude', $contents)) {
                $location->latitude = $contents['latitude'];
            }

            if (array_key_exists('longitude', $contents)) {
                $location->longitude = $contents['longitude'];
            }

            if (array_key_exists('metro_code', $contents)) {
                $location->metroCode = $contents['metro_code'];
            }

            if (array_key_exists('area_code', $contents)) {
                $location->areaCode = $contents['area_code'];
            }

            $location->driver = get_class($this);
        } catch (\Exception $e) {
            $location->error = true;
        }

        return $location;
    }
}
