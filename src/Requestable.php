<?php

namespace Stevebauman\Location;

interface Requestable
{
    /**
     * Get the client IP address.
     */
    public function ip(): string;

    /**
     * Set the IP address to resolve.
     */
    public function using(string $ip = null): static;
}
