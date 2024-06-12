<?php

namespace Stevebauman\Location\Drivers;

use Closure;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Fluent;
use Stevebauman\Location\Request;

abstract class HttpDriver extends Driver
{
    /**
     * The HTTP resolver callback.
     */
    protected static ?Closure $httpResolver = null;

    /**
     * Get the URL for the HTTP request.
     */
    abstract public function url(string $ip): string;

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
    public function process(Request $request): Fluent|false
    {
        return rescue(fn () => new Fluent(
            $this->http()
                ->throw()
                ->acceptJson()
                ->get($this->url($request->getIp()))
                ->json()
        ), false, false);
    }

    /**
     * Create a new HTTP request.
     */
    protected function http(): PendingRequest
    {
        $callback = static::$httpResolver ?: fn ($http) => $http;

        return value($callback, Http::withOptions(
            config('location.http', [
                'timeout' => 3,
                'connect_timeout' => 3,
            ])
        ));
    }
}
