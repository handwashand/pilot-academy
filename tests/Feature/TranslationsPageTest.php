<?php

namespace Tests\Feature;

use App\Filament\Resources\Translations\Pages\EditTranslation;
use App\Filament\Resources\Translations\Pages\ListTranslations;
use App\Filament\Resources\Translations\TranslationResource;
use App\Models\Language;
use App\Models\Translation;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Settings → Translations: any admin corrects the wording students see.
 */
class TranslationsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LanguageSeeder::class);
    }

    private function user(string $role): User
    {
        return User::create([
            'name' => ucfirst($role),
            'email' => "{$role}@pilot.local",
            'password' => 'password',
            'role' => $role,
        ]);
    }

    private function row(string $key, string $code): Translation
    {
        return Translation::where('key', $key)
            ->whereHas('language', fn ($query) => $query->where('code', $code))
            ->firstOrFail();
    }

    /**
     * Translations belongs to the Settings cluster now
     * (App\Filament\Clusters\Settings) — see SettingsClusterTest — so it no
     * longer has its own row in the main sidebar, only a tab inside Settings.
     */
    public function test_every_admin_finds_translations_in_the_sidebar(): void
    {
        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get('/admin')
            ->assertOk()
            ->assertDontSee(TranslationResource::getUrl('index'), false);

        $this->get(TranslationResource::getUrl('index'))->assertOk();
    }

    public function test_creators_do_not_get_it(): void
    {
        $this->actingAs($this->user(User::ROLE_CREATOR))
            ->get(TranslationResource::getUrl('index'))
            ->assertForbidden();
    }

    /**
     * One row per key, with every language beside it, and a search that finds
     * the row by words seen in any of them.
     */
    public function test_a_key_is_one_row_with_a_column_for_every_language(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);

        Livewire::actingAs($admin)->test(ListTranslations::class)->assertOk();

        $this->assertSame(Language::count(), Translation::where('key', 'academy.home.hero_title')->count());

        $russian = $this->row('academy.home.hero_title', 'ru');
        $this->assertNull($russian->value, 'Listed, not changed: the shipped text still applies.');

        // The row stands for the key, whichever language it was written in.
        $anchor = Translation::where('key', 'academy.home.hero_title')->orderBy('id')->first();
        $other = Translation::where('key', 'academy.home.start_learning')->orderBy('id')->first();

        Livewire::actingAs($admin)
            ->test(ListTranslations::class)
            // Russian words, and the row they belong to is the key's one row.
            ->searchTable('Добро пожаловать в Pilot Academy')
            ->assertCanSeeTableRecords([$anchor])
            ->assertCanNotSeeTableRecords([$other])
            // Every active language is a column of its own.
            ->assertSee('Русский')
            ->assertSee('Español')
            // And each column shows that language's line.
            ->assertSee('Добро пожаловать в Pilot Academy');
    }

    /** Clicking a language's cell corrects that language, not the row's own. */
    public function test_a_cell_corrects_the_language_of_its_column(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        Livewire::actingAs($admin)->test(ListTranslations::class);

        $anchor = Translation::where('key', 'academy.home.hero_title')->orderBy('id')->first();
        $spanishCode = 'es';

        Livewire::actingAs($admin)
            ->test(ListTranslations::class)
            ->callAction(
                TestAction::make("correct_{$spanishCode}")->table($anchor),
                ['value' => 'Bienvenida a la academia'],
            )
            ->assertHasNoActionErrors();

        $this->assertSame('Bienvenida a la academia', $this->row('academy.home.hero_title', 'es')->fresh()->value);
        $this->assertNull($this->row('academy.home.hero_title', 'ru')->fresh()->value, 'Only the column clicked changes.');
        $this->assertSame('Bienvenida a la academia', __t('academy.home.hero_title', [], 'es'));
    }

    public function test_a_correction_reaches_students_and_clearing_it_restores_the_shipped_text(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        Livewire::actingAs($admin)->test(ListTranslations::class);
        $spanish = $this->row('academy.home.hero_title', 'es');

        Livewire::actingAs($admin)
            ->test(EditTranslation::class, ['record' => $spanish->getRouteKey()])
            ->fillForm(['value' => 'Bienvenida a la academia'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Bienvenida a la academia', __t('academy.home.hero_title', [], 'es'));

        auth()->logout();
        $this->withHeader('Accept-Language', 'es')
            ->get(route('academy.home'))
            ->assertSee('Bienvenida a la academia');

        Livewire::actingAs($admin)
            ->test(EditTranslation::class, ['record' => $spanish->getRouteKey()])
            ->fillForm(['value' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($spanish->fresh()->value);
        $this->assertSame('Te damos la bienvenida a Pilot Academy', __t('academy.home.hero_title', [], 'es'));
    }
}
