![Location Banner]
(https://raw.githubusercontent.com/stevebauman/location/master/location-banner.jpg)

[![Travis CI](https://img.shields.io/travis/stevebauman/location.svg?style=flat-square)](https://travis-ci.org/stevebauman/location)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/stevebauman/location.svg?style=flat-square)](https://scrutinizer-ci.com/g/stevebauman/location/?branch=master)
[![Latest Stable Version](https://img.shields.io/packagist/v/stevebauman/location.svg?style=flat-square)](https://packagist.org/packages/stevebauman/location)
[![Total Downloads](https://img.shields.io/packagist/dt/stevebauman/location.svg?style=flat-square)](https://packagist.org/packages/stevebauman/location)
[![License](https://img.shields.io/packagist/l/stevebauman/location.svg?style=flat-square)](https://packagist.org/packages/stevebauman/location)

##Description
Unlike other location packages that require you installing database services, this package allows you to use external web servers to grab the users current location based on their IP address.
This means you don't have to consistently update a local database to keep your results current. This package is also able to use MaxMind services for retrieving location information.

Your server must support `file_get_contents()` for drivers Telize, IpInfo, FreeGeoIp and GeoPlugin. You can use the MaxMind driver for grabbing location through local database (by downloading <a href="http://dev.maxmind.com/geoip/geoip2/geolite2/#Downloads">GeoLite2 City</a> and placing it in your project source: `app/database/maxmind/GeoLite2-City.mmdb` - you will have to create the maxmind directory), or you can use their web services through the config file.

Also, by default, once a location is grabbed from the user, it is set into a session key named 'location'. The package will automatically
use the session object once a location has been set so there will be minimal requests. You can use `Session:get('location')` to retrieve the location object manually from when it was first taken if you wish.
This can be turned off in the config file if you'd like to grab the location from a provider on every request (not recommended).

##Installation (Laravel 4)
Add Location to your `composer.json` file:

	"stevebauman/location": "1.1.*"

Then run `composer update` on your project source.

Add the service provider in `app/config/app.php` file:

	'Stevebauman\Location\LocationServiceProvider',
	
Add the alias in `app/config/app.php` file:

	'Location' => 'Stevebauman\Location\Facades\Location',

Publish the config file:

	php artisan config:publish stevebauman/location

##Installation (Laravel 5)
Add Location to your `composer.json` file:

	"stevebauman/location": "1.1.*"

Then run `composer update` on your project source.

Add the service provider in `config/app.php`:

	'Stevebauman\Location\LocationServiceProvider',

Add the alias in your `config/app.php` file:

	'Location' => 'Stevebauman\Location\Facades\Location',

Publish the config file (mandatory):

    php artisan vendor:publish

##Changelog
    
    1.1.8 - May 8th, 2015 - Rolled back layout change, PHP-CS fixed all files
    1.1.7 - May 6th, 2015 - Code reformatting tweaks
    1.1.6 - April 30th, 2015 - Small exception fix
    1.1.5 - April 30th, 2015 - Code formatting and doc tweaks
    1.1.4 - April 30th, 2015 - Updated configuration path for PSR changes
    1.1.3 - April 24th, 2015 - PSR compatibility update, more updates to come
    1.1.2 - March 21st, 2015 - Added tests
    1.1.1 - March 15th, 2015 - Fixed MaxMind local database path for Laravel 5
    1.1.0 - February 13th, 2015 - Added Laravel 5 compatibility
    1.0.7 - February 9th, 2015 - Documentation updates and some small tweaks
    1.0.6 - January 5th, 2015 - Bug fixes, see release notes for more
    1.0.5 - January 4th, 2015 - Updated Location::get() functionality, see usage
    1.0.4 - December 29th, 2014 - Added new driver Telize, re-publish config after update to use it
    1.0.3 - December 28th, 2014 - Cleaned up Location class a bit and added more documentation
    1.0.2 - December 22nd, 2014 - Added New Driver IpInfo, re-publish config after update if you'd like to use it
    1.0.1 - December 8th, 2014 - Added MaxMind GeoIP dependency
    1.0.0 - December 8th, 2014 - Revamped entire package

##Usage

####Getting a user location (automatic IP detection):

    $location = Location::get();

####Getting a user location with a specific IP:

    Location::get('192.168.1.1');
    
You can also retrieve a specific field with either
    
    //Using automatic IP detection
    Location::get(NULL, 'countryCode');
    
    Location::get('192.168.1.1', 'countryCode');

Using `Location::get()` will return a Location object, where you can retrieve each field with:

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

Available drivers at the moment are [Telize](http://www.telize.com/), [IpInfo](https://ipinfo.io/), [FreeGeoIp](https://freegeoip.net/), [GeoPlugin](http://www.geoplugin.com/), [MaxMind](https://www.maxmind.com/en/home). Default selected driver is Telize.

####Creating your own drivers

To create your own location driver, change the driver namespace in the config file (the 'driver_namespace' key) to your own namespace.

The class must implement the public method `get($ip)`. `$ip` being the users IP address being inputted.

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

    'driver_namespace' => 'MyApp\Location\Drivers\\',

    'selected_driver' => 'MyDriver',

Keep in mind that this will prevent the pre-existing drivers from working. This will be changed in the upcoming releases.

##Configuration

####Drivers

The drivers array in the configuration file stores a list of the available drivers and their configuration.

####Driver Namespace

Stores the namespace which the drivers in the Drivers array above, are located.

####Selected Driver

The selected driver that exists in the drivers array

####Selected Driver Fallbacks

The drivers to fallback to in the drivers array if the select driver fails. These will be tried in succession. 
It's recommended to use MaxMind as the last driver fallback so you're always retrieving some data.

####Localhost Testing

If you're running your web application locally, you may want to set this to true so you can set the `localhost_testing_ip`
and try different IP addresses.

####Localhost Forget Location

Setting this to true removes the location key from the session so it is retrieved on every request for testing purposes.

####Localhost Testing IP

When `localhost_testing` is set to true, the location will be grabbed from this IP, even if you specify an IP inside the
`Location::get($ip)` function.

####Dropdown Config

This allows you to set the keys and name of the dropdown HTML list that's generated from `Location::dropdown()` or `Location::lists()`

####Country Codes

This is used for the dropdown HTML list, as well as some drivers to convert the countries location code into the location name.