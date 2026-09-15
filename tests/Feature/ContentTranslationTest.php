<?php

namespace Tests\Feature;

use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Models\Course;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\PilotQuickStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Course and lesson text in other languages: written with Translate on the
 * edit page, shown to students in their language, English where none exists.
 */
class ContentTranslationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LanguageSeeder::class);
        $this->seed(PilotQuickStartSeeder::class);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Pilot Admin',
            'email' => 'admin@pilot.local',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_an_admin_translates_a_course_and_a_lesson_from_their_edit_pages(): void
    {
        $course = Course::first();
        $lesson = $course->lessons()->first();
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->callAction('translateContent', data: ['ru' => ['title' => 'Быстрый старт Pilot', 'description' => 'Всё, что нужно для начала работы.']])
            ->assertHasNoActionErrors();

        Livewire::actingAs($admin)
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->callAction('translateContent', data: ['es' => ['title' => 'Primeros pasos', 'content' => '<p>Texto de la lección.</p>']])
            ->assertHasNoActionErrors();

        $this->assertSame('Быстрый старт Pilot', $course->fresh()->translated('title', 'ru'));
        $this->assertSame('Primeros pasos', $lesson->fresh()->translated('title', 'es'));
        $this->assertSame($course->title, $course->fresh()->translated('title', 'fr'), 'No French written: English shows.');
    }

    public function test_students_see_course_and_lesson_text_in_their_language(): void
    {
        $course = Course::first();
        $lesson = $course->lessons()->first();
        $course->setTranslation('title', 'ru', 'Быстрый старт Pilot');
        $lesson->setTranslation('title', 'ru', 'Начало работы с Pilot');
        $lesson->setTranslation('content', 'ru', '<p>Текст урока на русском.</p>');

        $this->withHeader('Accept-Language', 'ru')->get(route('academy.home'))
            ->assertOk()->assertSee('Быстрый старт Pilot')->assertSee('Начало работы с Pilot');

        $this->withHeader('Accept-Language', 'ru')->get(route('academy.lesson', [$course, $lesson]))
            ->assertOk()->assertSee('Начало работы с Pilot')->assertSee('Текст урока на русском.', false);

        $this->withHeader('Accept-Language', 'en')->get(route('academy.course', $course))
            ->assertOk()->assertSee($course->title)->assertDontSee('Быстрый старт Pilot');
    }

    public function test_a_course_written_in_french_is_translated_into_english_and_russian(): void
    {
        $course = Course::create([
            'title' => 'Démarrage rapide',
            'slug' => 'demarrage-rapide',
            'language' => 'fr',
            'level' => 'beginner',
            'status' => Course::STATUS_PUBLISHED,
        ]);

        Livewire::actingAs($this->admin())
            ->test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->assertFormSet(['language' => 'fr'])
            ->callAction('translateContent', data: ['en' => ['title' => 'Quick start'], 'ru' => ['title' => 'Быстрый старт']])
            ->assertHasNoActionErrors();

        $course = $course->fresh();
        $this->assertSame('Quick start', $course->translated('title', 'en'), 'English is a translation of a French course.');
        $this->assertSame('Быстрый старт', $course->translated('title', 'ru'));
        $this->assertSame('Démarrage rapide', $course->translated('title', 'fr'), 'French readers get the original.');

        // A "French translation" of a French course is not a thing.
        $course->setTranslation('title', 'fr', 'Autre titre');
        $this->assertSame('Démarrage rapide', $course->fresh()->translated('title', 'fr'));
        $this->assertDatabaseMissing('content_translations', ['value' => 'Autre titre']);
    }

    public function test_a_lesson_written_inside_a_course_takes_its_language(): void
    {
        $course = Course::create(['title' => 'Курс', 'slug' => 'kurs', 'language' => 'ru', 'level' => 'beginner']);
        $lesson = $course->lessons()->create(['title' => 'Урок', 'slug' => 'urok', 'content' => '<p>Текст.</p>']);

        $this->assertSame('ru', $lesson->fresh()->language);
    }

    public function test_search_finds_a_course_by_its_translation(): void
    {
        $course = Course::first();
        // Lower case on purpose: SQLite only lower-cases ASCII in tests.
        $course->setTranslation('title', 'ru', 'быстрый старт пилот');

        $this->withHeader('Accept-Language', 'ru')
            ->get(route('academy.search', ['q' => 'старт пилот']))
            ->assertOk()
            ->assertSee('быстрый старт пилот');
    }

    public function test_emptying_a_translation_brings_the_english_back(): void
    {
        $course = Course::first();
        $course->setTranslation('title', 'pt', 'Início rápido');

        Livewire::actingAs($this->admin())
            ->test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->assertActionVisible('translateContent')
            ->callAction('translateContent', data: ['pt' => ['title' => '']])
            ->assertHasNoActionErrors();

        $this->assertSame($course->title, $course->fresh()->translated('title', 'pt'));
    }
}
