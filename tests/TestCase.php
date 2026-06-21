<?php

namespace Recurrente\Laravel\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Recurrente\Laravel\RecurrenteServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [RecurrenteServiceProvider::class];
    }
}
