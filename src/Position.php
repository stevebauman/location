<?php

namespace Stevebauman\Location;

class Position
{
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
        $data = get_object_vars($this);

        unset($data['driver']);

        return count(array_filter($data)) === 0;
    }
}
