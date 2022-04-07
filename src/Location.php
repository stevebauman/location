<?php

namespace Stevebauman\Location;

use Illuminate\Contracts\Config\Repository;
use Stevebauman\Location\Drivers\Driver;
use Stevebauman\Location\Exceptions\DriverDoesNotExistException;

class Location
{
    /**
     * The current driver.
     *
     * @var Driver
     */
    protected $driver;

    /**
     * The application configuration.
     *
     * @var Repository
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param Repository $config
     *
     * @throws DriverDoesNotExistException
     */
    public function __construct(Repository $config)
    {
        $this->config = $config;

        $this->setDefaultDriver();
    }

    /**
     * Set the current driver to use.
     *
     * @param Driver $driver
     */
    public function setDriver(Driver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Set the default location driver to use.
     *
     * @throws DriverDoesNotExistException
     */
    public function setDefaultDriver()
    {
        $driver = $this->getDriver($this->getDefaultDriver());

        foreach ($this->getDriverFallbacks() as $fallback) {
            $driver->fallback($this->getDriver($fallback));
        }

        $this->setDriver($driver);
    }

    /**
     * Attempt to retrieve the location of the user.
     *
     * @param string|null $ip
     *
     * @return \Stevebauman\Location\Position|bool
     */
    public function get($ip = null)
    {
        if ($location = $this->driver->get($ip ?: $this->getClientIP())) {
            return $location;
        }

        return false;
    }

    /**
     * Get the client IP address.
     *
     * @return string
     */
    protected function getClientIP()
    {
        return $this->localHostTesting()
            ? $this->getLocalHostTestingIp()
            : request()->ip();
    }

    /**
     * Determine if testing is enabled.
     *
     * @return bool
     */
    protected function localHostTesting()
    {
        return $this->config->get('location.testing.enabled', true);
    }

    /**
     * Get the testing IP address.
     *
     * @return string
     */
    protected function getLocalHostTestingIp()
    {
        return $this->config->get('location.testing.ip', '66.102.0.0');
    }

    /**
     * Get the fallback location drivers to use.
     *
     * @return array
     */
    protected function getDriverFallbacks()
    {
        return $this->config->get('location.fallbacks', []);
    }

    /**
     * Get the default location driver.
     *
     * @return string
     */
    protected function getDefaultDriver()
    {
        return $this->config->get('location.driver');
    }

    /**
     * Attempt to create the location driver.
     *
     * @param string $driver
     *
     * @return Driver
     *
     * @throws DriverDoesNotExistException
     */
    protected function getDriver($driver)
    {
        if (! class_exists($driver)) {
            throw DriverDoesNotExistException::forDriver($driver);
        }

        return app()->make($driver);
    }
}
