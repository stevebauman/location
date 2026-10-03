<?php

namespace Stevebauman\Location\Drivers;

use Closure;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Fluent;
use Stevebauman\Location\Deadline;
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
        if (Deadline::remaining() === 0.0) {
            return false;
        }

        return rescue(function () use ($request) {
            $response = $this->http()->acceptJson()->get(
                $this->url($request->getIp())
            );

            throw_if($response->failed());

            return new Fluent($response->json());
        }, false, false);
    }

    /**
     * Create a new HTTP request.
     */
    protected function http(): PendingRequest
    {
        $callback = static::$httpResolver ?: fn ($http) => $http;

        return value($callback, Http::withOptions($this->options()));
    }

    /**
     * Get the options to use for the HTTP request.
     */
    protected function options(): array
    {
        $options = config('location.http', [
            'timeout' => 3,
            'connect_timeout' => 3,
        ]);

        if (is_null($remaining = Deadline::remaining())) {
            return $options;
        }

        // No request may outlive what's left of the lookup's budget. A
        // missing or zero timeout is unlimited as far as Guzzle is
        // concerned, so those get the whole remaining budget.
        foreach (['timeout', 'connect_timeout'] as $option) {
            $options[$option] = min(($options[$option] ?? 0) ?: INF, $remaining);
        }

        return $options;
    }
}
