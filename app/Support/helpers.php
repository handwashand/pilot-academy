<?php

use App\Services\Translator;

if (! function_exists('__t')) {
    function __t(string $key, array $replace = [], ?string $locale = null): string
    {
        return app(Translator::class)->translate($key, $replace, $locale);
    }
}

if (! function_exists('__tc')) {
    /** __t() for a line with plural forms: `__tc('academy.common.lessons', 3)`. */
    function __tc(string $key, int $count, array $replace = [], ?string $locale = null): string
    {
        return app(Translator::class)->choice($key, $count, $replace, $locale);
    }
}
