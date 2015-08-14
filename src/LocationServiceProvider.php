<?php

namespace Stevebauman\Location;

use Illuminate\Support\ServiceProvider;

class LocationServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     */
    public function register()
    {
        $config = __DIR__.'/Config/config.php';

        $this->mergeConfigFrom($config, 'location');

        $this->publishes([
            $config => config_path('location.php'),
        ], 'config');

        $this->app->bind('location', function($app) {
            return new Location();
        });
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
