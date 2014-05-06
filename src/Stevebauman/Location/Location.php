<?php namespace Stevebauman\Location;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Request;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

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
	
	private $base_url;
	
	public function __construct(Repository $config){
		$this->config = $config;
		$this->countries = $this->config->get('location::country_codes');
		$this->driver = $this->root_namespace.$this->driver_namespace.$this->config->get('location::selected_driver');
		$this->setLocation();
		$this->base_url = URL::to('/');
	}
	
	
	/**
	 * Magic method for calling location attributes such as Location::get_country_code();
	 *
	 * @return NULL
	 */
	public function __call($method, $arguments){
		$attributes = explode('_', $method);
		if(in_array('get', $attributes)){
			$field = '';
			
			foreach($attributes as $attribute){
				if($attribute != 'get'){
					if($attribute === end($attributes)){
						$field .= $attribute;
					} else{
						$field .= $attribute.'_';
					}
				}
			}
			return $this->get($field);
		}
	}
	
	/**
	 * Sets location array to driver's location response
	 *
	 * @return NULL
	 */
	private function setLocation(){
		$driver = new $this->driver;
		$driver->set($this->getClientIP());
		$driver_fields = $this->config->get('location::drivers.'.$this->config->get('location::selected_driver').'.fields');
		$location = $driver->get();
		
		foreach($driver_fields as $field=>$value){
			$this->location[$field] = $location[$value];
		}
	}
	
	/**
	 * Returns location array
	 *
	 * @return mixed (array() or string())
	 */
	public function get($field = NULL){
		if($field){
			if(array_key_exists($field, $this->location)){
				return $this->location[$field];
			}
		} else{
			return $this->location;
		}
	}
	
	
	public function getCountryCode(){
		if(array_key_exists('country_code', $this->location)){
			
			return strtolower($this->location['country_code']);
		} else{
			return false;
		}
	}
	
	/**
	 * Get the client IP address.
	 *
	 * Thanks to: https://github.com/Torann/laravel-4-geoip/blob/master/src/Torann/GeoIP/GeoIP.php
	 * @return string
	 */
	private function getClientIP()
	{
		if($this->config->get('location::localhost_testing')){
			return $this->config->get('location::localhost_testing_ip');
		} else{
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
	
	public function url(){
		$url = parse_url(URL::current());
		if(array_key_exists(strtoupper(Request::segment(1)), $this->countries)){
			return $url['host'].$url['path'];
		} else{
			$url = parse_url(URL::current());
			return $url['host'].'/ca'.$url['path'];
		}
	}
	

}