<?php

namespace App\Filament\Resources\Concerns;

use Illuminate\Support\Str;

/**
 * Headings in the reader's language, capitalised the way that language does.
 *
 * Filament Title-Cases record names in headings — "Create Course", "Quiz
 * Attempts" — which is English style. In Russian or French it reads wrong
 * ("Попытки Тестов", "Avis Des Apprenants"). Here a list heading capitalises
 * only its first letter, and a record name inside a heading stays lower case
 * ("Создать курс", "Create course").
 */
trait HasSentenceCaseLabels
{
    public static function getTitleCaseModelLabel(): string
    {
        return static::getModelLabel();
    }

    public static function getTitleCasePluralModelLabel(): string
    {
        return Str::ucfirst(static::getPluralModelLabel());
    }
}
