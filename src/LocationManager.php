<?php

namespace Stevebauman\Location;

use Illuminate\Support\Traits\Macroable;
use Stevebauman\Location\Drivers\Driver;
use Stevebauman\Location\Exceptions\DriverDoesNotExistException;

class LocationManager
{
    use Macroable;

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
    public function get(?string $ip = null): Position|bool
    {
        $request = $this->request()->setIp($ip);

        if (! $this->cacheEnabled()) {
            return $this->driver->get($request);
        }

        $key = $this->cacheKey($request->getIp());

        $cache = cache()->store($this->cacheStore());

        /**
         * The value can be:
         *
         * - Position instance (cache hit)
         * - false (cache hit for failed lookup, if ignore_failed is false)
         * - null (cache miss)
         *
         * @var Position|false|null $position
         */
        $position = $cache->get($key);

        if (! is_null($position)) {
            if ($position instanceof Position) {
                $position->cached = true;
            }

            return $position;
        }

        $position = $this->driver->get($request);

        $ignoreFailed = config('location.cache.ignore_failed', true);

        if (! $ignoreFailed || ($position !== false && ! $position->isEmpty())) {
            $cache->put($key, $position, $this->cacheTtl());
        }

        return $position;
    }

    /**
     * Determine whether the cache is enabled.
     */
    protected function cacheEnabled(): bool
    {
        return (bool) config('location.cache.enabled', false);
    }

    /**
     * Get the cache store to use.
     */
    protected function cacheStore(): ?string
    {
        return config('location.cache.store');
    }

    /**
     * Get the cache TTL.
     */
    protected function cacheTtl(): int
    {
        return (int) config('location.cache.ttl', 3600);
    }

    /**
     * Get the cache key for an IP address.
     */
    protected function cacheKey(string $ip): string
    {
        return config('location.cache.prefix', 'location') . '_' . $ip;
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
