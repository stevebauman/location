<h1 align="center">Location</h1>

<p align="center">
Retrieve a user's location from their IP address using various services.
</p>

<p align="center">
<a href="https://github.com/stevebauman/location/actions">
<img src="https://img.shields.io/github/actions/workflow/status/stevebauman/location/run-tests.yml?branch=master&style=flat-square">
</a>

<a href="https://scrutinizer-ci.com/g/stevebauman/location/?branch=master">
<img src="https://img.shields.io/scrutinizer/g/stevebauman/location.svg?style=flat-square">
</a>

<a href="https://packagist.org/packages/stevebauman/location">
<img src="https://img.shields.io/packagist/dt/stevebauman/location.svg?style=flat-square">
</a>

<a href="https://packagist.org/packages/stevebauman/location">
<img src="https://img.shields.io/packagist/l/stevebauman/location.svg?style=flat-square">
</a>
</p>

## Requirements

- PHP >= 7.3
- PHP cURL extension enabled
- Laravel >= 5.0

## Installation

Install location using `composer require`:

```bash
composer require stevebauman/location
```

Add the service provider in `config/app.php`:

> **Important**: If you're using Laravel 5.5 or above, you can skip the registration
> of the service provider, as it is registered automatically.

```php
Stevebauman\Location\LocationServiceProvider::class
```

Publish the configuration file (this will create a `location.php` file inside the `config/` directory):

```bash
php artisan vendor:publish --provider="Stevebauman\Location\LocationServiceProvider"
```

## Usage

### Retrieve a client's location

```php
use Stevebauman\Location\Facades\Location;

if ($position = Location::get()) {
    // Successfully retrieved position.
    echo $position->countryName;
} else {
    // Failed retrieving position.
}
```

> **Note**:
> - This method retrieves the user's IP address via `request()->ip()`.
> - In the default configuration, `testing.enabled` is active, the returned IP address is in the USA. Disable it to get the client's real IP address.

### Retrieve the location of a specific IP address

```php
$position = Location::get('192.168.1.1');
```

## Drivers

### Available Drivers

Available drivers:

- [IpApi](http://ip-api.com) - Default
- [IpApiPro](https://pro.ip-api.com)
- [IpData](https://ipdata.co)
- [IpInfo](https://ipinfo.io)
- [Kloudend](https://ipapi.co)
- [GeoPlugin](http://www.geoplugin.com)
- [MaxMind](https://www.maxmind.com/en/home)
- [Cloudflare](https://support.cloudflare.com/hc/en-us/articles/200168236-Configuring-IP-geolocation)

#### Setting up MaxMind with a self-hosted database (optional)

We encourage setting up MaxMind as a fallback driver using a local database, as it allows
you to bypass any throttling that could occur from using external web services.

To set up MaxMind to retrieve the user's location from your own server, you must:

1. Create a [maxmind account](https://www.maxmind.com/en/geolite2/signup).
2. Sign in.
3. Click "Download Files" from the left-hand navigation menu.
4. Download the `GeoLite2-City.tar.gz` or `GeoLite2-Country.tar.gz` GZIP file depending on how precisely you need to locate people.
3. Extract the downloaded file (you may need to use an application such as [7zip](http://www.7-zip.org/download.html) if on Windows).
4. Create a `maxmind` folder inside your Laravel application's `database` directory (`database/maxmind`).
5. Place the `GeoLite2-City.mmdb` or `GeoLite2-Country.mmdb` file into the `maxmind` directory. You should end up with a folder path of:
    - `my-laravel-app/database/maxmind/GeoLite2-*.mmdb`.
6. Inside `config/location.php`, set your default location `driver` to `Stevebauman\Location\Drivers\MaxMind::class`.
7. If you opted for the country database, edit `maxmind.local.type` to `country` and update `maxmind.local.path`.
8. That's it, you're all set!

> **Note**: Keep in mind, you'll need to update this file on a regular basis to retrieve the most current information from clients.

### Fallback Drivers

In the config file, you can specify as many fallback drivers as you wish. It is
recommended to install the MaxMind database `.mmdb` file, so you are always
retrieving some generic location information for the user.

If an exception occurs trying to grab a driver (such as a 404 error if the
providers API changes), it will automatically use the next driver in line.

### Creating your own drivers

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
    
    protected function process($ip)
    {
        return rescue(function () use ($ip) {
            $response = json_decode(file_get_contents($this->url($ip)), true);
            
            return new Fluent($response);
        }, $rescue = false);
    }

    protected function hydrate(Position $position, Fluent $location)
    {
        $position->countryCode = $location->country_code;

        return $position;
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
