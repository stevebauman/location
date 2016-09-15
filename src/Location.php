<?php

namespace Stevebauman\Location;

use Stevebauman\Location\Drivers\Driver;
use Stevebauman\Location\Exceptions\InvalidIpException;
use Stevebauman\Location\Exceptions\LocationFieldDoesNotExistException;
use Stevebauman\Location\Exceptions\DriverDoesNotExistException;
use Stevebauman\Location\Exceptions\NoDriverAvailableException;

class Location
{
    /**
     * Stores the current driver.
     *
     * @var Driver
     */
    protected $driver;

    /**
     * Stores the current location object
     *
     * @var \Stevebauman\Location\Objects\Location
     */
    protected $location;

    /**
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
     * @param Driver $driver
     */
    public function setDriver(Driver $driver)
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
        // Retrieve the default driver.
        $driver = $this->getDriver($this->getDefaultDriver());

        foreach($this->getDriverFallbacks() as $fallback) {
            // We'll add each fallback to our responsibility chain.
            $driver->fallback($this->getDriver($fallback));
        }

        // Finally, set the driver.
        $this->setDriver($driver);
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
        $location = $this->find($ip);

        if ($field) {
            if (property_exists($location, $field)) {
                return $location->{$field};
            } else {
                $message = sprintf('Location field: %s does not exist. Please check the docs'
                    .' to verify which fields are available.', $field);

                throw new LocationFieldDoesNotExistException($message);
            }
        }

        return $location;
    }

    /**
     * Sets the location property to the drivers returned location object.
     *
     * @param string $ip
     *
     * @return Location
     */
    protected function find($ip = '')
    {
        $key = 'location';

        if ($this->localHostForgetLocation()) {
            session()->forget($key);
        }

        if (session()->has($key)) {
            return session($key);
        }

        $location = $this->driver->get($ip ?: $this->getClientIP());

        // We'll store the location inside of our session
        // so it isn't retrieved on the next request.
        session([$key => $location]);

        return $location;
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
    protected function getDriverFallbacks()
    {
        return config('location.fallbacks', []);
    }

    /**
     * Returns the selected driver
     *
     * @return \Illuminate\Support\Facades\Config
     */
    protected function getDefaultDriver()
    {
        return config('location.driver');
    }

    /**
     * Returns the specified driver.
     *
     * @param string $name
     *
     * @return Driver
     *
     * @throws DriverDoesNotExistException
     */
    protected function getDriver($name)
    {
        $driver = config("location.drivers.$name.class");

        if (class_exists($driver)) {
            return new $driver();
        }

        throw new DriverDoesNotExistException("The driver [{$driver}] does not exist.");
    }
}
