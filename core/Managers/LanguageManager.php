<?php

declare(strict_types=1);

namespace Chamy\Core\Managers;

use Chamy\Core\Interfaces\ManagerInterface;

final class LanguageManager implements ManagerInterface
{
    private string $languagePath;
    private string $locale;
    private string $fallback;

    /** @var array<string, array<string, string>> */
    private array $translations = [];

    /** @var array<string> */
    private array $loadedFiles = [];

    public function __construct(string $languagePath, string $locale = 'de', string $fallback = 'en')
    {
        $this->languagePath = $languagePath;
        $this->locale = $locale;
        $this->fallback = $fallback;
    }

    public function getName(): string
    {
        return 'language';
    }

    public function boot(): void
    {
        $this->loadLocale($this->locale);

        if ($this->fallback !== $this->locale) {
            $this->loadLocale($this->fallback);
        }
    }

    public function translate(string $key, array $params = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->locale;

        $value = $this->translations[$locale][$key]
            ?? $this->translations[$this->fallback][$key]
            ?? $key;

        if (!empty($params)) {
            foreach ($params as $param => $replacement) {
                $value = str_replace(':' . $param, (string) $replacement, $value);
            }
        }

        return $value;
    }

    public function t(string $key, array $params = [], ?string $locale = null): string
    {
        return $this->translate($key, $params, $locale);
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
        $this->loadLocale($locale);
    }

    public function getFallback(): string
    {
        return $this->fallback;
    }

    public function getAvailableLocales(): array
    {
        $locales = [];
        $dirs = glob($this->languagePath . '/*', GLOB_ONLYDIR);

        if ($dirs === false) {
            return [];
        }

        foreach ($dirs as $dir) {
            $locales[] = basename($dir);
        }

        return $locales;
    }

    public function addTranslations(string $locale, array $translations): void
    {
        if (!isset($this->translations[$locale])) {
            $this->translations[$locale] = [];
        }

        $this->translations[$locale] = array_merge($this->translations[$locale], $translations);
    }

    public function loadFile(string $locale, string $file): void
    {
        $cacheKey = $locale . ':' . $file;

        if (in_array($cacheKey, $this->loadedFiles, true)) {
            return;
        }

        if (!file_exists($file)) {
            return;
        }

        $data = require $file;

        if (is_array($data)) {
            $this->addTranslations($locale, $this->flatten($data));
        }

        $this->loadedFiles[] = $cacheKey;
    }

    // ------------------------------------------------------------------

    private function loadLocale(string $locale): void
    {
        $path = $this->languagePath . DIRECTORY_SEPARATOR . $locale;
        // Load single-file locale (languages/de.php) if present
        $single = $this->languagePath . DIRECTORY_SEPARATOR . $locale . '.php';
        if (file_exists($single)) {
            $this->loadFile($locale, $single);
        }

        if (!is_dir($path)) {
            return;
        }

        $files = glob($path . '/*.php');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            $this->loadFile($locale, $file);
        }
    }

    private function flatten(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $fullKey = $prefix !== '' ? $prefix . '.' . $key : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flatten($value, $fullKey));
            } else {
                $result[$fullKey] = (string) $value;
            }
        }

        return $result;
    }
}
