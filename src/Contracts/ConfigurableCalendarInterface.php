<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Contracts;

interface ConfigurableCalendarInterface
{
    /**
     * @param  array<string, mixed>  $settings
     */
    public function configure(array $settings): void;
}
