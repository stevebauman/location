<?php

namespace Stevebauman\Location;

interface Requestable
{
    /**
     * Set the IP address to resolve.
     */
    public function using(string $ip = null): static;

    /**
     * Get the client IP address.
     */
    public function ip(): string;
}
