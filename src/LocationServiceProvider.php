<?php

namespace Stevebauman\Location;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class LocationServiceProvider extends ServiceProvider
{
    /**
     * Run boot operations.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->isLumen()) {
            return;
        }

        $config = __DIR__.'/../config/location.php';

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
        $this->app->singleton('location', function ($app) {
            return new Location($app['config']);
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

    /**
     * Determine if the current application is Lumen.
     *
     * @return bool
     */
    protected function isLumen()
    {
        return Str::contains($this->app->version(), 'Lumen');
    }
}
