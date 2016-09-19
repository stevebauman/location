<?php

namespace Stevebauman\Location\Drivers;

use Illuminate\Support\Fluent;
use Stevebauman\Location\Position;

abstract class Driver
{
    /**
     * The fallback driver.
     *
     * @var Driver
     */
    protected $fallback;

    /**
     * Append a fallback driver to the end of the chain.
     *
     * @param Driver $handler
     */
    public function fallback(Driver $handler)
    {
        if (is_null($this->fallback)) {
            $this->fallback = $handler;
        } else {
            $this->fallback->fallback($handler);
        }
    }

    /**
     * Handle the driver request.
     *
     * @param string $ip
     *
     * @return Position|bool
     */
    public function get($ip)
    {
        $location = $this->process($ip);

        if (!$location && $this->fallback) {
            $location = $this->fallback->get($ip);
        }

        if ($location instanceof Fluent) {
            $position = $this->hydrate(new Position(), $location);

            $position->driver = get_class($this);

            return $position;
        }

        return false;
    }

    /**
     * Returns the URL to use for querying the current driver.
     *
     * @return string
     */
    abstract protected function url();

    /**
     * Hydrates the position with the given location
     * instance using the drivers array map.
     *
     * @param Position $position
     * @param Fluent   $location
     *
     * @return \Stevebauman\Location\Position
     */
    abstract protected function hydrate(Position $position, Fluent $location);

    /**
     * Process the specified driver.
     *
     * @param string $ip
     *
     * @return Fluent|bool
     */
    abstract protected function process($ip);
}
