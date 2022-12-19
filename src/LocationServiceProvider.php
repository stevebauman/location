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
        $config = __DIR__.'/../config/location.php';

        if ($this->app->runningInConsole()) {
            $this->publishes([$config => config_path('location.php')]);
        }

        $this->mergeConfigFrom($config, 'location');
    }

    /**
     * Register bindings in the service container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('location', function ($app) {
            return new LocationManager($app['config']);
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
