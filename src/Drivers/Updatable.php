<?php

namespace Stevebauman\Location\Drivers;

use Illuminate\Console\Command;

interface Updatable
{
    /**
     * Update the driver.
     */
    public function update(Command $command): void;
}
