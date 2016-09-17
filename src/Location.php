<?php

namespace Stevebauman\Location;

use Stevebauman\Location\Drivers\Driver;
use Stevebauman\Location\Exceptions\DriverDoesNotExistException;

class Location
{
    /**
     * The session key.
     *
     * @var string
     */
    protected $key = 'location';

    /**
     * The current driver.
     *
     * @var Driver
     */
    protected $driver;

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
     * Sets the location session key.
     *
     * @param string $key
     *
     * @return Location
     */
    public function setSessionKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Returns the driver's location object. If a field is specified it will
     * return the matching location objects variable.
     *
     * @param string $ip
     *
     * @return \Stevebauman\Location\Position|bool
     */
    public function get($ip = '')
    {
        if (session()->has($this->key)) {
            return session($this->key);
        }

        if ($location = $this->driver->get($ip ?: $this->getClientIP())) {
            // We'll store the location inside of our session
            // so it isn't retrieved on the next request.
            session([$this->key => $location]);

            return $location;
        }

        return false;
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
        return config('location.testing.enabled', true);
    }

    /**
     * Retrieves the config option for the localhost testing IP.
     *
     * @return string
     */
    protected function getLocalHostTestingIp()
    {
        return config('location.testing.ip', '66.102.0.0');
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
     * @param string $driver
     *
     * @return Driver
     *
     * @throws DriverDoesNotExistException
     */
    protected function getDriver($driver)
    {
        if (class_exists($driver)) {
            return new $driver();
        }

        throw new DriverDoesNotExistException("The driver [{$driver}] does not exist.");
    }
}
