<?php

namespace Stevebauman\Location\Drivers;

use Closure;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Fluent;
use Stevebauman\Location\Position;

abstract class Driver
{
    /**
     * The fallback driver.
     *
     * @var Driver|null
     */
    protected $fallback;

    /**
     * The HTTP resolver callback.
     *
     * @var Closure|null
     */
    protected static $httpResolver;

    /**
     * Set the callback used to resolve a pending HTTP request.
     */
    public static function resolveHttpBy(Closure $callback): void
    {
        static::$httpResolver = $callback;
    }

    /**
     * Append a fallback driver to the end of the chain.
     *
     * @param Driver $handler
     */
    public function fallback(Driver $handler)
    {
        if (is_null($this->fallback)) {
            $this->fallback = $handler;
        } else {
            $this->fallback->fallback($handler);
        }
    }

    /**
     * Get a position from the IP address.
     */
    public function get(string $ip): Position|false
    {
        $data = $this->process($ip);

        $position = $this->makePosition();

        // Here we will ensure the locations data we received isn't empty.
        // Some IP location providers will return empty JSON. We want
        // to avoid this, so we can call the next fallback driver.
        if ($data instanceof Fluent && $this->fluentDataIsNotEmpty($data)) {
            $position = $this->hydrate($position, $data);

            $position->ip = $ip;
            $position->driver = get_class($this);
        }

        if (! $position->isEmpty()) {
            return $position;
        }

        return $this->fallback ? $this->fallback->get($ip) : false;
    }

    /**
     * Create a new HTTP request.
     */
    protected function http(): PendingRequest
    {
        $callback = static::$httpResolver ?: fn ($http) => $http;

        return value($callback, Http::timeout(2)->connectTimeout(2));
    }

    /**
     * Hydrate the Position object with the given location data.
     */
    abstract protected function hydrate(Position $position, Fluent $location): Position;

    /**
     * Attempt to fetch and process the location data from the driver.
     */
    protected function process(string $ip): Fluent|false
    {
        return rescue(fn () => new Fluent(
            $this->http()->get($this->url($ip))->json()
        ), false);
    }

    /**
     * Get the URL to use for retrieving the IP's location.
     */
    protected function url(string $ip): string
    {
        return '';
    }

    /**
     * Create a new position instance.
     */
    protected function makePosition(): Position
    {
        return app(config('location.position', Position::class));
    }

    /**
     * Determine if the given fluent data is not empty.
     *
     * @param Fluent $data
     *
     * @return bool
     */
    protected function fluentDataIsNotEmpty(Fluent $data): bool
    {
        return ! empty(array_filter($data->getAttributes()));
    }
}
