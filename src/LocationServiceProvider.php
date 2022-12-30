<?php

namespace Stevebauman\Location;

use Illuminate\Support\ServiceProvider;
use Stevebauman\Location\Commands\Update;

class LocationServiceProvider extends ServiceProvider
{
    /**
     * Run boot operations.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(
            $config = __DIR__.'/../config/location.php', 'location'
        );

        if ($this->app->runningInConsole()) {
            $this->publishes([$config => config_path('location.php')]);
        }

        $this->commands(Update::class);
    }

    /**
     * Register bindings in the service container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(LocationManager::class);
    }
}
