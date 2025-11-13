<?php

declare(strict_types=1);

namespace Lisoing\Calendar\Support;

final class LocaleResolver
{
    /**
     * @param  array<int, string>  $supported
     */
    public static function resolve(?string $locale, array $supported, string $default, string ...$fallbacks): string
    {
        $candidates = self::candidates($locale, array_merge([$default], $fallbacks));

        if ($supported === []) {
            return $candidates[0] ?? $default;
        }

        foreach ($candidates as $candidate) {
            if (in_array($candidate, $supported, true)) {
                return $candidate;
            }
        }

        return $default;
    }

    /**
     * @param  array<int, string>  $fallbacks
     * @return array<int, string>
     */
    public static function candidates(?string $locale, array $fallbacks = []): array
    {
        $locales = [];

        $canonical = self::canonicalize($locale);

        if ($canonical !== '') {
            $locales[] = $canonical;

            if (str_contains($canonical, '_')) {
                $segments = explode('_', $canonical);

                if ($segments[0] !== '') {
                    $locales[] = $segments[0];
                }
            }
        }

        foreach ($fallbacks as $fallback) {
            $canonicalFallback = self::canonicalize($fallback);

            if ($canonicalFallback !== '') {
                $locales[] = $canonicalFallback;
            }
        }

        return array_values(array_unique($locales));
    }

    public static function canonicalize(?string $locale): string
    {
        if ($locale === null) {
            return '';
        }

        $locale = str_replace('-', '_', trim($locale));

        if ($locale === '') {
            return '';
        }

        $segments = explode('_', $locale);

        $primary = strtolower($segments[0]);

        if ($primary === '') {
            return '';
        }

        $region = $segments[1] ?? null;

        if ($region === null || $region === '') {
            return $primary;
        }

        return $primary.'_'.strtoupper($region);
    }
}
