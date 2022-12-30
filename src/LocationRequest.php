<?php

namespace Stevebauman\Location;

use Illuminate\Http\Request;

class LocationRequest extends Request implements Requestable
{
    /**
     * The IP address to resolve.
     */
    protected ?string $ip;

    /**
     * Set the IP address to resolve.
     */
    public function using(string $ip = null): static
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get the client IP address.
     */
    public function ip(): string
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
