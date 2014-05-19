#Location

##Installation
Add Location to your `composer.json` file.

	"stevebauman/location": "dev-master"

Then run "composer update" on your project source.

Add the service provider in "app/config/app.php"

	'Stevebauman\Location\LocationServiceProvider',
	
Add the alias

	'Location'		=> 'Stevebauman\Location\Facades\Location',

Publish the config file:

	php artisan config:publish stevebauman/location

##Version 0.2

Added new function dropdown. This will populate a laravel select box with all countries in the config file:

	Location::dropdown()

You can set the default output of the dropdown box through the config file, or you can add the `value` and `name` to the dropdown function:

	Location::dropdown('country_name', 'country_name')

The output would be:
	
	`<option value="United States">United States</option>`

You can automatically select the users default location as well:

	Form::select('countries', Location::dropdown(), Location::get_country_code());

Version 0.3 will allow fallback drivers incase a location service is unavailable.

##Version 0.1

Unlike other location packages that require you installing database services, this package allows you to use external web servers to grab the users current location based on their IP address. This package is also able to use MaxMind services for retrieving location information.

Your server must support `file_get_contents()` for drivers FreeGeoIp and GeoPlugin. You can use the MaxMind driver for grabbing location through local database (by downloading <a href="http://dev.maxmind.com/geoip/geoip2/geolite2/#Downloads">GeoLite2 City</a> and placing it in your project source: `app/database/maxmind/GeoLite2-City.mmdb`), or you can use their web services through the config file.

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
	`city_name`,
	`latitude`,
	`longitude`

Getting a user location field:

	Location::get_country_code(); 
	Location::get_country_name();

Getting entire user location array (will return fields above for depending on the driver):

	Location::get();
	
Getting attribute as a prefix (useful for routing)

	Location::prefix_country_code(); //Would return 'us' rather than 'US'
	Location::prefix_country_name(); //Would return 'united-states' rather than 'United States'