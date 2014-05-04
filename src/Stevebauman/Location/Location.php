<?php namespace Stevebauman\Location;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Request;
use Illuminate\Config\Repository;

class Location {
	
	/**
     * Illuminate config repository.
     *
     * @var Illuminate\Config\Repository
     */
    protected $config;
	
	protected $driver;
	protected $driver_namespace = 'Drivers\\';
	
	protected $root_namespace = 'Stevebauman\\Location\\';
	
	public $location = array();
	
	private $countries = array();
	private $allowed_countries = array();
	
	public function __construct(Repository $config){
		$this->config = $config;
		$this->countries = $this->config->get('location::country_codes');
		$this->driver = $this->root_namespace.$this->driver_namespace.$this->config->get('location::driver');
		$this->setLocation();
	}
	
	private function setLocation(){
		$driver = new $this->driver;
		
		$driver->set($this->getClientIP());
		$this->location = $driver->get();
	}
	
	public function getLocation(){
		return $this->location;
	}
	
	/**
	 * Get the client IP address.
	 *
	 * Thanks to: https://github.com/Torann/laravel-4-geoip/blob/master/src/Torann/GeoIP/GeoIP.php
	 * @return string
	 */
	private function getClientIP()
	{
		return '72.38.34.168';
		if (getenv('HTTP_CLIENT_IP')) {
			$ipaddress = getenv('HTTP_CLIENT_IP');
		}
		else if(getenv('HTTP_X_FORWARDED_FOR')) {
			$ipaddress = getenv('HTTP_X_FORWARDED_FOR');
		}
		else if(getenv('HTTP_X_FORWARDED')) {
			$ipaddress = getenv('HTTP_X_FORWARDED');
		}
		else if(getenv('HTTP_FORWARDED_FOR')) {
			$ipaddress = getenv('HTTP_FORWARDED_FOR');
		}
		else if(getenv('HTTP_FORWARDED')) {
			$ipaddress = getenv('HTTP_FORWARDED');
		}
		else if(getenv('REMOTE_ADDR')) {
			$ipaddress = getenv('REMOTE_ADDR');
		}
		else if( isset($_SERVER['REMOTE_ADDR']) ) {
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		}
		else {
			$ipaddress = $this->config->get('Location::default_ip');
		}

		return $ipaddress;
	}

}