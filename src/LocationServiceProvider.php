<?php

namespace Stevebauman\Location;

use Illuminate\Support\ServiceProvider;
use Stevebauman\Location\Commands\Update;
use Stevebauman\Location\Drivers\LocalMaxMindDatabase;

class LocationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the service provider.
     */
    public function boot(): void
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
     */
    public function register(): void
    {
        $this->app->singleton(LocationManager::class);

        $this->app->bind(LocalMaxMindDatabase::class, function ($app) {
            $config = $app['config'];
            $disk = $config->get('location.maxmind.storage.disk');

            return new LocalMaxMindDatabase(
                path: $config->get('location.maxmind.local.path', $app->databasePath('maxmind/GeoLite2-City.mmdb')),
                disk: $disk ? $app['filesystem']->disk($disk) : null,
                storagePath: $config->get('location.maxmind.storage.path', 'maxmind/GeoLite2-City.mmdb'),
                ttl: (int) $config->get('location.maxmind.storage.ttl', 3600),
            );
        });
    }
}
