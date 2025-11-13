<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Tests;

use Lisoing\Calendar\CalendarServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [CalendarServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.timezone', 'Asia/Phnom_Penh');
        $app['config']->set('cache.default', 'array');
    }
}

