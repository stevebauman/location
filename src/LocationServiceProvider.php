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
