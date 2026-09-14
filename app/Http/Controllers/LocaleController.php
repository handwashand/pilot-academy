<?php

namespace App\Http\Controllers;

use App\Services\Translator;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LocaleController extends Controller
{
    public function __invoke(Request $request)
    {
        $code = (string) $request->input('locale');

        if (! app(Translator::class)->isActiveCode($code)) {
            throw ValidationException::withMessages([
                'locale' => __t('locale.invalid'),
            ]);
        }

        $request->session()->put('locale', $code);

        if ($user = $request->user()) {
            $user->forceFill(['locale' => $code])->save();
        }

        return back();
    }
}
