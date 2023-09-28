<?php

namespace Stevebauman\Location;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

class Position implements Arrayable
{
    /**
     * The IP address used to retrieve the location.
     */
    public string $ip;

    /**
     * The driver used for retrieving the location.
     */
    public string $driver;

    /**
     * The country name.
     */
    public ?string $countryName = null;

    /**
     * The country's currency code.
     */
    public ?string $currencyCode;

    /**
     * The country code.
     */
    public ?string $countryCode = null;

    /**
     * The region code.
     */
    public ?string $regionCode = null;

    /**
     * The region name.
     */
    public ?string $regionName = null;

    /**
     * The city name.
     */
    public ?string $cityName = null;

    /**
     * The zip code.
     */
    public ?string $zipCode = null;

    /**
     * The ISO code.
     */
    public ?string $isoCode = null;

    /**
     * The postal code.
     */
    public ?string $postalCode = null;

    /**
     * The latitude.
     */
    public ?string $latitude = null;

    /**
     * The longitude.
     */
    public ?string $longitude = null;

    /**
     * The metro code.
     */
    public ?string $metroCode = null;

    /**
     * The area code.
     */
    public ?string $areaCode = null;

    /**
     * The timezone.
     */
    public ?string $timezone = null;

    /**
     * Determine if the position is empty.
     */
    public function isEmpty(): bool
    {
        $data = Arr::except(
            $this->toArray(), ['ip', 'driver']
        );

        return empty(array_filter($data));
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
