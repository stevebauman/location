<?php namespace Stevebauman\Location\Drivers;

use Stevebauman\Location\Objects\Location;
use Stevebauman\Location\Drivers\DriverInterface;

class FreeGeoIp implements DriverInterface {
	
    public function __construct($config)
    {
        $this->config = $config;

        $this->url = $this->config->get('location::drivers.FreeGeoIp.url');
    }

    /**
    * Retrive the current user location from FreeGeoIP
    *
    * @param string $ip
    */
    public function get($ip)
    {
        $location = new Location;

        try {

            $contents = json_decode(file_get_contents($this->url.$ip), true);

            $location->countryName = $contents['country_code'];

            $location->regionCode = $contents['region_code'];

            $location->regionName = $contents['region_name'];

            $location->cityName = $contents['city'];

            $location->zipCode = $contents['zipcode'];

            $location->latitude = $contents['latitude'];

            $location->longitude = $contents['longitude'];

            $location->metroCode = $contents['metro_code'];

            $location->areaCode = $contents['area_code'];

        } catch(\Exception $e){
            $location->error = true;
        }

        return $location;
    }
}