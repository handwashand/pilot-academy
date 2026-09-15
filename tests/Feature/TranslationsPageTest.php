<?php

namespace Tests\Feature;

use App\Filament\Resources\Translations\Pages\EditTranslation;
use App\Filament\Resources\Translations\Pages\ListTranslations;
use App\Filament\Resources\Translations\TranslationResource;
use App\Models\Language;
use App\Models\Translation;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
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

    public function test_every_admin_finds_translations_in_the_sidebar(): void
    {
        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get('/admin')
            ->assertOk()
            ->assertSee(TranslationResource::getUrl('index'), false);

        $this->get(TranslationResource::getUrl('index'))->assertOk();
    }

    public function test_creators_do_not_get_it(): void
    {
        $this->actingAs($this->user(User::ROLE_CREATOR))
            ->get(TranslationResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_the_page_lists_the_student_site_text_in_every_language_and_finds_it_by_its_words(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);

        Livewire::actingAs($admin)->test(ListTranslations::class)->assertOk();

        $this->assertSame(Language::count(), Translation::where('key', 'academy.home.hero_title')->count());

        $russian = $this->row('academy.home.hero_title', 'ru');
        $this->assertNull($russian->value, 'Listed, not changed: the shipped text still applies.');

        Livewire::actingAs($admin)
            ->test(ListTranslations::class)
            ->searchTable('Добро пожаловать в Pilot Academy')
            ->assertCanSeeTableRecords([$russian])
            ->assertCanNotSeeTableRecords([$this->row('academy.home.start_learning', 'ru')]);
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
