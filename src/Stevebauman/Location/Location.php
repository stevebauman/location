<?php

namespace Stevebauman\Location;

use Illuminate\Session\SessionManager as Session;
use Illuminate\Config\Repository as Config;
use Stevebauman\Location\Exceptions\LocationFieldDoesNotExistException;
use Stevebauman\Location\Exceptions\DriverDoesNotExistException;
use Stevebauman\Location\Exceptions\NoDriverAvailableException;

/**
 * Retrieves a users generic location based on their visiting IP address
 * 
 * @author Steve Bauman <steven_bauman_7@hotmail.com>
 * @package Stevebauman\Location
 * @license MIT
 */
class Location {
    
    /*
     * Holds the current driver object
     */
    private $driver;
    
    /*
     * Holds the current location object
     */
    private $location;
    
    public function __construct(Config $config, Session $session)
    {
        $this->config = $config;
        $this->session = $session;
        
        $this->setDriver();
    }
    
    /**
     * Returns the driver's location object. If a field is specified it will
     * return the matching location objects variable.
     * 
     * @param string $field
     * @throws Stevebauman\Location\Exceptions\LocationFieldDoesNotExistException
     * @return mixed
     */
    public function get($field = NULL)
    {
        if($field) {
            
            if(property_exists($this->location, $field)) {
                
                return $this->location->{$field};
                
            } else {
                
                $message = sprintf('Location field: %s does not exist. Please check the docs'
                        . ' to verify which fields are available.', $field);
                
                throw new LocationFieldDoesNotExistException($message);
                
            }
            
        }
        
        return $this->location;
    }
    
    /**
     * Returns a country array compatible with Laravel's Form::select()
     * 
     * @param string $value
     * @param string $name
     * @return array
     */
    public function lists($value = '', $name = '')
    {
        $countries = $this->config->get('location::country_codes');
        
        $list = array();
        
        /*
         * If no value or name set, grab the default dropdown config values
         */
        if(empty($value) && empty($name)){
            
            $dropdown_value = $this->config->get('location::dropdown_config.value');
            $dropdown_name = $this->config->get('location::dropdown_config.name');
            
        } else{
            
            $dropdown_value = $value;
            $dropdown_name = $name;
            
        }

        foreach($countries as $country_code => $country_name){
            $list[$$dropdown_value] = $$dropdown_name;
        }
        
        return $list;
    }
    
    /**
     * Depreciated function from Beta. Alias for lists function.
     * 
     * @param string $value
     * @param string $name
     * @return type
     */
    public function dropdown($value = '', $name = '')
    {
        return $this->lists($value, $name);
    }
    
    /**
     * Returns true/false if one of the properties on the selected driver
     * equals the specified field
     * 
     * @param string $field
     * @return boolean
     */
    public function is($field)
    {
        /*
         * Get all the location properties
         */
        $properties = get_object_vars($this->location);
        
        /*
         * Check each property and compare them to the inputted field
         */
        foreach($properties as $property) {
            
            if(strcasecmp($field, $property) === 0){
                return true;
            }
	
        }
        
        return false;
    }
    
    /**
     * Creates the selected driver instance and retrieves the location
     */
    private function setDriver()
    {
        /*
         * Retrive the current driver
         */
        $this->driver = $this->getDriver($this->config->get('location::selected_driver'));
        
        /*
         * Removes location from the session if config option is set
         */
        if($this->config->get('location::localhost_forget_location')) {
            $this->session->forget('location');
        }
        
        /*
         * Check if the location has already been set in the current session
         */
        if($this->session->has('location')) {
            
            /*
             * Set the current driver to the current session location
             */
            $this->location = $this->session->get('location');
            
        } else {
            
            /*
             * Session is new, grab the location and set the session
             */
            $this->location = $this->driver->get($this->getClientIP());
            
            /*
             * Returned object variable 'error' will be true if an exception has
             * occured trying to grab the location from the driver. Let's
             * try retrieving the location from one of our fallbacks
             */
            if($this->location->error) {
                
                $this->location = $this->getLocationFromFallback();
                
            }
            
            $this->session->set('location', $this->location);
        }
    }
    
    /**
     * Returns a fallback driver location
     * 
     * @return \Stevebauman\Location\Objects\Location
     * @throws Stevebauman\Location\Exceptions\NoDriverAvailableException
     */
    private function getLocationFromFallback()
    {
        $fallbacks = $this->config->get('location::selected_driver_fallbacks');
        
        foreach($fallbacks as $fallbackDriver) {
            
            $driver = $this->getDriver($fallbackDriver);
            
            $location = $driver->get($this->getClientIP());
            
            /*
             * If no error has occured, return the new location
             */
            if(!$location->error) {
                
                return $location;
                
            }
            
            /*
             * Errors occured on trying to get each driver location,
             * throw no driver available exception
             */
            if($fallbackDriver === end($fallbacks)) {
                
                $message = sprintf('No Location drivers are available. Last driver tried was: %s.'
                        . ' Did you forget to set up your MaxMind GeoLite2-City.mmdb?', get_class($driver));
                
                throw new NoDriverAvailableException($message);
                
            }
            
        }
    }
    
    /**
     * Returns the client IP address. Will return the set config IP if localhost
     * testing is set to true
     *
     * Thanks to: https://github.com/Torann/laravel-4-geoip/blob/master/src/Torann/GeoIP/GeoIP.php
     * @return string
     */
    private function getClientIP()
    {
        if($this->config->get('location::localhost_testing')) {
            
            return $this->config->get('location::localhost_testing_ip');
            
        } else {
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
            else if(filter_input('INPUT_SERVER', 'REMOTE_ADDR')) {
                $ipaddress = filter_input('INPUT_SERVER', 'REMOTE_ADDR');
            }
            else {
                $ipaddress = $this->config->get('location::default_ip');
            }

            return $ipaddress;
        }
    }
    
    /**
     * Returns the specified driver
     * 
     * @param string $driver
     * @throws Stevebauman\Location\Exceptions\DriverDoesNotExistException
     */
    private function getDriver($driver)
    {
        $namespace = $this->config->get('location::driver_namespace');
        
        $driverStr = $namespace.$driver;
        
        if(class_exists($driverStr)) {

            return new $driverStr($this->config);
            
        } else {
            
            $message = sprintf('The driver: %s, does not exist. Please check the docs and'
                    . ' verify that it does.', $driver);
            
            throw new DriverDoesNotExistException($message);
            
        }
        
    }

}