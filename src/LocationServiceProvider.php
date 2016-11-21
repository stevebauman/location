<?php

namespace Stevebauman\Location;

use Illuminate\Support\ServiceProvider;

class LocationServiceProvider extends ServiceProvider
{
    /**
     * Run boot operations.
     */
    public function boot()
    {
        $this->app->bind('location', Location::class);
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $config = __DIR__.'/Config/config.php';

        $this->publishes([
            $config => config_path('location.php'),
        ], 'config');

        $this->mergeConfigFrom($config, 'location');
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
