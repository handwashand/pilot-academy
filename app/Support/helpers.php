<?php

use App\Services\Translator;

if (! function_exists('__t')) {
    function __t(string $key, array $replace = [], ?string $locale = null): string
    {
        return app(Translator::class)->translate($key, $replace, $locale);
    }
}
