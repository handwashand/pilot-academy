<?php

namespace App\Services;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Translator
{
    private array $bundles = [];

    public function translate(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale ??= App::getLocale();
        $default = $this->defaultCode();

        $value = $this->bundle($locale)[$key] ?? null;

        if (blank($value) && $locale !== $default) {
            $value = $this->bundle($default)[$key] ?? null;
        }

        if (blank($value)) {
            $value = Str::headline(Str::afterLast($key, '.'));
        }

        return $this->replace((string) $value, $replace);
    }

    public function bundle(?string $locale = null): array
    {
        $locale ??= App::getLocale();

        if (isset($this->bundles[$locale])) {
            return $this->bundles[$locale];
        }

        if (! $this->tablesReady()) {
            return $this->bundles[$locale] = [];
        }

        return $this->bundles[$locale] = Cache::remember(
            "translations.bundle.{$locale}",
            now()->addDay(),
            fn (): array => Translation::query()
                ->whereHas('language', fn ($query) => $query->where('code', $locale)->where('is_active', true))
                ->whereNotNull('value')
                ->pluck('value', 'key')
                ->filter(fn ($value): bool => filled($value))
                ->all(),
        );
    }

    public function activeLanguages()
    {
        if (! $this->tablesReady()) {
            return collect();
        }

        $languages = Cache::remember('translations.languages.active', now()->addDay(), fn (): array => Language::active()
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->map(fn (Language $language): array => $language->getAttributes())
            ->all());

        return collect($languages)->map(fn (array $attributes): Language => (new Language())->newFromBuilder($attributes));
    }

    public function defaultCode(): string
    {
        if (! $this->tablesReady()) {
            return config('app.locale', 'en');
        }

        return Cache::remember('translations.languages.default', now()->addDay(), fn (): string => Language::query()
            ->where('is_default', true)
            ->value('code') ?: config('app.locale', 'en'));
    }

    public function activeLanguage(?string $code = null): ?Language
    {
        $code ??= App::getLocale();

        return $this->activeLanguages()->firstWhere('code', $code);
    }

    public function isActiveCode(?string $code): bool
    {
        return filled($code) && $this->activeLanguages()->contains('code', $code);
    }

    public function direction(?string $code = null): string
    {
        return $this->activeLanguage($code)?->direction ?: 'ltr';
    }

    public function clearBundleCache(?string $code): bool
    {
        if ($code) {
            Cache::forget("translations.bundle.{$code}");
            unset($this->bundles[$code]);
        }

        return true;
    }

    public function clearLanguageCaches(): bool
    {
        Cache::forget('translations.languages.active');
        Cache::forget('translations.languages.default');
        $this->bundles = [];

        return true;
    }

    private function tablesReady(): bool
    {
        try {
            return Schema::hasTable('languages') && Schema::hasTable('translations');
        } catch (\Throwable) {
            return false;
        }
    }

    private function replace(string $value, array $replace): string
    {
        foreach ($replace as $key => $replacement) {
            $value = str_replace(
                [':'.$key, ':'.Str::upper($key), ':'.Str::ucfirst($key)],
                [$replacement, Str::upper((string) $replacement), Str::ucfirst((string) $replacement)],
                $value,
            );
        }

        return $value;
    }
}
