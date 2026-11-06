<h1 align="center">Location</h1>

<p align="center">
Retrieve a visitor's location from their IP address using various services (online & local).
</p>

<p align="center">
<a href="https://github.com/stevebauman/location/actions"><img src="https://img.shields.io/github/actions/workflow/status/stevebauman/location/run-tests.yml?branch=master&style=flat-square" alt="Build status"></a>
<a href="https://packagist.org/packages/stevebauman/location"><img src="https://img.shields.io/packagist/dt/stevebauman/location.svg?style=flat-square" alt="Number of downloads"></a>
<a href="https://packagist.org/packages/stevebauman/location"><img src="https://img.shields.io/packagist/l/stevebauman/location.svg?style=flat-square" alt="MIT license"></a>
</p>

## Index

- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Testing](#testing)
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

`Location::get()` returns a `Stevebauman\Location\Position` instance, or `false` when no driver could
locate the IP address. With the default `IpApi` driver, the position looks like this:

```php
$position = Location::get('8.8.8.8');

$position->toArray();
```

```php
[
    'ip' => '8.8.8.8',
    'driver' => 'Stevebauman\Location\Drivers\IpApi',
    'countryName' => 'United States',
    'currencyCode' => 'USD',
    'countryCode' => 'US',
    'regionCode' => 'VA',
    'regionName' => 'Virginia',
    'cityName' => 'Ashburn',
    'zipCode' => '20149',
    'isoCode' => null,
    'postalCode' => '20149',
    'latitude' => '39.03',
    'longitude' => '-77.5',
    'metroCode' => null,
    'areaCode' => null,
    'timezone' => 'America/New_York',
]
```

All values are strings, including `latitude` and `longitude`. A field the driver
doesn't provide is `null`. See [Available Fields](#available-fields) for what each driver returns.

### Retrieve the location of a specific IP address

```php
$position = Location::get('8.8.8.8');
```

## Testing

You may call `Location::fake` with an array of IP address patterns and their expected positions to fake the location of an IP address:

```php
use Stevebauman\Location\Position;
use Stevebauman\Location\Facades\Location;

Location::fake([
    '127.0.0.1' => Position::make([
        'countryName' => 'United States',
        'countryCode' => 'US',
        // ...
    ])
]);

// Somewhere in your application...

$position = Location::get('127.0.0.1'); // Position
```

If you prefer, you may use an asterisk to return the same position for any IP address that is given: 

```php
Location::fake([
    '*' => Position::make([
        'countryName' => 'United States',
        'countryCode' => 'US',
        // ...
    ])
]);

$position = Location::get($anyIpAddress); // Position
```

If no expectations are given, or an expectation is not matched, `Location::get` will return `false`:

```php
Location::fake();

Location::get($anyIpAddress); // false
```

If your application attempts to retrieve the location's of multiple IP addresses, you may provide multiple IP address expectation patterns:

```php
Location::fake([
    '127.0.0.1' => Position::make([
        'countryName' => 'United States',
        'countryCode' => 'US',
        // ...
    ]),
    '192.168.1.1' => Position::make([
        'countryName' => 'Canada',
        'countryCode' => 'CA',
        // ...
    ]),
]);
```

You may also use an asterisk to fake several IP patterns:

```php
Location::fake([
    '192.123.*.*' => Position::make([
        'countryName' => 'United States',
        'countryCode' => 'US',
        // ...
    ]),
    '192.456.*.*' => Position::make([
        'countryName' => 'Canada',
        'countryCode' => 'CA',
        // ...
    ]),
]);
```

## Drivers

### Available Drivers

| Driver | Token (`.env`) | HTTPS | Notes |
|---|---|---|---|
| [IpApi](https://ip-api.com) | - | No | Default driver. The free API is for non-commercial use and is rate limited. |
| [IpApiPro](https://pro.ip-api.com) | `IP_API_TOKEN` | Yes | Paid version of IpApi, same fields. |
| [IpData](https://ipdata.co) | `IPDATA_TOKEN` | Yes | Token required. |
| [IpInfo](https://ipinfo.io) | `IPINFO_TOKEN` (optional) | No | Doesn't return the country name. |
| [IpInfo Lite](https://ipinfo.io/lite) | `IPINFO_TOKEN` | Yes | Country only. |
| [Kloudend](https://ipapi.co) | `KLOUDEND_TOKEN` (optional) | Yes | |
| [GeoPlugin](https://www.geoplugin.com) | - | No | The free API has been discontinued, requests without a paid plan return `403`. |
| [MaxMind](https://www.maxmind.com/en/home) | `MAXMIND_LICENSE_KEY` | - | Reads a local database, no request per lookup. The web service also needs `MAXMIND_USER_ID`. |
| [Cloudflare](https://developers.cloudflare.com/network/ip-geolocation/) | - | - | Reads the headers Cloudflare adds to the request, so the app must be behind Cloudflare and the IP passed to `Location::get()` is ignored. |
| [IP2Location.io](https://www.ip2location.io/) | `IP2LOCATIONIO_TOKEN` | Yes | |

#### Available Fields

Every position has `ip` and `driver`. The other fields depend on the driver:

| Field | IpApi / Pro | IpData | IpInfo | IpInfo Lite | Kloudend | GeoPlugin | MaxMind City | MaxMind Country | Cloudflare | IP2Location.io |
|---|---|---|---|---|---|---|---|---|---|---|
| `countryName` | ✓ | ✓ | | ✓ | ✓ | ✓ | ✓ | ✓ | | ✓ |
| `countryCode` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `isoCode` | | | | | | | ✓ | ✓ | ✓ | |
| `currencyCode` | ✓ | ✓ | | | ✓ | | | | | ✓ ¹ |
| `regionCode` | ✓ | ✓ | | | ✓ | ✓ | ✓ | | ✓ ² | ✓ ¹ |
| `regionName` | ✓ | ✓ | ✓ | | ✓ | ✓ | ✓ | | ✓ ² | ✓ |
| `cityName` | ✓ | ✓ | ✓ | | ✓ | ✓ | ✓ | | ✓ ² | ✓ |
| `zipCode`, `postalCode` | ✓ | ✓ | ✓ | | ✓ | | ✓ | | ✓ ² | ✓ |
| `latitude`, `longitude` | ✓ | ✓ | ✓ | | ✓ | ✓ | ✓ | | ✓ ² | ✓ |
| `timezone` | ✓ | ✓ | ✓ | | ✓ | ✓ | ✓ | | ✓ ² | ✓ ³ |
| `metroCode` | | | | | | | ✓ ⁴ | | | ✓ ¹ |
| `areaCode` | | | | | | ✓ | | | | ✓ ¹ |

1. Only on paid IP2Location.io plans, `null` on the free plan.
2. Only when the "Add visitor location headers" Managed Transform is enabled in Cloudflare. Without it, only the country is available.
3. A UTC offset such as `-07:00`, not a timezone name like the other drivers.
4. Deprecated by MaxMind.

`zipCode` and `postalCode` always hold the same value.

#### Setting up MaxMind with a self-hosted database (optional)

It is encouraged to set up MaxMind as a fallback driver using a local database 
so that some location information is returned in the event of hitting
a rate limit from the external web services.

To set up MaxMind to retrieve the user's location from your own server, you must:

1. Create a [maxmind account](https://www.maxmind.com/en/geolite2/signup) and sign in
2. Click "Manage License Keys" from the profile menu dropdown
3. Generate a new license key
4. Copy the license key and save it into your `.env` file using the `MAXMIND_LICENSE_KEY` key
5. Run `php artisan location:update` to download the latest `.mmdb` file into your `database/maxmind` directory
6. That's it, you're all set!

> **Note**: Keep in mind, you'll need to update this file by running `location:update` 
> on a regular basis to retrieve the most current information from your visitors.

#### Sharing a MaxMind database across servers

When an application runs on multiple servers, the MaxMind database may be stored
on a shared Laravel filesystem disk. Each server caches the shared database on
its local filesystem because the MaxMind reader requires a local file path.

```php
// config/location.php

'maxmind' => [
    // ...

    'storage' => [
        'disk' => env('MAXMIND_STORAGE_DISK'),
        'path' => env('MAXMIND_STORAGE_PATH', 'maxmind/GeoLite2-City.mmdb'),
        'ttl' => env('MAXMIND_STORAGE_TTL', 3600),
    ],

    'local' => [
        'type' => 'city',
        'path' => storage_path('app/maxmind/GeoLite2-City.mmdb'),
        // ...
    ],
],
```

For example, the database may be stored on the application's `s3` disk:

```ini
MAXMIND_STORAGE_DISK=s3
MAXMIND_STORAGE_PATH=maxmind/GeoLite2-City.mmdb
MAXMIND_STORAGE_TTL=3600
```

The `ttl` value is the number of seconds a local database may be used before the
configured disk is checked for a newer version.

Only one server should be scheduled to download and publish the database from
MaxMind. Laravel's `onOneServer` method may be used when every server uses the
same lock-capable cache store:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('location:update')
    ->daily()
    ->onOneServer();
```

Other servers will download the database from the shared disk when it is first
needed and refresh their local copy after its TTL has elapsed.

Each server coordinates refreshes with a lock beside its local database. While
one worker refreshes the database, other workers continue using the existing
copy. If a refresh fails, the existing database is retained and another attempt
is made after the configured TTL. Downloads are validated before replacing the
local copy.

### Fallback Drivers

In the config file, you can specify as many fallback drivers as you wish. It is
recommended to configure the MaxMind driver with the local database `.mmdb`
file (mentioned above), so you are alwaysretrieving some generic location
information from the visitor.

If an exception occurs trying to grab a driver (such as a 400/500 error if the
providers API changes), it will automatically use the next driver in line.

### Timeouts

The `http` config option times out a single driver's request. Each driver gets
its own, so with the default configuration an unreachable provider costs you
that timeout five times over before the lookup gives up.

Set `total_timeout` to give the whole lookup one shared budget instead:

```php
'total_timeout' => 5,
```

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
