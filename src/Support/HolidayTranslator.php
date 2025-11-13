<?php
declare(strict_types=1);

namespace Lisoing\Calendar\Support;

use Illuminate\Support\Arr;
use JsonException;

final class HolidayTranslator
{
    public static function translate(string $directory, string $key, string $locale, string $fallbackLocale): string
    {
        $translations = self::loadTranslations($directory, $locale);

        if (Arr::has($translations, $key)) {
            return (string) Arr::get($translations, $key);
        }

        if ($fallbackLocale !== '' && strcasecmp($locale, $fallbackLocale) !== 0) {
            $fallbackTranslations = self::loadTranslations($directory, $fallbackLocale);

            if (Arr::has($fallbackTranslations, $key)) {
                return (string) Arr::get($fallbackTranslations, $key);
            }
        }

        return $key;
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadTranslations(string $directory, string $locale): array
    {
        $directory = strtolower($directory);
        $locale = strtolower($locale);

        if ($directory === '' || $locale === '') {
            return [];
        }

        foreach (self::candidatePaths($directory, $locale) as $path => $type) {
            if (! file_exists($path)) {
                continue;
            }

            try {
                $translations = self::loadFile($path, $type);
            } catch (JsonException) {
                continue;
            }

            if (is_array($translations)) {
                /** @var array<string, mixed> $translations */
                return $translations;
            }
        }

        return [];
    }

    /**
     * @return array<string, string>
     */
    private static function candidatePaths(string $directory, string $locale): array
    {
        $base = self::languageDirectory().'/'.$directory.'/'.$locale.'/holidays';

        return [
            $base.'.php' => 'php',
            $base.'.json' => 'json',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function loadFile(string $path, string $type): ?array
    {
        return match ($type) {
            'php' => self::loadPhpFile($path),
            'json' => self::loadJsonFile($path),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function loadPhpFile(string $path): ?array
    {
        $data = require $path;

        return is_array($data) ? $data : null;
    }

    /**
     * @return array<string, mixed>|null
     *
     * @throws JsonException
     */
    private static function loadJsonFile(string $path): ?array
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return is_array($data) ? $data : null;
    }

    private static function languageDirectory(): string
    {
        return dirname(__DIR__, 2).'/lang';
    }
}
