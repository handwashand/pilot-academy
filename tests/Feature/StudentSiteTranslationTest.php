<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Language;
use App\Models\Translation;
use App\Services\Translator;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\PilotQuickStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

/**
 * The student site's words ship in lang/{code}/academy.php, so every page reads
 * in the visitor's language straight after a deploy, with no seeding.
 */
class StudentSiteTranslationTest extends TestCase
{
    use RefreshDatabase;

    private const OTHER_LANGUAGES = ['ru', 'es', 'fr', 'pt'];

    public function test_every_language_has_exactly_the_english_keys_and_placeholders(): void
    {
        $translator = app(Translator::class);
        $english = $translator->shipped('en');

        $this->assertArrayHasKey('nav.help', $english, 'The header and sign-in strings ship with the code too.');

        foreach (self::OTHER_LANGUAGES as $code) {
            $other = $translator->shipped($code);

            $this->assertSame([], array_values(array_diff(array_keys($english), array_keys($other))), "Missing from {$code}.");
            $this->assertSame([], array_values(array_diff(array_keys($other), array_keys($english))), "Not in English, but in {$code}.");

            foreach ($english as $key => $line) {
                $this->assertEqualsCanonicalizing(
                    $this->placeholders($line),
                    $this->placeholders($other[$key]),
                    "The placeholders in {$code} {$key} differ from English.",
                );
            }
        }
    }

    public function test_every_academy_key_the_code_uses_exists(): void
    {
        $files = array_merge(
            File::allFiles(resource_path('views')),
            File::allFiles(app_path()),
        );

        $used = [];

        foreach ($files as $file) {
            // Every key, not only the student site's: a key that exists only in
            // the database reads as its own name on a server nobody seeded.
            preg_match_all("/__tc?\\('([a-z_]+\\.[a-z0-9_.]+)'/", $file->getContents(), $matches);
            $used = [...$used, ...$matches[1]];
        }

        // A key built at runtime ("academy.common.level.".$level) ends in a dot.
        $used = array_filter(array_unique($used), fn (string $key): bool => ! str_ends_with($key, '.'));

        $this->assertNotEmpty($used);

        foreach ($used as $key) {
            $this->assertTrue(Lang::hasForLocale($key, 'en'), "{$key} is used but not shipped in lang/en/.");
        }
    }

    public function test_english_reads_properly_without_any_seeded_translations(): void
    {
        $this->seed(PilotQuickStartSeeder::class);

        // No LanguageSeeder: exactly what a server sees before anyone seeds.
        $this->get(route('academy.home'))
            ->assertOk()
            ->assertSee('Welcome to Pilot Academy')
            ->assertSee('Start learning')
            ->assertDontSee('Hero Title');
    }

    public function test_the_student_site_reads_in_the_visitors_language(): void
    {
        $this->seed(PilotQuickStartSeeder::class);
        $this->seed(LanguageSeeder::class);

        $course = Course::first();

        $this->withHeader('Accept-Language', 'ru-RU,ru;q=0.9')
            ->get(route('academy.home'))
            ->assertOk()
            ->assertSee('Добро пожаловать в Pilot Academy')
            ->assertSee('Начать обучение')
            ->assertDontSee('Start learning');

        $this->withHeader('Accept-Language', 'es')
            ->get(route('academy.course', $course))
            ->assertOk()
            ->assertSee('Todos los cursos')
            ->assertDontSee('All courses');

        $this->withHeader('Accept-Language', 'pt-BR')
            ->get(route('academy.search', ['q' => 'zzz-nothing']))
            ->assertOk()
            ->assertSee('Nada encontrado.');
    }

    public function test_the_language_choice_is_in_the_footer_where_a_phone_can_reach_it(): void
    {
        $this->seed(LanguageSeeder::class);

        $this->get(route('academy.home'))
            ->assertOk()
            ->assertSeeInOrder(['<footer', 'name="locale" value="ru"', 'Русский', '</footer>'], false);
    }

    public function test_plural_forms_follow_the_language(): void
    {
        $this->assertSame('1 lesson', __tc('academy.common.lessons', 1, [], 'en'));
        $this->assertSame('2 lessons', __tc('academy.common.lessons', 2, [], 'en'));

        $this->assertSame('1 урок', __tc('academy.common.lessons', 1, [], 'ru'));
        $this->assertSame('3 урока', __tc('academy.common.lessons', 3, [], 'ru'));
        $this->assertSame('5 уроков', __tc('academy.common.lessons', 5, [], 'ru'));
        $this->assertSame('21 урок', __tc('academy.common.lessons', 21, [], 'ru'));
    }

    public function test_a_translation_saved_by_an_admin_overrides_the_shipped_text(): void
    {
        $this->seed(LanguageSeeder::class);

        (new Translation)->forceFill([
            'key' => 'academy.home.start_learning',
            'language_id' => Language::where('code', 'es')->value('id'),
            'value' => '¡Vamos!',
            'module' => 'academy',
        ])->save();

        app(Translator::class)->clearBundleCache('es');

        $this->assertSame('¡Vamos!', __t('academy.home.start_learning', [], 'es'));
        $this->assertSame('Commencer', __t('academy.home.start_learning', [], 'fr'));
    }

    /** @return list<string> */
    private function placeholders(string $line): array
    {
        preg_match_all('/:[a-z_]+/', $line, $matches);

        return array_values(array_unique($matches[0]));
    }
}
