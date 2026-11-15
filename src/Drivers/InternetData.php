<?php

namespace Stevebauman\Location\Drivers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use Stevebauman\Location\Position;

class InternetData extends HttpDriver
{
    /**
     * {@inheritdoc}
     */
    public function url(string $ip): string
    {
        return "https://api.internetdata.io/$ip";
    }

    /**
     * {@inheritdoc}
     */
    protected function http(): PendingRequest
    {
        $http = parent::http();

        if ($token = config('location.internetdata.token')) {
            $http->withToken($token);
        }

        return $http;
    }

    /**
     * {@inheritdoc}
     */
    protected function hydrate(Position $position, Fluent $location): Position
    {
        $data = new Fluent($location->location ?? []);

        $position->countryName = $data->country_name;
        $position->countryCode = $data->country;
        $position->currencyCode = $data->currency_code;
        $position->timezone = $data->timezone;

        // The region and below come only with a key on a paid plan.
        $position->regionCode = $data->region_code ? Str::after($data->region_code, '-') : null;
        $position->regionName = $data->region;
        $position->cityName = $data->city;
        $position->zipCode = $data->postal_code;
        $position->postalCode = $data->postal_code;
        $position->latitude = $this->toNullableString($data->latitude);
        $position->longitude = $this->toNullableString($data->longitude);

        return $position;
    }
}
