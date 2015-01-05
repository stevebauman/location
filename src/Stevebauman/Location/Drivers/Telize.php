<?php

namespace Stevebauman\Location\Drivers;

use Stevebauman\Location\Objects\Location;
use Stevebauman\Location\Drivers\DriverInterface;

class Telize implements DriverInterface {
    
    public function __construct($config)
    {
        $this->config = $config;
        
        $this->url = $this->config->get('location::drivers.Telize.url');

    }
    
    public function get($ip)
    {
        $location = new Location;
        
        try {
            $contents = json_decode(file_get_contents($this->url.$ip));

            $location->ip = $ip;
            
            $location->countryName = $contents->country;
            
            $location->countryCode = $contents->country_code;
            
            $location->regionName = $contents->region;
            
            $location->regionCode = $contents->region_code;
            
            $location->cityName = $contents->city;
            
            $location->postalCode = $contents->postal_code;
            
            $location->longitude = $contents->longitude;
            
            $location->latitude = $contents->latitude;
            
            $location->driver = get_class($this);
            
        } catch (\Exception $e) {
            
            $location->error = true;
            
        }
        
        return $location;
    }
    
}