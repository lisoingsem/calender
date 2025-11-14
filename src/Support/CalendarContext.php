<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\App;
use Lisoing\Calendar\CalendarManager;
use Lisoing\Calendar\Formatting\FormatterManager;
use Lisoing\Calendar\ValueObjects\CalendarDate;
use Lisoing\Countries\Country;

final class CalendarContext
{
    private ?CalendarDate $currentDate = null;
    private ?string $timezone = null;

    public function __construct(
        private readonly CalendarManager $manager,
        private readonly string $targetIdentifier,
        private readonly ?string $countryClass = null
    ) {
        // Set timezone from country if available
        if ($countryClass !== null && class_exists($countryClass) && is_subclass_of($countryClass, Country::class)) {
            $countryTimezone = $countryClass::timezone();
            if ($countryTimezone !== null) {
                $this->timezone = $countryTimezone;
            }
        }
    }

    public function identifier(): string
    {
        return $this->targetIdentifier;
    }

    public function fromCarbon(CarbonInterface $dateTime): self
    {
        // Apply timezone if set
        if ($this->timezone !== null) {
            $dateTime = $dateTime->setTimezone($this->timezone);
        }

        $date = $this->manager->fromDateTime($dateTime, $this->targetIdentifier);
        
        // Create new context with the date for chaining
        $context = new self($this->manager, $this->targetIdentifier, $this->countryClass);
        $context->currentDate = $date;
        $context->timezone = $this->timezone;
        
        return $context;
    }

    /**
     * Create a new context with the given date for chaining.
     * This allows: Calendar::for(...)->withDate($date)->toLunar()->toString()
     */
    public function withDate(CalendarDate $date): self
    {
        $context = new self($this->manager, $this->targetIdentifier, $this->countryClass);
        $context->currentDate = $date;
        $context->timezone = $this->timezone;

        return $context;
    }

    public function fromCalendar(CalendarDate $date): CalendarDate
    {
        return $this->manager->convert($date, $this->targetIdentifier);
    }

    public function convert(CalendarDate $date): CalendarDate
    {
        return $this->fromCalendar($date);
    }

    public function toCarbon(?CalendarDate $date = null): CarbonInterface
    {
        $date = $date ?? $this->currentDate;
        
        if ($date === null) {
            throw new \RuntimeException('No date available. Call fromCarbon() first or pass a CalendarDate.');
        }

        return $this->manager->toDateTime($date);
    }

    /**
     * Switch to solar calendar (default: gregorian).
     */
    public function toSolar(?string $targetCalendar = 'gregorian'): self
    {
        if ($this->currentDate === null) {
            throw new \RuntimeException('No date available. Call fromCarbon() first.');
        }

        $converted = $this->manager->convert($this->currentDate, $targetCalendar);
        
        $context = new self($this->manager, $targetCalendar, $this->countryClass);
        $context->currentDate = $converted;
        $context->timezone = $this->timezone;

        return $context;
    }

    /**
     * Switch to gregorian calendar (alias for toSolar('gregorian')).
     */
    public function toGregorian(): self
    {
        return $this->toSolar('gregorian');
    }

    /**
     * Switch to lunisolar calendar.
     * Uses country's default lunisolar calendar if available, otherwise uses provided identifier.
     * 
     * @param  string|null  $targetCalendar  Calendar identifier (e.g., 'km', 'chinese')
     * @return self Returns self for chaining
     */
    public function toLunisolar(?string $targetCalendar = null): self
    {
        if ($this->currentDate === null) {
            throw new \RuntimeException('No date available. Call fromCarbon() first.');
        }

        // Determine target calendar
        $calendarIdentifier = null;
        if ($targetCalendar === null) {
            if ($this->countryClass !== null && class_exists($this->countryClass) && is_subclass_of($this->countryClass, Country::class)) {
                // Use country's lunisolar calendar
                $calendarIdentifier = $this->countryClass::calendar();
            } else {
                throw new \RuntimeException('No lunisolar calendar specified and no country class available.');
            }
        } else {
            $calendarIdentifier = $targetCalendar;
        }

        $converted = $this->manager->convert($this->currentDate, $calendarIdentifier);
        
        $context = new self($this->manager, $calendarIdentifier, $this->countryClass);
        $context->currentDate = $converted;
        $context->timezone = $this->timezone;

        return $context;
    }

    /**
     * Switch to Islamic/lunar calendar.
     * Converts to a purely lunar calendar (moon-based only, no solar alignment).
     * 
     * @param  string|null  $targetCalendar  Calendar identifier (e.g., 'islamic', 'hijri'). Defaults to 'islamic' if not provided.
     * @return self Returns self for chaining
     */
    public function toIslamic(?string $targetCalendar = null): self
    {
        if ($this->currentDate === null) {
            throw new \RuntimeException('No date available. Call fromCarbon() first.');
        }

        // Default to 'islamic' if no calendar specified
        $calendarIdentifier = $targetCalendar ?? 'islamic';

        $converted = $this->manager->convert($this->currentDate, $calendarIdentifier);
        
        $context = new self($this->manager, $calendarIdentifier, $this->countryClass);
        $context->currentDate = $converted;
        $context->timezone = $this->timezone;

        return $context;
    }

    /**
     * Set timezone for subsequent operations.
     */
    public function timezone(?string $timezone): self
    {
        $context = new self($this->manager, $this->targetIdentifier, $this->countryClass);
        $context->currentDate = $this->currentDate;
        $context->timezone = $timezone;

        return $context;
    }

    /**
     * Format the current date using FormatterManager.
     */
    public function format(?string $locale = null): string
    {
        if ($this->currentDate === null) {
            throw new \RuntimeException('No date available. Call fromCarbon() first.');
        }

        if (! function_exists('app')) {
            throw new \RuntimeException('Laravel application not available. FormatterManager requires Laravel.');
        }

        /** @var FormatterManager $formatterManager */
        $formatterManager = app(FormatterManager::class);

        return $formatterManager->format($this->currentDate, $locale);
    }

    /**
     * Get formatted string from CalendarDate.
     * 
     * @param  bool  $includeStructureWords  Whether to include structure words (for Khmer: ថ្ងៃ, ខែ, ឆ្នាំ)
     */
    public function toString(?string $locale = null, bool $includeStructureWords = true): string
    {
        if ($this->currentDate === null) {
            throw new \RuntimeException('No date available. Call fromCarbon() first.');
        }

        return $this->currentDate->toString($locale, $includeStructureWords);
    }

    /**
     * Get the current CalendarDate.
     */
    public function getDate(): ?CalendarDate
    {
        return $this->currentDate;
    }

    /**
     * Get calendar identifier from current date (for backward compatibility).
     */
    public function getCalendar(): ?string
    {
        return $this->currentDate?->getCalendar();
    }

    /**
     * Get context from current date (for backward compatibility).
     */
    public function getContext(): array
    {
        return $this->currentDate?->getContext() ?? [];
    }

    // Proxy methods to CalendarDate for backward compatibility and convenience
    public function getYear(): int
    {
        if ($this->currentDate === null) {
            throw new \RuntimeException('No date available. Call fromCarbon() first.');
        }
        return $this->currentDate->getYear();
    }

    public function getMonth(): int
    {
        if ($this->currentDate === null) {
            throw new \RuntimeException('No date available. Call fromCarbon() first.');
        }
        return $this->currentDate->getMonth();
    }

    public function getDay(): int
    {
        if ($this->currentDate === null) {
            throw new \RuntimeException('No date available. Call fromCarbon() first.');
        }
        return $this->currentDate->getDay();
    }

    public function getDayOfWeek(?string $locale = null): string
    {
        if ($this->currentDate === null) {
            throw new \RuntimeException('No date available. Call fromCarbon() first.');
        }
        return $this->currentDate->getDayOfWeek($locale);
    }

    public function getLunarDay(?string $locale = null): string
    {
        if ($this->currentDate === null) {
            throw new \RuntimeException('No date available. Call fromCarbon() first.');
        }
        return $this->currentDate->getLunarDay($locale);
    }

    public function getLunarMonth(?string $locale = null): string
    {
        if ($this->currentDate === null) {
            throw new \RuntimeException('No date available. Call fromCarbon() first.');
        }
        return $this->currentDate->getLunarMonth($locale);
    }

    public function getLunarYear(?string $locale = null): string
    {
        if ($this->currentDate === null) {
            throw new \RuntimeException('No date available. Call fromCarbon() first.');
        }
        return $this->currentDate->getLunarYear($locale);
    }

    public function getAnimalYear(?string $locale = null): string
    {
        if ($this->currentDate === null) {
            throw new \RuntimeException('No date available. Call fromCarbon() first.');
        }
        return $this->currentDate->getAnimalYear($locale);
    }

    public function getEraYear(?string $locale = null): string
    {
        if ($this->currentDate === null) {
            throw new \RuntimeException('No date available. Call fromCarbon() first.');
        }
        return $this->currentDate->getEraYear($locale);
    }

    public function getPhase(?string $locale = null): string
    {
        if ($this->currentDate === null) {
            throw new \RuntimeException('No date available. Call fromCarbon() first.');
        }
        return $this->currentDate->getPhase($locale);
    }

    public function formatDay(?string $locale = null): string
    {
        if ($this->currentDate === null) {
            throw new \RuntimeException('No date available. Call fromCarbon() first.');
        }
        return $this->currentDate->formatDay($locale);
    }
}

