<?php

namespace Stevebauman\Location\Drivers;

use Closure;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Fluent;
use Stevebauman\Location\Requestable;

abstract class HttpDriver extends Driver
{
    /**
     * The HTTP resolver callback.
     *
     * @var Closure|null
     */
    protected static $httpResolver;

    /**
     * Get the URL for the HTTP request.
     */
    abstract protected function url(string $ip): string;

    /**
     * Set the callback used to resolve a pending HTTP request.
     */
    public static function resolveHttpBy(Closure $callback): void
    {
        static::$httpResolver = $callback;
    }

    /**
     * Attempt to fetch and process the location data from the driver.
     */
    public function process(Requestable $request): Fluent|false
    {
        return rescue(fn () => new Fluent(
            $this->http()->get($this->url($request->ip()))->json()
        ), false);
    }

    /**
     * Create a new HTTP request.
     */
    protected function http(): PendingRequest
    {
        $callback = static::$httpResolver ?: fn ($http) => $http;

        return value($callback, Http::timeout(2)->connectTimeout(2));
    }
}
