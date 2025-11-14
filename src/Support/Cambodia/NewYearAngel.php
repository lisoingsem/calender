<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support\Cambodia;

/**
 * New Year Angel value object representing one of the seven angels
 * that come down to earth during Khmer New Year.
 *
 * Based on the Songkran Sote folklore, each angel represents a different
 * day of the week when Songkran falls.
 */
final class NewYearAngel
{
    /**
     * @param  int  $dayOfWeek  Day of week (0=Sunday, 1=Monday, ..., 6=Saturday)
     * @param  string  $nameKey  Translation key for angel name
     * @param  string  $jewelryKey  Translation key for jewelry description
     * @param  string  $flowerKey  Translation key for flower name
     * @param  string  $foodKey  Translation key for favorite food
     * @param  string  $rightHandKey  Translation key for right hand item
     * @param  string  $leftHandKey  Translation key for left hand item
     * @param  string  $animalKey  Translation key for animal ridden
     */
    public function __construct(
        private readonly int $dayOfWeek,
        private readonly string $nameKey,
        private readonly string $jewelryKey,
        private readonly string $flowerKey,
        private readonly string $foodKey,
        private readonly string $rightHandKey,
        private readonly string $leftHandKey,
        private readonly string $animalKey
    ) {}

    public function dayOfWeek(): int
    {
        return $this->dayOfWeek;
    }

    public function nameKey(): string
    {
        return $this->nameKey;
    }

    public function jewelryKey(): string
    {
        return $this->jewelryKey;
    }

    public function flowerKey(): string
    {
        return $this->flowerKey;
    }

    public function foodKey(): string
    {
        return $this->foodKey;
    }

    public function rightHandKey(): string
    {
        return $this->rightHandKey;
    }

    public function leftHandKey(): string
    {
        return $this->leftHandKey;
    }

    public function animalKey(): string
    {
        return $this->animalKey;
    }
}

