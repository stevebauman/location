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
        $config = __DIR__.'/Config/config.php';

        $this->publishes([
            $config => config_path('location.php'),
        ]);

        $this->mergeConfigFrom($config, 'location');
    }

    /**
     * Register the location binding.
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
