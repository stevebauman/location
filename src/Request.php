<?php

namespace Stevebauman\Location;

interface Request
{
    /**
     * Get the client IP address.
     */
    public function getIp(): string;

    /**
     * Set the IP address to resolve.
     */
    public function setIp(string $ip = null): static;

    /**
     * Get a header from the request.
     */
    public function getHeader(string $key = null, string|array $default = null): string|array|null;
}
