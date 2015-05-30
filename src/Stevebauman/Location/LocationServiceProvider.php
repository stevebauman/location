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
        /*
         * If the package method exists, we're using Laravel 4,
         * if not then we're definitely on laravel 5
         */
        if (method_exists($this, 'package')) {
            $this->package('stevebauman/location');
        } else {
            $this->publishes([
                __DIR__.'/../../config/config.php' => config_path('location.php'),
            ], 'config');
        }

        $this->app['location'] = $this->app->share(function ($app) {
            return new Location($app, $app['config'], $app['session']);
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
