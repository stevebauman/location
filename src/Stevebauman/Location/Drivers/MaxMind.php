<?php namespace Stevebauman\Location\Drivers;

use GeoIp2\Database\Reader;
use GeoIp2\WebService\Client;
use Stevebauman\Location\Objects\Location;
use Stevebauman\Location\Drivers\DriverInterface;

class MaxMind implements DriverInterface {	
	
        public function __construct($config)
        {
            $this->config = $config;
        }
    
	/**
	* Retrieves the current user location from MaxMind
   	*/
	public function get($ip){
            
            $location = new Location;
            
            $settings = $this->config->get('location::drivers.MaxMind.configuration');

            try {
                
                if($settings['web_service']) {
                    $maxmind = new Client($settings['user_id'], $settings['license_key']);
                }
                else {
                    $maxmind = new Reader(app_path().'/database/maxmind/GeoLite2-City.mmdb');
                }
                
                $record = $maxmind->city($ip);
                
                $location->isoCode = $record->country->isoCode;
                
                $location->countryName = $record->country->name;
                
                $location->cityName = $record->city->name;
                
                $location->postalCode = $record->postal->code;
                
                $location->latitude = $record->location->latitude;
                
                $location->driver = get_class($this);
                
            } catch(\Exception $e){
                $location->error = true;
            }
            
            return $location;
	}
}