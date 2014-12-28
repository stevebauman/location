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
            
            if($contents->loc) {
            
                $coords = explode(',', $contents->loc);
                
                if(array_key_exists(0, $coords)) {
                    $location->latitude = $coords[0];
                }
                if(array_key_exists(1, $coords)) {
                    $location->longitude = $coords[1];
                }
            }
            
            $location->driver = get_class($this);
            
        } catch(\Exception $e){
            $location->error = true;
        }

        return $location;
    }
    
}