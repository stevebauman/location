<?php

namespace Stevebauman\Location\Commands;

use Illuminate\Console\Command;
use Stevebauman\Location\Drivers\Updatable;
use Stevebauman\Location\Facades\Location;

class Update extends Command
{
    /**
     * The signature of the console command.
     *
     * @var string
     */
    protected $signature = 'location:update';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Update the configured drivers.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        foreach (Location::drivers() as $driver) {
            if ($driver instanceof Updatable) {
                $this->line(sprintf('Updating driver [%s]...', $driver::class));

                $driver->update($this);

                $this->line(sprintf('Successfully updated driver [%s].', $driver::class));

                $this->newLine();
            }
        }

        $this->line('All configured drivers have been updated.');

        return static::SUCCESS;
    }
}
