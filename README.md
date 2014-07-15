#Location

![alt text](https://travis-ci.org/stevebauman/location.svg?branch=master)

##Installation
Add Location to your `composer.json` file.

	"stevebauman/location": "dev-master"

Then run `composer update` on your project source.

Add the service provider in `app/config/app.php`

	'Stevebauman\Location\LocationServiceProvider',
	
Add the alias

	'Location'		=> 'Stevebauman\Location\Facades\Location',

Publish the config file:

	php artisan config:publish stevebauman/location

## Version 0.5

* Updated for Laravel 4.2 Support

* `Location::get()` now returns in an Eloquent Collection so you can do handy things with the results such as: `Location::get()->toJson(); //Or even Location::get()->toArray()`
	
* Removed trash, cleaned up a little

All fields are still accessible the same way as shown below, putting the result in an Eloquent Collection just allows for some nice transformation functions.

## Version 0.4

Added ability to fallback to other drivers if querying a driver is not available.

You'll need to either run `php artisan config:publish stevebauman/location` again to publish the new config changes, or paste in this into the config file:

	/** Selected Driver Fallbacks:
	*		The drivers you want to use to grab location if the selected driver is unavailable (in order)
	**/
	'selected_driver_fallbacks' => array(
		'GeoPlugin', 	//Used after 'selected_driver' fails
		'MaxMind' 		//Used after above driver fails
	),

## Version 0.3

Added new function `is()`. This will return true/false if this user's location equals the country code/country name of the inputted country. For example (using country code):

	if(Location::is_US()){
		echo "You're located in the US!";
	}
	

or you can input the country name directly into the function:

	if(Location::is('Canada')){
		echo "You're located in Canada!";
	}

This function is completely case insensitive so your able to use:

	Location::is_us()
	Location::is_Us()
	Location::is('canada')
	Location::is('united states')

##Version 0.2

Added new function `dropdown()`. This will populate a laravel select box with all countries in the config file:

	Location::dropdown()

You can set the default output of the dropdown box through the config file, or you can add the `value` and `name` to the dropdown function:

	Location::dropdown('country_name', 'country_name')

The output would be:
	
	`<option value="United States">United States</option>`

You can automatically select the users default location as well:

	Form::select('countries', Location::dropdown(), Location::get_country_code());


##Version 0.1

Unlike other location packages that require you installing database services, this package allows you to use external web servers to grab the users current location based on their IP address. This package is also able to use MaxMind services for retrieving location information.

Your server must support `file_get_contents()` for drivers FreeGeoIp and GeoPlugin. You can use the MaxMind driver for grabbing location through local database (by downloading <a href="http://dev.maxmind.com/geoip/geoip2/geolite2/#Downloads">GeoLite2 City</a> and placing it in your project source: `app/database/maxmind/GeoLite2-City.mmdb` - you will have to create the maxmind directory), or you can use their web services through the config file.

Also, by default, once a location is grabbed from the user, it is set into a session key named 'location'. You can use `Session:get('location')` to retrieve their location from when it was first taken.

###Drivers

Available drivers at the moment are FreeGeoIp, GeoPlugin, MaxMind. Default selected driver is FreeGeoIp.


Fields available for FreeGeoIp:

	`country_code`,
	`country_name`,
	`region_code`,
	`city_name`,

Fields available for GeoPlugin:

	`country_code`,
	`country_name`,
	`region_name`,
	`city_name`
	
Fields available for MaxMind:

	`country_code`,
	`country_name`,
	`city_name`,
	`state_code`,
	`latitude`,
	`longitude`

Getting a user location field:

	Location::get_country_code(); 
	Location::get_country_name();
	Location::get_city_name();
	
Getting entire user location array (will return fields above for depending on the driver):

	Location::get();
	
Getting attribute as a prefix (useful for routing)

	Location::prefix_country_code(); //Would return 'us' rather than 'US'
	Location::prefix_country_name(); //Would return 'united-states' rather than 'United States'