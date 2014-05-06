<?php namespace Stevebauman\Location\Drivers;

class FreeGeoIp {
	
	private $url = 'http://freegeoip.net/json/';
	
	private $location = array();
	
	public function __construct(){
		
	}	
	
	/**
	* Sets location array to
	*
	* @param string $ip
   	*/
	public function set($ip){
		$this->location = json_decode(file_get_contents($this->url.$ip), true);
	}
	
	public function get(){
		return $this->location;
	}
}