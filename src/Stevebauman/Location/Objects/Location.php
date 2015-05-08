<?php

namespace Stevebauman\Location\Objects;

/**
 * Class Location.
 */
class Location
{
    /**
     * Holds the country name.
     *
     * @var string
     */
    public $countryName = '';

    /**
     * Holds the country code.
     *
     * @var string
     */
    public $countryCode = '';

    /**
     * Holds the region code.
     *
     * @var string
     */
    public $regionCode = '';

    /**
     * Holds the region name.
     *
     * @var string
     */
    public $regionName = '';

    /**
     * Holds the city name.
     *
     * @var string
     */
    public $cityName = '';

    /**
     * Holds the zip code.
     *
     * @var string
     */
    public $zipCode = '';

    /**
     * Holds the iso code.
     *
     * @var string
     */
    public $isoCode = '';

    /**
     * Holds the postal code.
     *
     * @var string
     */
    public $postalCode = '';

    /**
     * Holds the latitude.
     *
     * @var string
     */
    public $latitude = '';

    /**
     * Holds the longitude.
     *
     * @var string
     */
    public $longitude = '';

    /**
     * Holds the metro code.
     *
     * @var string
     */
    public $metroCode = '';

    /**
     * Holds the area code.
     *
     * @var string
     */
    public $areaCode = '';

    /**
     * Holds the internet service provider name.
     *
     * @var string
     */
    public $isp = '';

    /*
     * Holds the IP address that was used to retrieve location information
     *
     * @var string
     */
    public $ip = '';

    /*
     * Holds the drivers name the location was taken from
     *
     * @var string
     */
    public $driver = '';

    /*
     * Indicates if there was an issue gathering the location from the driver.
     * This is used for driver fallbacks
     *
     * @var bool
     */
    public $error = false;
}
