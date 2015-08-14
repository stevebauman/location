<?php

namespace Stevebauman\Location\Tests;

use Orchestra\Testbench\TestCase;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\LocationServiceProvider;

class FunctionalTestCase extends TestCase
{
    public function getPackageProviders($app)
    {
        return [LocationServiceProvider::class];
    }

    public function getPackageAliases($app)
    {
        return ['Location' => Location::class];
    }
}
