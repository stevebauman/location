<?php

namespace Stevebauman\Location\Drivers;

use Stevebauman\Location\Objects\Location;
use Stevebauman\Location\Location as LocationInstance;

/**
 * The GeoPlugin driver.
 *
 * Class GeoPlugin
 */
class GeoPlugin implements DriverInterface
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

        $this->url = $this->config->get('location'.$this->instance->getConfigSeparator().'drivers.GeoPlugin.url');
    }

    /**
     * Retrieves the location from the driver GeoPlugin and returns a location object.
     *
     * @param string $ip
     *
     * @return Location
     */
    public function get($ip)
    {
        $location = new Location();

        try {
            $contents = unserialize(file_get_contents($this->url.$ip));

            $location->ip = $ip;

            if (array_key_exists('geoplugin_countryCode', $contents)) {
                $location->countryCode = $contents['geoplugin_countryCode'];
            }

            if (array_key_exists('geoplugin_countryName', $contents)) {
                $location->countryName = $contents['geoplugin_countryName'];
            }

            if (array_key_exists('geoplugin_regionName', $contents)) {
                $location->regionName = $contents['geoplugin_regionName'];
            }

            if (array_key_exists('geoplugin_city', $contents)) {
                $location->regionName = $contents['geoplugin_city'];
            }

            if (array_key_exists('geoplugin_longitude', $contents)) {
                $location->longitude = $contents['geoplugin_longitude'];
            }

            if (array_key_exists('geoplugin_latitude', $contents)) {
                $location->latitude = $contents['geoplugin_latitude'];
            }

            if (array_key_exists('geoplugin_areaCode', $contents)) {
                $location->areaCode = $contents['geoplugin_areaCode'];
            }

            if (array_key_exists('geoplugin_regionCode', $contents)) {
                $location->regionCode = $contents['geoplugin_regionCode'];
            }

            if (array_key_exists('geoplugin_regionName', $contents)) {
                $location->regionName = $contents['geoplugin_regionName'];
            }

            $location->driver = get_class($this);
        } catch (\Exception $e) {
            $location->error = true;
        }

        return $location;
    }
}
