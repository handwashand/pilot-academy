<?php

namespace App\Services;

use App\Models\Language;
use App\Models\Translation;
use Closure;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Translator
{
    /** Files in lang/{code}/ that __t() reads, and admins may correct. */
    public const SHIPPED_GROUPS = ['academy', 'nav', 'footer', 'auth', 'field', 'locale', 'help', 'guide', 'admin', 'core', 'mail', 'labels', 'admin_nav', 'admin_common', 'admin_courses', 'admin_lessons', 'admin_people', 'admin_library', 'admin_results', 'admin_settings', 'admin_pages', 'admin_widgets'];

    private array $bundles = [];

    /** @var array<string, array<string, string>> */
    private array $shipped = [];

    /**
     * The lines shipped for one language, as "academy.home.hero_title" => text.
     *
     * @return array<string, string>
     */
    public function shipped(string $code): array
    {
        if (isset($this->shipped[$code])) {
            return $this->shipped[$code];
        }

        $lines = [];

        foreach (self::SHIPPED_GROUPS as $group) {
            $path = lang_path("{$code}/{$group}.php");

            if (! is_file($path)) {
                continue;
            }

            foreach (Arr::dot(require $path) as $key => $line) {
                if (is_string($line)) {
                    $lines["{$group}.{$key}"] = $line;
                }
            }
        }

        return $this->shipped[$code] = $lines;
    }

    public function translate(string $key, array $replace = [], ?string $locale = null): string
    {
        return $this->replace($this->line($key, $locale), $replace);
    }

    /**
     * A line with plural forms, separated by `|` ("урок|урока|уроков"). The
     * form is picked by Laravel's own rules for the language, so Russian gets
     * three forms and English two. `:count` is filled in.
     */
    public function choice(string $key, int $count, array $replace = [], ?string $locale = null): string
    {
        $locale ??= App::getLocale();

        $line = app('translator')->getSelector()->choose($this->line($key, $locale), $count, $locale);

        return $this->replace($line, ['count' => $count, ...$replace]);
    }

    /**
     * The raw line, first match wins:
     * - text saved for this language in the translations table, where admins
     *   override;
     * - the text shipped in lang/{code}/*.php;
     * - the same two for the default language;
     * - the key made readable.
     *
     * Shipped text means a page reads correctly straight after `git pull`.
     * The deploy runs no seeders.
     */
    private function line(string $key, ?string $locale): string
    {
        $locale ??= App::getLocale();

        foreach (array_unique([$locale, $this->defaultCode()]) as $code) {
            $saved = $this->bundle($code)[$key] ?? null;

            if (filled($saved)) {
                return (string) $saved;
            }

            if (Lang::hasForLocale($key, $code)) {
                $shipped = Lang::get($key, [], $code, false);

                if (is_string($shipped) && filled($shipped)) {
                    return $shipped;
                }
            }
        }

        return Str::headline(Str::afterLast($key, '.'));
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

        return collect($languages)->map(fn (array $attributes): Language => (new Language)->newFromBuilder($attributes));
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

    /**
     * The language to write to someone in, when they are not the one making the
     * request — an email, a certificate, a bell alert. Their own choice if that
     * language is switched on, otherwise the default.
     */
    public function localeFor(?HasLocalePreference $person): string
    {
        $code = $person?->preferredLocale();

        return $this->isActiveCode($code) ? $code : $this->defaultCode();
    }

    /**
     * Run $callback with the app in $code, then put the request's language back
     * — even when the callback throws.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function inLocale(string $code, Closure $callback): mixed
    {
        $previous = App::getLocale();

        App::setLocale($code);

        try {
            return $callback();
        } finally {
            App::setLocale($previous);
        }
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
