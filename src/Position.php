<?php

namespace Stevebauman\Location;

class Position
{
    /**
     * The country name.
     *
     * @var string
     */
    public $countryName = '';

    /**
     * The country code.
     *
     * @var string
     */
    public $countryCode = '';

    /**
     * The region code.
     *
     * @var string
     */
    public $regionCode = '';

    /**
     * The region name.
     *
     * @var string
     */
    public $regionName = '';

    /**
     * The city name.
     *
     * @var string
     */
    public $cityName = '';

    /**
     * The zip code.
     *
     * @var string
     */
    public $zipCode = '';

    /**
     * The iso code.
     *
     * @var string
     */
    public $isoCode = '';

    /**
     * The postal code.
     *
     * @var string
     */
    public $postalCode = '';

    /**
     * The latitude.
     *
     * @var string
     */
    public $latitude = '';

    /**
     * The longitude.
     *
     * @var string
     */
    public $longitude = '';

    /**
     * The metro code.
     *
     * @var string
     */
    public $metroCode = '';

    /**
     * The area code.
     *
     * @var string
     */
    public $areaCode = '';

    /**
     * The driver used for retrieving the location.
     *
     * @var string
     */
    public $driver = '';
}
