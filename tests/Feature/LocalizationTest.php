<?php

namespace Tests\Feature;

use App\Filament\Resources\Languages\LanguageResource;
use App\Filament\Resources\Translations\TranslationResource;
use App\Models\Course;
use App\Models\Language;
use App\Models\Translation;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_translation_fallback_chain_uses_default_then_humanised_key(): void
    {
        $this->seed(LanguageSeeder::class);

        $russian = Language::where('code', 'ru')->firstOrFail();
        Translation::where('key', 'nav.help')->where('language_id', $russian->id)->update(['value' => '']);

        $this->assertSame('Help', __t('nav.help', [], 'ru'));
        $this->assertSame('Add', __t('course.add', [], 'ru'));
    }

    public function test_locale_switch_rejects_unknown_codes_and_saves_session_and_user_locale(): void
    {
        $this->seed(LanguageSeeder::class);

        $user = User::factory()->create(['role' => User::ROLE_LEARNER]);

        $this->actingAs($user)
            ->from('/login')
            ->post(route('locale.switch'), ['locale' => 'ru'])
            ->assertRedirect('/login');

        $this->assertSame('ru', session('locale'));
        $this->assertSame('ru', $user->fresh()->locale);

        $this->post(route('locale.switch'), ['locale' => 'de'])
            ->assertSessionHasErrors('locale');
    }

    public function test_accept_language_is_used_when_session_and_user_do_not_choose(): void
    {
        $this->seed(LanguageSeeder::class);

        $this->withHeader('Accept-Language', 'ru-RU,ru;q=0.9,en;q=0.8')
            ->get(route('login'))
            ->assertOk()
            ->assertSee('Войти');
    }

    public function test_content_translation_falls_back_and_blank_deletes_translation(): void
    {
        $this->seed(LanguageSeeder::class);

        $course = Course::create([
            'title' => 'Pilot quick start',
            'slug' => 'pilot-quick-start',
            'description' => 'English description',
        ]);

        $this->assertSame('Pilot quick start', $course->translated('title', 'ru'));

        $course->setTranslation('title', 'ru', 'Быстрый старт Pilot');
        $this->assertSame('Быстрый старт Pilot', $course->fresh()->translated('title', 'ru'));

        $course->setTranslation('title', 'ru', '');
        $this->assertSame('Pilot quick start', $course->fresh()->translated('title', 'ru'));
        $this->assertDatabaseMissing('content_translations', ['field' => 'title']);
    }

    public function test_language_seeder_never_overwrites_edited_translations(): void
    {
        $this->seed(LanguageSeeder::class);

        $translation = Translation::where('key', 'nav.help')
            ->whereHas('language', fn ($query) => $query->where('code', 'es'))
            ->firstOrFail();

        $translation->update(['value' => 'Soporte']);

        $this->seed(LanguageSeeder::class);

        $this->assertSame('Soporte', $translation->fresh()->value);
    }

    public function test_language_seeder_includes_brazilian_portuguese_for_every_key(): void
    {
        $this->seed(LanguageSeeder::class);

        $this->assertDatabaseHas('languages', [
            'code' => 'pt',
            'name' => 'Portuguese (Brazil)',
            'native_name' => 'Português (Brasil)',
            'direction' => 'ltr',
        ]);

        $keyCount = Translation::query()->distinct('key')->count('key');

        foreach (Language::pluck('code') as $code) {
            $this->assertSame(
                $keyCount,
                Translation::whereHas('language', fn ($query) => $query->where('code', $code))->count(),
                "Every seeded key should exist for {$code}."
            );
        }

        $this->assertSame('Entrar', __t('auth.login', [], 'pt'));
    }

    public function test_localization_resources_require_explicit_permissions(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin);
        $this->assertFalse(LanguageResource::canAccess());
        $this->assertFalse(TranslationResource::canAccess());

        $admin->permissions()->create(['permission' => User::PERMISSION_LANGUAGES_MANAGE]);
        $admin->permissions()->create(['permission' => User::PERMISSION_TRANSLATIONS_MANAGE]);

        $this->assertTrue(LanguageResource::canAccess());
        $this->assertTrue(TranslationResource::canAccess());
    }
}
