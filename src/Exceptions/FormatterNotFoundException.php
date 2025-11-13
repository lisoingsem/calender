<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Exceptions;

use InvalidArgumentException;

final class FormatterNotFoundException extends InvalidArgumentException
{
    /**
     * @param  array<int, string>  $available
     */
    public static function make(string $missing, array $available): self
    {
        return new self(sprintf(
            'Formatter for calendar [%s] is not registered. Available formatters: %s',
            $missing,
            implode(', ', $available)
        ));
    }
}
