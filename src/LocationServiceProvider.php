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
     * Run boot operations.
     */
    public function boot()
    {
        $this->app->bind('location', function($app) {
            return new Location($app['session'], $app['config']);
        });
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $config = __DIR__.'/Config/config.php';

        if (class_exists('Illuminate\Foundation\Application', false)) {
            $this->publishes([
                $config => config_path('location.php'),
            ], 'config');
        }

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
