<?php

namespace Stevebauman\Location;

use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;

class LocationFake
{
    use ForwardsCalls;

    /**
     * Constructor.
     */
    public function __construct(
        protected LocationManager $manager,
        protected array $requests = [],
    ) {
    }

    /**
     * Forward missing method calls to the location manager.
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->forwardCallTo($this->manager, $method, $parameters);
    }

    /**
     * Get a fake location instance.
     */
    public function get(?string $ip = null): Position|bool
    {
        $ip ??= '127.0.0.1';

        foreach ($this->requests as $ipRequest => $position) {
            if (Str::is($ipRequest, $ip)) {
                $position->ip = $ip;

                return $position;
            }
        }

        return false;
    }
}
