<?php

namespace Stevebauman\Location\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Stevebauman\Location\LocationServiceProvider;

class TestCase extends BaseTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getPackageProviders($app)
    {
        return [
            LocationServiceProvider::class,
        ];
    }
}

