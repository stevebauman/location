<?php namespace Stevebauman\Location\Drivers;

use GeoIp2\Database\Reader;
use GeoIp2\WebService\Client;

class MaxMind {
	
	private $location = array();
	protected $config;
	
	
	public function __construct(){
		global $app;
		$this->config = $app['config'];
	}	
	
	/**
	* Sets location array to
	*
	* Thanks to Torann: https://github.com/Torann/laravel-4-geoip/blob/master/src/Torann/GeoIP/GeoIP.php
	* @param string $ip
   	*/
	public function set($ip){
		$settings = $this->config->get('location::drivers.MaxMind.configuration');
		
		try{
			if($settings['web_service']) {
				$maxmind = new Client($settings['user_id'], $settings['license_key']);
			}
			else {
				$maxmind = new Reader(app_path().'/database/maxmind/GeoLite2-City.mmdb');
			}
			
			$record = $maxmind->city($ip);
			
			$this->location = array(
				"ip"			=> $ip,
				"isoCode" 		=> $record->country->isoCode,
				"country" 		=> $record->country->name,
				"city" 			=> $record->city->name,
				"state" 		=> $record->mostSpecificSubdivision->isoCode,
				"postal_code" 	=> $record->postal->code,
				"lat" 			=> $record->location->latitude,
				"lon" 			=> $record->location->longitude,
				"default"       => false
			);
	
			unset($record);
	
			return $this->location;
		} catch(\Exception $e){
			$this->location = false;
		}
	}

	
	public function get(){
		return $this->location;
	}
}