<?php

namespace Stevebauman\Location\Drivers;

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
     * @return mixed|bool
     */
    public function get($ip)
    {
        $location = $this->process($ip);

        if (!$location && !is_null($this->fallback)) {
            $location = $this->fallback->get($ip);
        }

        return $location;
    }

    /**
     * Process the specified driver.
     *
     * @param string $ip
     *
     * @return \Stevebauman\Location\Objects\Location|bool
     */
    abstract protected function process($ip);
}
