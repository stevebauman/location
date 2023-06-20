<?php

namespace Stevebauman\Location;

use Stevebauman\Location\Drivers\Driver;
use Stevebauman\Location\Exceptions\DriverDoesNotExistException;

class LocationManager
{
    /**
     * The current driver.
     */
    protected Driver $driver;

    /**
     * The loaded drivers.
     *
     * @var Driver[]
     */
    protected array $loaded = [];

    /**
     * The request resolver callback.
     *
     * @var callable
     */
    protected $requestResolver;

    /**
     * Constructor.
     *
     * @throws DriverDoesNotExistException
     */
    public function __construct()
    {
        $this->setDefaultDriver();

        $this->requestResolver = fn () => LocationRequest::createFrom(request());
    }

    /**
     * Set the current driver to use.
     */
    public function setDriver(Driver $driver): static
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * Set the default location driver to use.
     *
     * @throws DriverDoesNotExistException
     */
    public function setDefaultDriver(): static
    {
        $this->loaded[] = $driver = $this->getDriver($this->getDefaultDriver());

        foreach ($this->getDriverFallbacks() as $fallback) {
            $driver->fallback($this->loaded[] = $this->getDriver($fallback));
        }

        return $this->setDriver($driver);
    }

    /**
     * Attempt to retrieve the location of the user.
     */
    public function get(string $ip = null): Position|bool
    {
        if ($location = $this->driver->get($this->request()->setIp($ip))) {
            return $location;
        }

        return false;
    }

    /**
     * Get the HTTP request.
     */
    protected function request(): Request
    {
        return call_user_func($this->requestResolver);
    }

    /**
     * Set the request resolver callback.
     */
    public function resolveRequestUsing(callable $callback): void
    {
        $this->requestResolver = $callback;
    }

    /**
     * Get the loaded driver instances.
     *
     * @return Driver[]
     */
    public function drivers(): array
    {
        return $this->loaded;
    }

    /**
     * Get the fallback location drivers to use.
     */
    protected function getDriverFallbacks(): array
    {
        return config('location.fallbacks', []);
    }

    /**
     * Get the default location driver.
     */
    protected function getDefaultDriver(): string
    {
        return config('location.driver');
    }

    /**
     * Attempt to create the location driver.
     *
     * @throws DriverDoesNotExistException
     */
    protected function getDriver(string $driver): Driver
    {
        if (! class_exists($driver)) {
            throw DriverDoesNotExistException::forDriver($driver);
        }

        return app($driver);
    }
}
