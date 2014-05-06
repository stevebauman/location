#Location

##Installation
Add Location to your `composer.json` file.

	"stevebauman/location": "dev-master"

Then run "composer update" on your project source.

Add the service provider in "app/config/app.php"

	'Stevebauman\Location\LocationServiceProvider',
	
Add the alias

	'Location'		=> 'Stevebauman\Location\Facades\Location',

Version 0.1
========


Simple usage

	"Location::get_country_code()"
