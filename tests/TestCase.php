<?php

namespace Elzdave\Benevolent\Tests;

use Elzdave\Benevolent\BenevolentServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected $baseUrl = 'http://benevolent.test';

    protected function getPackageProviders($app)
    {
        return [
            BenevolentServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('auth.guards.web.provider', 'benevolent');
        $app['config']->set('benevolent.base_url', $this->baseUrl);
    }
}
