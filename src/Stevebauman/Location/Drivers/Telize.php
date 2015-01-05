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
            
            if(property_exists($contents, 'country')) {
                $location->countryName = $contents->country; 
            }
            
            if(property_exists($contents, 'country_code')) {
                $location->countryCode = $contents->country_code;  
            }
            
            if(property_exists($contents, 'region')) {
                $location->regionName = $contents->region;
            }
            
            if(property_exists($contents, 'region_code')) {
                $location->regionCode = $contents->region_code;
            }
            
            if(property_exists($contents, 'city')) {
                $location->cityName = $contents->city;
            }
            
            if(property_exists($contents, 'postal_code')) {
                $location->postalCode = $contents->postal_code;
            }
            
            if(property_exists($contents, 'longitude')) {
                $location->longitude = $contents->longitude;
            }
            
            if(property_exists($contents, 'latitude')) {
                $location->latitude = $contents->latitude;
            }
            
            if(property_exists($contents, 'isp')) {
                $location->isp = $contents->isp;
            }
            
            $location->driver = get_class($this);
            
        } catch (\Exception $e) {

            $location->error = true;
            
        }
        
        return $location;
    }
    
}