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
