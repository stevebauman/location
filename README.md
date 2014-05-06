#Location

##Installation
Add Location to your `composer.json` file.

	"stevebauman/location": "dev-master"

Then run "composer update" on your project source.

Add the service provider in "app/config/app.php"

	'Stevebauman\Location\LocationServiceProvider',
	
Add the alias

	'Location'		=> 'Stevebauman\Location\Facades\Location',

##Version 0.1

Unlike other location packages that require you installing database services such as MaxMind, this package (so far) relies on external web servers to grab the users current location.

Your server must support `file_get_contents()` for the current drivers to work.

###Drivers

Available drivers at the moment are FreeGeoIp and GeoPlugin. Default selected driver is FreeGeoIp.


Fields available for FreeGeoIp:

	`country_code`,
	`country_name`,
	`region_code`,
	`city`,

Fields available for GeoPlugin:

	`country_code`,
	`country_name`,
	`region_name`,
	`city`

Getting a user location field:

	Location::get_country_code(), 
	Location::get_country_name()

Getting entire user location array (will return fields above for depending on the driver):

	Location::get()
	
