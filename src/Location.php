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
     * Holds the current driver object
     *
     * @var \Stevebauman\Location\Drivers\DriverInterface
     */
    protected $driver;

    /*
     * Holds the current location object
     *
     * @var \Stevebauman\Location\Objects\Location
     */
    protected $location;

    /**
     * Holds the configuration instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * Holds the session instance.
     *
     * @var \Illuminate\Session\SessionManager
     */
    protected $session;

    /*
     * Holds the current IP of the user
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
        $this->config = app('config');
        $this->session = app('session');

        $key = $this->config->get('location.selected_driver');

        $driver = $this->config->get("location.drivers.$key.class");

        if(class_exists($driver)) {
            // Set the currently selected driver from the configuration
            $this->setDriver(new $driver($this));
        } else {
            $message = "Driver: $driver, does not exist.";

            throw new DriverDoesNotExistException($message);
        }
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
     * Returns the current configuration instance.
     *
     * @return ConfigContract
     */
    public function getConfig()
    {
        return $this->config;
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
     * Depreciated function from Beta. Alias for lists function.
     *
     * @param string $value
     * @param string $name
     *
     * @return array
     */
    public function dropdown($value = '', $name = '')
    {
        return $this->lists($value, $name);
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
     * Creates the selected driver instance and sets the driver property.
     *
     * @param DriverInterface $driver
     */
    private function setDriver(DriverInterface $driver)
    {
        // Retrieve the current driver
        $this->driver = $driver;
    }

    /**
     * Sets the location property to the drivers returned location object.
     *
     * @param string $ip
     */
    private function setLocation($ip = '')
    {
        // Removes location from the session if config option is set
        if ($this->localHostForgetLocation()) {
            $this->session->forget('location');
        }

        // Check if the location has already been set in the current session
        if ($this->session->has('location')) {
            // Set the current driver to the current session location
            $this->location = $this->session->get('location');
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

            $this->session->set('location', $this->location);
        }
    }

    /**
     * Sets the current IP property. If an IP address is supplied, it is validated
     * before it's set, otherwise it is grabbed automatically from the client.
     *
     * @param string $ip
     */
    private function setIp($ip = null)
    {
        // If an IP address is supplied, we'll validate it and
        // set it, otherwise we'll grab it automatically
        // from the client
        if ($ip) {
            $this->ip = $this->validateIp($ip);
        } else {
            $this->ip = $this->getClientIP();
        }
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
    private function validateIp($ip)
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
    private function getLocationFromFallback()
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
        $message = sprintf('No Location drivers are available.'
            .' Did you forget to set up your MaxMind GeoLite2-City.mmdb?');

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
    private function getClientIP()
    {
        if ($this->localHostTesting()) {
            return $this->getLocalHostTestingIp();
        } else {
            if (getenv('HTTP_CLIENT_IP')) {
                $ipaddress = getenv('HTTP_CLIENT_IP');
            } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
                $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('HTTP_X_FORWARDED')) {
                $ipaddress = getenv('HTTP_X_FORWARDED');
            } elseif (getenv('HTTP_FORWARDED_FOR')) {
                $ipaddress = getenv('HTTP_FORWARDED_FOR');
            } elseif (getenv('HTTP_FORWARDED')) {
                $ipaddress = getenv('HTTP_FORWARDED');
            } elseif (getenv('REMOTE_ADDR')) {
                $ipaddress = getenv('REMOTE_ADDR');
            } else {
                $ipaddress = filter_input('INPUT_SERVER', 'REMOTE_ADDR');
            }

            return $ipaddress;
        }
    }

    /**
     * Retrieves the config option for localhost testing.
     *
     * @return bool
     */
    private function localHostTesting()
    {
        return $this->config->get('location.localhost_testing');
    }

    /**
     * Retrieves the config option for forgetting the location from the current session.
     *
     * @return bool
     */
    private function localHostForgetLocation()
    {
        return $this->config->get('location.localhost_forget_location');
    }

    /**
     * Retrieves the config option for the localhost testing IP.
     *
     * @return string
     */
    private function getLocalHostTestingIp()
    {
        return $this->config->get('location.localhost_testing_ip');
    }

    /**
     * Retrieves the config option for select driver fallbacks.
     *
     * @return array
     */
    private function getDriverFallbackList()
    {
        return $this->config->get('location.selected_driver_fallbacks', []);
    }

    /**
     * Retrieves the config option for country codes.
     *
     * @return array
     */
    private function getCountryCodes()
    {
        return $this->config->get('location.country_codes');
    }

    /**
     * Retrieves the config option for the dropdown value.
     *
     * @return string
     */
    private function getDropdownValue()
    {
        return $this->config->get('location.dropdown_config.value');
    }

    /**
     * Retrieves the config option for the dropdown name.
     *
     * @return string
     */
    private function getDropdownName()
    {
        return $this->config->get('location.dropdown_config.name');
    }

    /**
     * Retrieves the config option for the driver namespace.
     *
     * @return mixed
     */
    private function getDriverNamespace()
    {
        return $this->config->get('location.driver_namespace');
    }

    /**
     * Returns the specified driver.
     *
     * @param string $driver
     *
     * @return \Stevebauman\Location\Drivers\DriverInterface
     *
     * @throws DriverDoesNotExistException
     */
    private function getDriver($driver)
    {
        $namespace = $this->getDriverNamespace();

        $driverStr = $namespace.$driver;

        if (class_exists($driverStr)) {
            return new $driverStr($this);
        } else {
            $message = sprintf('The driver: %s, does not exist. Please check the docs and'
                .' verify that it does.', $namespace.$driver);

            throw new DriverDoesNotExistException($message);
        }
    }
}
