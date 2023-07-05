<?php

namespace Stevebauman\Location\Drivers;

use Illuminate\Support\Fluent;
use Stevebauman\Location\Position;
use Stevebauman\Location\Request;

abstract class Driver
{
    /**
     * The fallback driver.
     */
    protected ?Driver $fallback = null;

    /**
     * Append a fallback driver to the end of the chain.
     */
    public function fallback(Driver $handler): void
    {
        if (is_null($this->fallback)) {
            $this->fallback = $handler;
        } else {
            $this->fallback->fallback($handler);
        }
    }

    /**
     * Get a position from the request.
     */
    public function get(Request $request): Position|false
    {
        $data = $this->process($request);

        $position = $this->makePosition();

        // Here we will ensure the location's data we received isn't empty.
        // Some IP location providers will return empty JSON. We want
        // to avoid this, so we can call the next fallback driver.
        if ($data instanceof Fluent && ! $this->isEmpty($data)) {
            $position = $this->hydrate($position, $data);

            $position->ip = $request->getIp();
            $position->driver = get_class($this);
        }

        if (! $position->isEmpty()) {
            return $position;
        }

        return $this->fallback ? $this->fallback->get($request) : false;
    }

    /**
     * Attempt to fetch and process the location data from the driver.
     */
    abstract protected function process(Request $request): Fluent|false;

    /**
     * Hydrate the Position object with the given location data.
     */
    abstract protected function hydrate(Position $position, Fluent $location): Position;

    /**
     * Create a new position instance.
     */
    protected function makePosition(): Position
    {
        return app(config('location.position', Position::class));
    }

    /**
     * Determine if the given fluent data is not empty.
     */
    protected function isEmpty(Fluent $data): bool
    {
        return empty(array_filter($data->getAttributes()));
    }
}
