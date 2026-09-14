<?php

namespace App\Http\Middleware;

use App\Services\Translator;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $translator = app(Translator::class);
        $locale = null;

        if ($translator->isActiveCode($request->user()?->locale)) {
            $locale = $request->user()->locale;
        } elseif ($translator->isActiveCode($request->session()->get('locale'))) {
            $locale = $request->session()->get('locale');
        } else {
            foreach ($request->getLanguages() as $browserLocale) {
                $code = str($browserLocale)->before('-')->lower()->value();
                if ($translator->isActiveCode($code)) {
                    $locale = $code;
                    break;
                }
            }
        }

        $locale ??= $translator->defaultCode();
        App::setLocale($locale);

        return $next($request);
    }
}
