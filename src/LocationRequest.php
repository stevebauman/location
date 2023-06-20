<?php

namespace Stevebauman\Location;

use Illuminate\Http\Request as IlluminateRequest;

class LocationRequest extends IlluminateRequest implements Request
{
    /**
     * The IP address to resolve.
     */
    protected ?string $ip;

    /**
     * Get the client IP address.
     */
    public function getIp(): string
    {
        if ($this->ip) {
            return $this->ip;
        }

        if ($this->isTesting()) {
            return $this->getTestingIp();
        }

        return parent::ip();
    }

    /**
     * Set the IP address to resolve.
     */
    public function setIp(string $ip = null): static
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get a header from the request.
     */
    public function getHeader(string $key = null, string|array $default = null): string|array|null
    {
        return parent::header($key, $default);
    }

    /**
     * Determine if location testing is enabled.
     */
    protected function isTesting(): bool
    {
        return config('location.testing.enabled', true);
    }

    /**
     * Get the testing IP address.
     */
    protected function getTestingIp(): string
    {
        return config('location.testing.ip', '66.102.0.0');
    }
}
