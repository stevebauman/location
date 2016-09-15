<?php

namespace Stevebauman\Location;

use Stevebauman\Location\Drivers\DriverInterface;
use Stevebauman\Location\Exceptions\InvalidIpException;
use Stevebauman\Location\Exceptions\LocationFieldDoesNotExistException;
use Stevebauman\Location\Exceptions\DriverDoesNotExistException;
use Stevebauman\Location\Exceptions\NoDriverAvailableException;

class Location
{
    /*
     * Stores the current driver object
     *
     * @var DriverInterface
     */
    protected $driver;

    /*
     * Stores the current location object
     *
     * @var \Stevebauman\Location\Objects\Location
     */
    protected $location;

    /*
     * Stores the current IP of the user
     *
     * @var string
     */
    protected $ip;

    /**
     * Constructor.
     *
     * @throws DriverDoesNotExistException
     */
    public function __construct()
    {
        $this->setDefaultDriver();
    }

    /**
     * Creates the selected driver instance and sets the driver property.
     *
     * @param DriverInterface $driver
     */
    public function setDriver(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Sets the default driver from the configuration.
     *
     * @throws DriverDoesNotExistException
     */
    public function setDefaultDriver()
    {
        $selected = $this->getDefaultDriver();

        $this->setDriver($this->getDriver($selected));
    }

    /**
     * Returns the driver's location object. If a field is specified it will
     * return the matching location objects variable.
     *
     * @param string $ip
     * @param string $field
     *
     * @return \Stevebauman\Location\Objects\Location|array|string
     *
     * @throws LocationFieldDoesNotExistException
     */
    public function get($ip = '', $field = '')
    {
        $this->setLocation($ip);

        if ($field) {
            if (property_exists($this->location, $field)) {
                return $this->location->{$field};
            } else {
                $message = sprintf('Location field: %s does not exist. Please check the docs'
                    .' to verify which fields are available.', $field);

                throw new LocationFieldDoesNotExistException($message);
            }
        }

        return $this->location;
    }

    /**
     * Returns a country array compatible with Laravel's Form::select().
     *
     * @param string $value
     * @param string $name
     *
     * @return array
     */
    public function lists($value = '', $name = '')
    {
        $countries = $this->getCountryCodes();

        $list = [];

        // If no value or name set, grab the
        // default dropdown config values
        if (empty($value) && empty($name)) {
            $dropdownValue = $this->getDropdownValue();
            $dropdownName = $this->getDropdownName();
        } else {
            $dropdownValue = $value;
            $dropdownName = $name;
        }

        foreach ($countries as $country_code => $country_name) {
            $list[$$dropdownValue] = $$dropdownName;
        }

        return $list;
    }

    /**
     * Returns true/false if one of the properties on the selected driver
     * equals the specified field.
     *
     * @param string $field
     *
     * @return bool
     */
    public function is($field)
    {
        // Get all the location properties
        $properties = get_object_vars($this->location);

        // Check each property and compare
        // them to the inputted field
        foreach ($properties as $property) {
            if (strcasecmp($field, $property) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sets the location property to the drivers returned location object.
     *
     * @param string $ip
     */
    protected function setLocation($ip = '')
    {
        // The location session key.
        $key = 'location';

        // Removes location from the session if config option is set
        if ($this->localHostForgetLocation()) {
            session()->forget($key);
        }

        // Check if the location has already been set in the current session
        if (session()->has($key)) {
            // Set the current driver to the current session location
            $this->location = session($key);
        } else {
            $this->setIp($ip);

            $this->location = $this->driver->get($this->ip);

            // The locations object property 'error' will be true if an
            // exception has occurred trying to grab the location
            // from the driver. Let's try retrieving the
            // location from one of our fall-backs
            if ($this->location->error) {
                $this->location = $this->getLocationFromFallback();
            }

            session([$key => $this->location]);
        }
    }

    /**
     * Sets the current IP property. If an IP address is supplied, it is validated
     * before it's set, otherwise it is grabbed automatically from the client.
     *
     * @param string $ip
     */
    protected function setIp($ip = null)
    {
        // If an IP address is supplied, we'll validate it and
        // set it, otherwise we'll grab it automatically
        // from the client.
        $this->ip = $ip ? $this->validateIp($ip) : $this->getClientIP();
    }

    /**
     * Returns the IP address if it is valid, throws an exception if it's not.
     *
     * @param string $ip
     *
     * @return mixed
     *
     * @throws InvalidIpException
     */
    protected function validateIp($ip)
    {
        $filteredIp = filter_var($ip, FILTER_VALIDATE_IP);

        if ($filteredIp) {
            return $filteredIp;
        }

        $message = sprintf('The IP Address: %s is invalid', $ip);

        throw new InvalidIpException($message);
    }

    /**
     * Returns a fallback driver location.
     *
     * @return \Stevebauman\Location\Objects\Location
     *
     * @throws NoDriverAvailableException
     */
    protected function getLocationFromFallback()
    {
        $fallbacks = $this->getDriverFallbackList();

        if (is_array($fallbacks) && count($fallbacks) > 0) {
            foreach ($fallbacks as $fallbackDriver) {
                $driver = $this->getDriver($fallbackDriver);

                $location = $driver->get($this->ip);

                // If no error has occurred, return the new location
                if (!$location->error) {
                    return $location;
                }
            }
        }

        // Errors occurred on trying to get a location from
        // each driver, or no fallback drivers exist.
        // Throw no driver available exception
        $message = sprintf('No Location drivers are available.');

        throw new NoDriverAvailableException($message);
    }

    /**
     * Returns the client IP address. Will return the set config IP if localhost
     * testing is set to true.
     *
     * @thanks https://github.com/Torann/laravel-4-geoip/blob/master/src/Torann/GeoIP/GeoIP.php
     *
     * @return string
     */
    protected function getClientIP()
    {
        return $this->localHostTesting() ? $this->getLocalHostTestingIp() : request()->ip();
    }

    /**
     * Retrieves the config option for localhost testing.
     *
     * @return bool
     */
    protected function localHostTesting()
    {
        return config('location.localhost_testing', true);
    }

    /**
     * Retrieves the config option for forgetting the location from the current session.
     *
     * @return bool
     */
    protected function localHostForgetLocation()
    {
        return config('location.localhost_forget_location', false);
    }

    /**
     * Retrieves the config option for the localhost testing IP.
     *
     * @return string
     */
    protected function getLocalHostTestingIp()
    {
        return config('location.localhost_testing_ip', '66.102.0.0');
    }

    /**
     * Retrieves the config option for select driver fallbacks.
     *
     * @return array
     */
    protected function getDriverFallbackList()
    {
        return config('location.selected_driver_fallbacks', []);
    }

    /**
     * Retrieves the config option for country codes.
     *
     * @return array
     */
    protected function getCountryCodes()
    {
        return config('location.country_codes');
    }

    /**
     * Retrieves the config option for the dropdown value.
     *
     * @return string
     */
    protected function getDropdownValue()
    {
        return config('location.dropdown_config.value', 'country_code');
    }

    /**
     * Retrieves the config option for the dropdown name.
     *
     * @return string
     */
    protected function getDropdownName()
    {
        return config('location.dropdown_config.name', 'country_name');
    }

    /**
     * Returns the selected driver
     *
     * @return \Illuminate\Support\Facades\Config
     */
    protected function getDefaultDriver()
    {
        return config('location.selected_driver');
    }

    /**
     * Returns the specified driver.
     *
     * @param string $name
     *
     * @return DriverInterface
     *
     * @throws DriverDoesNotExistException
     */
    protected function getDriver($name)
    {
        $driver = config("location.drivers.$name.class");

        if (class_exists($driver)) {
            return new $driver($this);
        } else {
            $message = sprintf('The driver: %s, does not exist.', $driver);

            throw new DriverDoesNotExistException($message);
        }
    }
}
