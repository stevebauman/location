<?php

namespace Stevebauman\Location;

use Illuminate\Contracts\Support\Arrayable;

class Position implements Arrayable
{
    /**
     * The IP address used to retrieve the location.
     *
     * @var string
     */
    public $ip;

    /**
     * The country name.
     *
     * @var string|null
     */
    public $countryName;

    /**
     * The country code.
     *
     * @var string|null
     */
    public $countryCode;

    /**
     * The region code.
     *
     * @var string|null
     */
    public $regionCode;

    /**
     * The region name.
     *
     * @var string|null
     */
    public $regionName;

    /**
     * The city name.
     *
     * @var string|null
     */
    public $cityName;

    /**
     * The zip code.
     *
     * @var string|null
     */
    public $zipCode;

    /**
     * The iso code.
     *
     * @var string|null
     */
    public $isoCode;

    /**
     * The postal code.
     *
     * @var string|null
     */
    public $postalCode;

    /**
     * The latitude.
     *
     * @var string|null
     */
    public $latitude;

    /**
     * The longitude.
     *
     * @var string|null
     */
    public $longitude;

    /**
     * The metro code.
     *
     * @var string|null
     */
    public $metroCode;

    /**
     * The area code.
     *
     * @var string|null
     */
    public $areaCode;

    /**
     * The driver used for retrieving the location.
     *
     * @var string|null
     */
    public $driver;

    /**
     * Determine if the position is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        $data = $this->toArray();

        unset($data['ip']);
        unset($data['driver']);

        return empty(array_filter($data));
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return get_object_vars($this);
    }
}
