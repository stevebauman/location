<?php

namespace Stevebauman\Location\Drivers;

use Stevebauman\Location\Objects\Location;
use Stevebauman\Location\Drivers\DriverInterface;

class IpInfo implements DriverInterface {
    
    public function __construct($config)
    {
        $this->config = $config;
        
        $this->url = $this->config->get('location::drivers.IpInfo.url');
    }
    
    public function get($ip)
    {
        $location = new Location;
        
        try {
            $contents = json_decode(file_get_contents($this->url.$ip));
            
            $location->countryCode = $contents->country;

            $countries = $this->config->get('location::country_codes');

            /*
             * See if we can convert the country code to the country name
             */
            if(array_key_exists($location->countryCode, $countries)) {
                $location->countryName = $countries[$location->countryCode];
            }
            
            $location->regionName = $contents->region;
            
            $location->cityName = $contents->city;
            
            $coords = explode(',', $contents->loc);
            
            $location->latitude = $coords[0];
            $location->longitude = $coords[1];
            
        } catch(\Exception $e){
            $location->error = true;
        }

        return $location;
    }
    
}