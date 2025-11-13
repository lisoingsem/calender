<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Exceptions;

use InvalidArgumentException;

final class CalendarNotFoundException extends InvalidArgumentException
{
    /**
     * @param  array<int, string>  $available
     */
    public static function make(string $missing, array $available): self
    {
        return new self(sprintf(
            'Calendar [%s] is not registered. Available calendars: %s',
            $missing,
            implode(', ', $available)
        ));
    }
}
