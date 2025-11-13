<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Contracts;

interface ConfigurableHolidayProviderInterface
{
    /**
     * @param  array<string, mixed>  $settings
     */
    public function configure(array $settings): void;
}
