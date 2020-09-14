<?php

namespace Stevebauman\Location;

use Illuminate\Support\ServiceProvider;

class LocationServiceProvider extends ServiceProvider
{
    /**
     * Run boot operations.
     *
     * @return void
     */
    public function boot()
    {
        $config = __DIR__.'/../config/config.php';

        if ($this->app->runningInConsole()) {
            $this->publishes([$config => config_path('location.php')]);
        }

        $this->mergeConfigFrom($config, 'location');
    }

    /**
     * Register the location binding.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('location', Location::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['location'];
    }
}
