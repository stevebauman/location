<?php namespace Stevebauman\Location\Drivers;

class GeoPlugin {
	
	private $url = 'http://www.geoplugin.net/php.gp?ip=';
	
	private $location = array();
	
	public function __construct(){
		
	}
	
	
	/**
	* Sets location array to
	*
	* @param string $ip
   	*/
	public function set($ip){
		try{
			$this->location = unserialize(file_get_contents($this->url.$ip));
		} catch(\Exception $e){
			$this->location = false;
		}
	}
	
	public function get(){
		return $this->location;
	}
}