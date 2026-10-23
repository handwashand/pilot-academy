<?php

namespace Tests\Feature;

use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\Pages\ListCourses;
use App\Filament\Resources\Courses\RelationManagers\FinalQuestionsRelationManager;
use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Filament\Resources\Lessons\Pages\ListLessons;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Course reviewers check translations before a course goes out, in the admin
 * panel. An admin whose panel is in Spanish sees — and corrects — the Spanish;
 * the English original is what the panel shows in English, and a save in
 * Spanish never writes over it.
 */
class AdminReadingLanguageTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    private Lesson $lesson;

    private Question $question;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LanguageSeeder::class);

        $this->course = Course::create([
            'title' => 'Fuel monitoring',
            'slug' => 'fuel-monitoring',
            'description' => 'How fuel sensors work.',
            'level' => 'beginner',
            'language' => 'en',
            'status' => Course::STATUS_DRAFT,
        ]);
        $this->course->setTranslation('title', 'es', 'Control de combustible');

        $this->lesson = $this->course->lessons()->create([
            'title' => 'Adding a fuel sensor',
            'slug' => 'adding-a-fuel-sensor',
            'summary' => 'Connect the sensor.',
            'content' => '<p>Open the unit settings.</p>',
        ]);
        $this->lesson->setTranslation('title', 'es', 'Añadir un sensor de combustible');
        $this->lesson->setTranslation('content', 'es', '<p>Abra la configuración de la unidad.</p>');

        $this->question = $this->lesson->questions()->create(['prompt' => 'Where is the sensor added?', 'type' => 'single', 'sort_order' => 1]);
        $this->question->setTranslation('prompt', 'es', '¿Dónde se añade el sensor?');
        $right = $this->question->options()->create(['text' => 'Unit settings', 'is_correct' => true, 'sort_order' => 1]);
        $right->setTranslation('text', 'es', 'Configuración de la unidad');
        $this->question->options()->create(['text' => 'Reports', 'is_correct' => false, 'sort_order' => 2]);
    }

    private function admin(string $locale): User
    {
        app()->setLocale($locale); // Livewire::test() runs no SetLocale middleware.

        return User::create([
            'name' => 'Course Reviewer',
            'email' => "reviewer-{$locale}@pilot.test",
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'locale' => $locale,
        ]);
    }

    public function test_a_spanish_reviewer_sees_the_course_in_spanish(): void
    {
        Livewire::actingAs($this->admin('es'))
            ->test(EditCourse::class, ['record' => $this->course->getRouteKey()])
            ->assertSchemaStateSet(['title' => 'Control de combustible'])
            // No Spanish description yet: what a Spanish student would read.
            ->assertSchemaStateSet(['description' => 'How fuel sensors work.'])
            ->assertSee(__t('admin_common.reading.notice', ['language' => 'Español', 'original' => 'English']));
    }

    public function test_saving_in_spanish_changes_the_spanish_and_never_the_english(): void
    {
        Livewire::actingAs($this->admin('es'))
            ->test(EditCourse::class, ['record' => $this->course->getRouteKey()])
            ->fillForm(['title' => 'Control del combustible'])
            ->call('save')
            ->assertHasNoFormErrors();

        $course = $this->course->fresh();
        $this->assertSame('Fuel monitoring', $course->title);
        $this->assertSame('Control del combustible', $course->translated('title', 'es'));
        // Left as the English it showed: not copied in as "Spanish".
        $this->assertSame(0, $course->contentTranslations()->where('field', 'description')->count());
    }

    public function test_an_english_reviewer_sees_and_edits_the_original(): void
    {
        Livewire::actingAs($this->admin('en'))
            ->test(EditCourse::class, ['record' => $this->course->getRouteKey()])
            ->assertSchemaStateSet(['title' => 'Fuel monitoring'])
            ->assertDontSee('Shown in', false)
            ->fillForm(['title' => 'Fuel monitoring basics'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Fuel monitoring basics', $this->course->fresh()->title);
        $this->assertSame('Control de combustible', $this->course->fresh()->translated('title', 'es'));
    }

    public function test_the_lists_show_spanish_titles_and_find_them(): void
    {
        $admin = $this->admin('es');

        Livewire::actingAs($admin)->test(ListCourses::class)
            ->assertSee('Control de combustible')
            ->assertDontSee('Fuel monitoring')
            ->searchTable('control de')
            ->assertCanSeeTableRecords([$this->course]);

        Livewire::actingAs($admin)->test(ListLessons::class)
            ->assertSee('Añadir un sensor de combustible')
            ->searchTable('añadir')
            ->assertCanSeeTableRecords([$this->lesson]);
    }

    public function test_a_lessons_text_and_quiz_are_shown_and_saved_in_spanish(): void
    {
        $page = Livewire::actingAs($this->admin('es'))
            ->test(EditLesson::class, ['record' => $this->lesson->getRouteKey()])
            ->assertSchemaStateSet(['title' => 'Añadir un sensor de combustible'])
            ->assertSee('¿Dónde se añade el sensor?');

        $questions = $page->get('data.questions');
        $key = array_key_first($questions);
        // An answer with a translation in Spanish; one without, as written.
        $this->assertSame(['Configuración de la unidad', 'Reports'], array_values(array_column($questions[$key]['options'], 'text')));
        $questions[$key]['prompt'] = '¿Dónde se añade un sensor?';

        $page->set('data.questions', $questions)
            ->fillForm(['title' => 'Añadir el sensor de combustible'])
            ->call('save')
            ->assertHasNoFormErrors();

        $lesson = $this->lesson->fresh();
        $this->assertSame('Adding a fuel sensor', $lesson->title);
        $this->assertSame('<p>Open the unit settings.</p>', $lesson->content);
        $this->assertSame('Añadir el sensor de combustible', $lesson->translated('title', 'es'));

        $question = $this->question->fresh();
        $this->assertSame('Where is the sensor added?', $question->prompt);
        $this->assertSame('¿Dónde se añade un sensor?', $question->translated('prompt', 'es'));
        $this->assertSame(['Unit settings', 'Reports'], $question->options()->orderBy('sort_order')->pluck('text')->all());
        $this->assertSame(2, $question->options()->count());
    }

    public function test_untranslated_lesson_text_is_not_copied_in_as_spanish_on_save(): void
    {
        $this->lesson->setTranslation('content', 'es', null);
        $this->lesson->update(['content' => '<h2>Before you start</h2><ul><li><p>Open <strong>Units</strong>.</p></li></ul>']);

        Livewire::actingAs($this->admin('es'))
            ->test(EditLesson::class, ['record' => $this->lesson->getRouteKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        // The rich editor rewrites HTML as it loads it; that is not a translation.
        $this->assertSame(0, $this->lesson->contentTranslations()->whereIn('field', ['content', 'summary', 'transcript'])->count());
        $this->assertSame('Añadir un sensor de combustible', $this->lesson->fresh()->translated('title', 'es'));
    }

    public function test_questions_cannot_be_added_or_removed_while_reading_a_translation(): void
    {
        Livewire::actingAs($this->admin('es'))
            ->test(EditLesson::class, ['record' => $this->lesson->getRouteKey()])
            ->assertDontSee(__t('admin_lessons.form.add_question'));

        // The control: in the original, questions can be added.
        Livewire::actingAs($this->admin('en'))
            ->test(EditLesson::class, ['record' => $this->lesson->getRouteKey()])
            ->assertSee(__t('admin_lessons.form.add_question'));
    }

    public function test_a_final_question_is_edited_in_spanish(): void
    {
        $this->course->finalQuestions()->attach($this->question->id);

        Livewire::actingAs($this->admin('es'))
            ->test(FinalQuestionsRelationManager::class, ['ownerRecord' => $this->course, 'pageClass' => EditCourse::class])
            ->assertSee('¿Dónde se añade el sensor?')
            ->callAction(TestAction::make('edit')->table($this->question), ['prompt' => '¿Dónde se añade el sensor de combustible?'])
            ->assertHasNoFormErrors();

        $question = $this->question->fresh();
        $this->assertSame('Where is the sensor added?', $question->prompt);
        $this->assertSame('¿Dónde se añade el sensor de combustible?', $question->translated('prompt', 'es'));
    }
}
