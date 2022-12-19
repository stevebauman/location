<?php

namespace Stevebauman\Location\Drivers;

use Illuminate\Console\Command;

interface Updatable
{
    /**
     * Update the driver.
     *
     * @param Command $command
     *
     * @return mixed
     */
    public function update(Command $command);
}
