<?php namespace Stevebauman\Location;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Request;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Database\Eloquent\Collection;

class Location {
	
	/**
     * Illuminate config repository.
     *
     * @var Illuminate\Config\Repository
     */
    protected $config;
	
	protected $driver;
	protected $driver_fallbacks;
	protected $driver_namespace = 'Drivers\\';
	
	protected $root_namespace = 'Stevebauman\\Location\\';
	
	public $location = array();
	
	private $countries = array();
	private $allowed_countries = array();
	
	private $allowed_attributes = array(
		'get', 'prefix', 'dropdown', 'is'
	);
	
	private $base_url;
	
	public function __construct(Repository $config){
		$this->config = $config;
		$this->countries = $this->config->get('location::country_codes');
		$this->allowed_countries = $this->config->get('location::allowed_countries');
		$this->driver = $this->root_namespace.$this->driver_namespace.$this->config->get('location::selected_driver');
		
		//Backwards compatibility with previous versions
		if($this->config->get('location::selected_driver_fallbacks')){
			foreach($this->config->get('location::selected_driver_fallbacks') as $driver){
				$this->driver_fallbacks[$driver] = $this->root_namespace.$this->driver_namespace.$driver;
			}
		} else{
			$this->driver_fallbacks = false;
		}
		
		$this->setLocation();
		$this->base_url = URL::to('/');
	}
	
	/**
	 * Magic method for calling location attributes such as Location::get_country_code();
	 *
	 * @return NULL
	 */
	public function __call($method, $arguments){
		$attributes = explode('_', strtolower($method));
		if(in_array($attributes[0], $this->allowed_attributes)){
			$field = '';
			foreach($attributes as $attribute){
				if(!in_array($attribute, $this->allowed_attributes)){
					if($attribute === end($attributes)){
						$field .= $attribute;
					} else{
						$field .= $attribute.'_';
					}
				}
			}
			return call_user_func(array($this, $attributes[0]), $field);
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
		$driver_location = $driver->get();
		
		//Check if a driver location was grabbed, and if driver fallbacks are enabled
		if(!$driver_location && $this->driver_fallbacks){
			foreach($this->driver_fallbacks as $config_key=>$fallback_driver){
				$driver = new $fallback_driver;
				$driver->set($this->getClientIP());
				$driver_fields = $this->config->get('location::drivers.'.$config_key.'.fields');
				$driver_location = $driver->get();
				if($driver_location) break;
			}
		}
		
		foreach($driver_fields as $field=>$value){
			$this->location[$field] = $driver_location[$value];
		}
		
		$this->location = new Collection($this->location);
		
		Session::put('location', $this->location);
	}
	
	
	/**
	 * Returns location array or location attribute such as 'country_code', 'country_name'
	 *
	 * @return mixed (array(), string() or boolean)
	 */
	public function get($field = NULL){
		if($this->location){
			if($field){
				return $this->location->get($field);
			} else{
				return $this->location;
			}
		} else{
			return false;
		}
	}
	
	/**
	 * Returns location field meant for a route such as:
	 *
	 * 'country_code' as 'US', would return 'us', or 'country_name' as 'United States', would return 'united-states'
	 *
	 * @param $field (string)
	 * @return mixed (string() or boolean)
	 */
	public function prefix($field = NULL){
		if($field){
			if(array_key_exists($field, $this->location)){
				return strtolower(str_replace(' ', '-', $this->location[$field]));
			}
		} else{
			return false;
		}
	}
	
	/**
	 * Returns an array of countries meant for laravel dropdown boxes (Form::select())
	 *
	 * @param $value (string)
	 * @param $name (string)
	 * @return array
	 *
	 */
	public function dropdown($value = NULL, $name = NULL){
		$countries = array();
		
		//If no value or name set, grab the default dropdown config values
		if(!$value && !$name){
			$dropdown_value = $this->config->get('location::dropdown_config.value');
			$dropdown_name = $this->config->get('location::dropdown_config.name');
		} else{
			$dropdown_value = $value;
			$dropdown_name = $name;
		}
		
		foreach($this->countries as $country_code=>$country_name){
			//Double $ sign indicates that i'm grabbing a variable from a string: http://us2.php.net/language.variables.variable
			$countries[$$dropdown_value] = $$dropdown_name;
		}
		return $countries;
	}
	
	
	/**
	* Returns true/false if the users current location is equal to the inputted country & city
	**/
	public function is($country){
		$country = str_replace('_', ' ',$country);
		foreach($this->get() as $field){
			if(strcasecmp($country, $field) == 0){
				return true;
			}
		}
		return false;
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
				$ipaddress = $this->config->get('location::default_ip');
			}
	
			return $ipaddress;
		}
	}
}