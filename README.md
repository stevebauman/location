#Location

![alt text](https://travis-ci.org/stevebauman/location.svg?branch=master)

##Beta Users

Switch `"location" : "dev-master"` to `"location" : "0.5"` to use previous
package while you switch your code around for the new one.

New package was rebuilt from the ground up and config file has large changes.

##Description
Unlike other location packages that require you installing database services, this package allows you to use external web servers to grab the users current location based on their IP address. This package is also able to use MaxMind services for retrieving location information.

Your server must support `file_get_contents()` for drivers FreeGeoIp and GeoPlugin. You can use the MaxMind driver for grabbing location through local database (by downloading <a href="http://dev.maxmind.com/geoip/geoip2/geolite2/#Downloads">GeoLite2 City</a> and placing it in your project source: `app/database/maxmind/GeoLite2-City.mmdb` - you will have to create the maxmind directory), or you can use their web services through the config file.

Also, by default, once a location is grabbed from the user, it is set into a session key named 'location'. The package will automatically
use the session object once a location has been set so there will be minimal requests. You can use `Session:get('location')` to retrieve the location object manually from when it was first taken if you wish.
This can be turned off in the config file if you'd like to grab the location from a provider on every request (not recommended).

##Installation
Add Location to your `composer.json` file.

	"stevebauman/location": "1.*"

Then run `composer update` on your project source.

Add the service provider in `app/config/app.php`

	'Stevebauman\Location\LocationServiceProvider',
	
Add the alias

	'Location' => 'Stevebauman\Location\Facades\Location',

Publish the config file:

	php artisan config:publish stevebauman/location

##Usage

####Getting a user location:

    Location::get();

This will return a Location object, where you can retrieve each field with:

    Location::get()->countryCode;

or

    $location = Location::get();
    
    echo $location->countryCode;
    echo $location->countryName;

####Checking a user location

Using the `is($location = '')` function will return true/false depending if
one of the location fields equals the inputted string. For example:

    if(Location::is('US')) {
        echo 'Your located in the United States!';
    }

    if(Location::is('California')) {
        echo 'Your located in California!';
    }

####Lists function for Laravel Select Form (or Dropdown for Beta users)
    
    Form::select('countries', Location::get()->countryCode, Location::lists());
    
    Form::select('countries', Location::get()->countryCode, Location::dropdown());

This returns an array of all the countries inside the config file.

####Fallback drivers

In the config file, you can specify as many fallback drivers as you wish. It's recommended to install
the MaxMind database `.mmdb` file so your always retrieving some generic location information from the user.

If an exception occurs trying to grab a driver (such as a 404 error if the providers API changes), it will automatically
use the next driver in line.

##Drivers

####Available Drivers

Available drivers at the moment are FreeGeoIp, GeoPlugin, MaxMind. Default selected driver is FreeGeoIp.


Fields available for FreeGeoIp:

	`country_code`,
 	`country_name`,
 	`region_code`,
 	`region_name`,
 	`city_name`,
 	`zipcode`,
	`latitude`,
 	`longitude`,
 	`metro_code`,
 	`area_code`,
 	
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

####Creating your own drivers

To create your own location driver, change the driver namespace in the config file (the 'driver_namespace' key) to your own namespace.

The class must implement the public method `get($ip)`. IP being the users IP address being inputted.

You must then create a `Stevebauman\Location\Objects\Location` object, set it's location variables, and return the location. Here's an example:
    
    use Stevebauman\Location\Objects\Location;

    class MyDriver {
        
        public function get($ip)
        {
            $location = new Location;

            //Retrieve a location in some way
            
            $location->countryCode = 'Country Code';

            $location->countryName = 'Country Name';
            
            return $location;
        }

    }

Then in the config file:

    'driver_namespace' => 'MyApp\Location\Drivers\\'