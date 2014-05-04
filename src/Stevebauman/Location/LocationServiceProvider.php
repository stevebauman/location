<?php namespace Stevebauman\Location;

use Illuminate\Support\ServiceProvider;

class LocationServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;
	
	
	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('stevebauman/location');
		
		include __DIR__.'/filters.php';
	}
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	 
	public function register()
	{

		$this->app['location'] = $this->app->share(function($app)
		{
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
		return array('location');
	}

}
