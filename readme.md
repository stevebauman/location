<h1 align="center">Location</h1>

<p align="center">
Retrieve a visitor's location from their IP address using various services.
</p>

<p align="center">
<a href="https://github.com/stevebauman/location/actions"><img src="https://img.shields.io/github/actions/workflow/status/stevebauman/location/run-tests.yml?branch=master&style=flat-square"></a>
<a href="https://packagist.org/packages/stevebauman/location"><img src="https://img.shields.io/packagist/dt/stevebauman/location.svg?style=flat-square"></a>
<a href="https://packagist.org/packages/stevebauman/location"><img src="https://img.shields.io/packagist/l/stevebauman/location.svg?style=flat-square"></a>
</p>

- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Drivers](#drivers)
- [Upgrading from v6](#upgrading-from-v6)
- [Versioning](#versioning)

## Requirements

- PHP >= 8.1
- Laravel >= 8.0

## Installation

Install location using `composer require`:

```bash
composer require stevebauman/location
```

Publish the configuration file (this will create a `location.php` file inside the `config` directory):

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

> **Important**:
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
- [IP2Location.io](https://www.ip2location.io/)

#### Setting up MaxMind with a self-hosted database (optional)

We encourage setting up MaxMind as a fallback driver using a local database, as it allows
you to bypass any throttling that could occur from using external web services.

To set up MaxMind to retrieve the user's location from your own server, you must:

1. Create a [maxmind account](https://www.maxmind.com/en/geolite2/signup) and sign in
2. Click "Manage License Keys" from the profile menu dropdown
3. Generate a new license key
4. Copy the license key and save it into your `.env` file using the `MAXMIND_LICENSE_KEY` key
5. Run `php artisan location:update` to download the latest `.mmdb` file into your `database/maxmind` directory
6. That's it, you're all set!

> **Note**: Keep in mind, you'll need to update this file by running `location:update` 
> on a regular basis to retrieve the most current information from your visitors.

### Fallback Drivers

In the config file, you can specify as many fallback drivers as you wish. It is
recommended to configure the MaxMind driver with the local database `.mmdb`
file (mentioned above), so you are alwaysretrieving some generic location
information from the visitor.

If an exception occurs trying to grab a driver (such as a 400/500 error if the
providers API changes), it will automatically use the next driver in line.

### Creating your own drivers

To create your own driver, simply create a class in your application, and extend the abstract Driver:

```php
namespace App\Location\Drivers;

use Illuminate\Support\Fluent;
use Illuminate\Support\Facades\Http;
use Stevebauman\Location\Position;
use Stevebauman\Location\Request;
use Stevebauman\Location\Drivers\Driver;

class MyDriver extends Driver
{    
    protected function process(Request $request): Fluent
    {
         $response = Http::get("https://driver-url.com", ['ip' => $request->getIp()]);
         
         return new Fluent($response->json());
    }

    protected function hydrate(Position $position, Fluent $location): Position
    {
        $position->countryCode = $location->country_code;

        return $position;
    }
}
```

Then, insert your driver class name into the configuration file:

```php
// config/location.php

'driver' => App\Location\Drivers\MyDriver::class,
```

## Upgrading from v6

In version 7, the codebase has been updated with strict PHP types, 
updated PHP and Laravel version requirements, an updated `Driver` 
structure, as well as a small configuration addition.

### Configuration

In version 7, location drivers can now implement an `Updatable` interface 
that allows them to be updated using the `location:update` command. 
Currently, only the MaxMind driver supports this.

To update your configuration file to be able to download the latest
MaxMind database file automatically, insert the below `url` 
configuration option in your `config/location.php` file:

```diff
// config/location.php

return [
    'maxmind' => [
        // ...
        
        'local' => [
            // ...
            
+            'url' => sprintf('https://download.maxmind.com/app/geoip_download_by_token?edition_id=GeoLite2-City&license_key=%s&suffix=tar.gz', env('MAXMIND_LICENSE_KEY')),
        ],
    ],
];
```

Once done, you may execute the below artisan command to download the latest `.mmdb` file:

```bash
php artisan location:update
```

### Drivers

In version 7, the codebase has been updated with strict PHP 
types, updated PHP and Laravel version requirements, 
and an updated `Driver` structure.

If you have created your own custom driver implementation, 
you must update it to use the base `Driver` or `HttpDriver` class.

If you're fetching a location using an HTTP service, it
may be useful to extend the `HttpDriver` to reduce 
the code you need to write:

```diff
namespace App\Location\Drivers;

use Illuminate\Support\Fluent;
use Stevebauman\Location\Position;
- use Stevebauman\Location\Drivers\Driver;
+ use Stevebauman\Location\Drivers\HttpDriver;

- class MyDriver extends Driver
+ class MyDriver extends HttpDriver
{
-    public function url($ip)
+    public function url(string $ip): string;
    {
        return "http://driver-url.com?ip=$ip";
    }
    
-    protected function process($ip)
-    {
-        return rescue(function () use ($ip) {
-            $response = json_decode(file_get_contents($this->url($ip)), true);
-            
-            return new Fluent($response);
-        }, $rescue = false);
-    }

-    protected function hydrate(Position $position, Fluent $location)
+    protected function hydrate(Position $position, Fluent $location): Position;
    {
        $position->countryCode = $location->country_code;

        return $position;
    }
}
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
