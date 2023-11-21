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
     * The location's country name.
     */
    public ?string $countryName = null;

    /**
     * The location's currency code.
     */
    public ?string $currencyCode;

    /**
     * The location's country code.
     */
    public ?string $countryCode = null;

    /**
     * The location's region code.
     */
    public ?string $regionCode = null;

    /**
     * The location's region name.
     */
    public ?string $regionName = null;

    /**
     * The location's city name.
     */
    public ?string $cityName = null;

    /**
     * The location's zip code.
     */
    public ?string $zipCode = null;

    /**
     * The location's ISO code.
     */
    public ?string $isoCode = null;

    /**
     * The location's postal code.
     */
    public ?string $postalCode = null;

    /**
     * The location's latitude.
     */
    public ?string $latitude = null;

    /**
     * The location's longitude.
     */
    public ?string $longitude = null;

    /**
     * The location's metro code.
     */
    public ?string $metroCode = null;

    /**
     * The location's area code.
     */
    public ?string $areaCode = null;

    /**
     * The location's timezone.
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
     * Transform the instance to an array.
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
