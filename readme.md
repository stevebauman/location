# Location

[![Travis CI](https://img.shields.io/travis/stevebauman/location.svg?style=flat-square)](https://travis-ci.org/stevebauman/location)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/stevebauman/location.svg?style=flat-square)](https://scrutinizer-ci.com/g/stevebauman/location/?branch=master)
[![Latest Stable Version](https://img.shields.io/packagist/v/stevebauman/location.svg?style=flat-square)](https://packagist.org/packages/stevebauman/location)
[![Total Downloads](https://img.shields.io/packagist/dt/stevebauman/location.svg?style=flat-square)](https://packagist.org/packages/stevebauman/location)
[![License](https://img.shields.io/packagist/l/stevebauman/location.svg?style=flat-square)](https://packagist.org/packages/stevebauman/location)

Retrieve a user's location from their IP address using external web services, or through a flat-file database hosted on your server.

## Requirements

- Laravel >= 5
- PHP 7.0 or greater
- cURL extension enabled

## Installation

Run the following command in the root of your project:

```bash
composer require stevebauman/location
```

> **Note**: If you're using Laravel 5.5 or above, you can skip the registration
> of the service provider, as it is registered automatically.

Add the service provider in `config/app.php`:

```php
Stevebauman\Location\LocationServiceProvider::class,
```

Publish the config file (will create the `location.php` file inside the config directory):

```bash
php artisan vendor:publish --provider="Stevebauman\Location\LocationServiceProvider"
```

## Usage

#### Retrieve a user's location

> **Note**: This method retrieves the user's IP address via `request()->ip()`:

```php
$position = Location::get();

// Returns instance of Stevebauman\Location\Position on success, otherwise false (for example for local network ip addresses)
```

#### Retrieve the location of a specific IP address

```php
$position = Location::get('192.168.1.1');
```

## Drivers

#### Available Drivers

Available drivers at the moment are:

- [IpApi](http://ip-api.com) - Default (works out of the box without config)
- [IpApiPro](https://pro.ip-api.com)
- [IpInfo](https://ipinfo.io)
- [GeoPlugin](http://www.geoplugin.com)
- [MaxMind](https://www.maxmind.com/en/home)

#### Setting Up MaxMind (optional)

To setup MaxMind to retrieve the user's location from your own server, create a [maxmind account](https://www.maxmind.com/en/geolite2/signup), create a license key and download the database file in mmdb format:

https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key=YOUR_LICENSE_KEY&suffix=tar.gz

1. Extract the downloaded file (you may need to use an application such as [7zip](http://www.7-zip.org/download.html) if on Windows)
2. Create a `maxmind` folder inside your `database` directory (`database/maxmind`)
3. Place the GeoLite2-City.mmdb file into the `maxmind` directory

You should end up with a folder path of: `my-laravel-app/database/maxmind/GeoLite2-City.mmdb`.

Then, set your default driver to `Stevebauman\Location\Drivers\MaxMind::class`, and you're all set!

> **Note**: Keep in mind, you'll need to update this file continuously to retrieve the most current information.

#### Fallback drivers

In the config file, you can specify as many fallback drivers as you wish. It's recommended to install the MaxMind database `.mmdb` file so you are always retrieving some generic location information for the user.

If an exception occurs trying to grab a driver (such as a 404 error if the
providers API changes), it will automatically use the next driver in line.

#### Creating your own drivers

To create your own driver, simply create a class in your application, and extend the abstract Driver:

```php
namespace App\Location\Drivers;

use Illuminate\Support\Fluent;
use Stevebauman\Location\Position;
use Stevebauman\Location\Drivers\Driver;

class MyDriver extends Driver
{
    public function url($ip)
    {
        return "http://driver-url.com?ip=$ip";
    }

    protected function hydrate(Position $position, Fluent $location)
    {
        $position->countryCode = $location->country_code;

        return $position;
    }

    protected function process($ip)
    {
        try {
            $response = json_decode(file_get_contents($this->url($ip)), true);

            return new Fluent($response);
        } catch (\Exception $e) {
            return false;
        }
    }
}
```

Then, insert your driver class name into the configuration file:

```php
/*
|--------------------------------------------------------------------------
| Driver
|--------------------------------------------------------------------------
|
| The default driver you would like to use for location retrieval.
|
*/

'driver' => App\Locations\MyDriver::class,
```

## Versioning
Location is versioned under the Semantic Versioning guidelines as much as possible.

Releases will be numbered with the following format:

```
<major>.<minor>.<patch>
```

And constructed with the following guidelines:

- Breaking backward compatibility bumps the major and resets the minor and patch.
- New additions without breaking backward compatibility bumps the minor and resets the patch.
- Bug fixes and misc changes bumps the patch.

Minor versions are not maintained individually, and you're encouraged to upgrade through to the next minor version.

Major versions are maintained individually through separate branches.
