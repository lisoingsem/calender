<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Tests;

use Lisoing\Calendar\CalendarServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            CalendarServiceProvider::class,
        ];
    }
}

