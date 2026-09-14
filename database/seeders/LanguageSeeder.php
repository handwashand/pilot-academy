<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use App\Services\Translator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LanguageSeeder extends Seeder
{
    private const LANGUAGES = [
        'en' => ['name' => 'English', 'native_name' => 'English', 'position' => 1],
        'ru' => ['name' => 'Russian', 'native_name' => 'Русский', 'position' => 2],
        'es' => ['name' => 'Spanish', 'native_name' => 'Español', 'position' => 3],
        'fr' => ['name' => 'French', 'native_name' => 'Français', 'position' => 4],
        'pt' => ['name' => 'Portuguese (Brazil)', 'native_name' => 'Português (Brasil)', 'position' => 5],
    ];

    /*
     * TERMINOLOGY
     * English | Russian | Spanish | French | Portuguese (Brazil) | Why
     * Trainee | Стажёр | Aprendiz | Apprenant | Aprendiz | Person taking operational training; not a school student.
     * Trainer | Наставник | Mentor | Formateur | Instrutor | Person guiding learning; avoids gym/training connotations in Russian.
     * Dashboard | Панель | Panel | Tableau | Painel | Compact admin surface label.
     * Settings | Настройки | Ajustes | Paramètres | Configurações | Spanish uses Ajustes for product settings.
     */
    public function run(): void
    {
        $languages = [];

        foreach (self::LANGUAGES as $code => $data) {
            $languages[$code] = Language::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $data['name'],
                    'native_name' => $data['native_name'],
                    'is_active' => true,
                    'is_default' => false,
                    'direction' => 'ltr',
                    'position' => $data['position'],
                ],
            );
        }

        if (! Language::where('is_default', true)->exists()) {
            $languages['en']->forceFill(['is_default' => true, 'is_active' => true])->save();
        }

        foreach ($this->strings() as $key => $values) {
            foreach ($values as $code => $value) {
                Translation::firstOrCreate(
                    ['key' => $key, 'language_id' => $languages[$code]->id],
                    [
                        'value' => $value,
                        'module' => str($key)->before('.')->value(),
                    ],
                );
            }
        }

        app(Translator::class)->clearLanguageCaches();
        foreach (array_keys(self::LANGUAGES) as $code) {
            app(Translator::class)->clearBundleCache($code);
        }
    }

    /**
     * The interface strings to seed, by key and language. The text lives in
     * lang/{code}/*.php, which is what the site reads anyway — this only puts
     * those lines in the translations table, where they have always been.
     *
     * The student-site file (academy.php) is left out: a seeded row would
     * override the shipped line and hide every later correction made in it.
     * Its lines reach the Translations page as empty rows instead.
     *
     * @return array<string, array<string, string>>
     */
    private function strings(): array
    {
        $translator = app(Translator::class);
        $groups = array_diff(Translator::SHIPPED_GROUPS, ['academy']);
        $strings = [];

        foreach (array_keys(self::LANGUAGES) as $code) {
            foreach ($translator->shipped($code) as $key => $value) {
                if (in_array(Str::before($key, '.'), $groups, true)) {
                    $strings[$key][$code] = $value;
                }
            }
        }

        return $strings;
    }
}
