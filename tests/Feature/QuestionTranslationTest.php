<?php

namespace Tests\Feature;

use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\RelationManagers\FinalQuestionsRelationManager;
use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Models\AiProvider;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\PilotQuickStartSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A quiz question — and its answer options — translate the same way a
 * course's or lesson's own text does: Question::translatable = ['prompt'],
 * Option::translatable = ['text'], both through HasContentTranslations.
 * TranslateQuestionsAction puts that behind a "Translate quiz" button on a
 * lesson's edit page (its own knowledge check) and on a course's final-quiz
 * bank (App\Filament\Actions\TranslateQuestionsAction).
 */
class QuestionTranslationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
        $this->seed(PilotQuickStartSeeder::class);
    }

    private function admin(string ...$rights): User
    {
        $admin = User::firstOrCreate(['email' => 'admin@pilot.local'], [
            'name' => 'Pilot Admin',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        foreach ($rights as $right) {
            $admin->permissions()->firstOrCreate(['permission' => $right]);
        }

        return $admin;
    }

    /** A lesson of its own, with no seeded questions, so "the" question is unambiguous. */
    private function lessonWithQuestion(): Lesson
    {
        $course = Course::first();
        $lesson = $course->lessons()->create([
            'title' => 'A lesson for translation tests',
            'slug' => 'translation-test-lesson',
            'content' => '<p>Body.</p>',
        ]);

        $question = $lesson->questions()->create(['prompt' => 'Which one?', 'type' => 'single', 'sort_order' => 1]);
        $question->options()->create(['text' => 'Right', 'is_correct' => true, 'sort_order' => 1]);
        $question->options()->create(['text' => 'Wrong', 'is_correct' => false, 'sort_order' => 2]);

        return $lesson;
    }

    // --- The model itself ---------------------------------------------------

    public function test_a_lessons_own_question_takes_the_lessons_language(): void
    {
        $lesson = Course::first()->lessons()->first();
        $lesson->update(['language' => 'fr']);

        $question = $lesson->questions()->create(['prompt' => 'Lequel ?', 'type' => 'single', 'sort_order' => 1]);

        $this->assertSame('fr', $question->contentLanguageCode());
    }

    public function test_a_course_only_question_takes_the_sites_default_language(): void
    {
        $question = Question::create(['prompt' => 'Written straight into the final', 'sort_order' => 99]);

        $this->assertSame('en', $question->contentLanguageCode());
    }

    public function test_an_option_takes_its_questions_language_with_no_column_of_its_own(): void
    {
        $lesson = Course::first()->lessons()->first();
        $lesson->update(['language' => 'fr']);
        $question = $lesson->questions()->create(['prompt' => 'Lequel ?', 'type' => 'single', 'sort_order' => 1]);
        $option = $question->options()->create(['text' => 'Correct', 'is_correct' => true, 'sort_order' => 1]);

        $this->assertSame('fr', $option->contentLanguageCode());
        $this->assertFalse(Schema::hasColumn('options', 'language'));
    }

    public function test_translated_falls_back_to_the_original_until_set(): void
    {
        $lesson = $this->lessonWithQuestion();
        $question = $lesson->questions()->first();

        $this->assertSame('Which one?', $question->translated('prompt', 'ru'));

        $question->setTranslation('prompt', 'ru', 'Какой из них?');
        $this->assertSame('Какой из них?', $question->fresh()->translated('prompt', 'ru'));

        $question->setTranslation('prompt', 'ru', '');
        $this->assertSame('Which one?', $question->fresh()->translated('prompt', 'ru'), 'Emptying a box restores the original.');
    }

    // --- Translating from a lesson's edit page ------------------------------

    public function test_translating_a_lessons_quiz_saves_the_question_and_its_options(): void
    {
        $lesson = $this->lessonWithQuestion();
        $question = $lesson->questions()->first();
        $right = $question->options()->where('text', 'Right')->first();
        $wrong = $question->options()->where('text', 'Wrong')->first();

        Livewire::actingAs($this->admin())
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->callAction('translateQuestions', data: [
                'language' => 'fr',
                'engine' => 'manual',
                "question_{$question->id}" => ['prompt' => 'Lequel ?'],
                "option_{$right->id}" => ['text' => 'Correct'],
                "option_{$wrong->id}" => ['text' => 'Incorrect'],
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('Lequel ?', $question->fresh()->translated('prompt', 'fr'));
        $this->assertSame('Correct', $right->fresh()->translated('text', 'fr'));
        $this->assertSame('Incorrect', $wrong->fresh()->translated('text', 'fr'));
        $this->assertSame($question->prompt, $question->fresh()->translated('prompt', 'es'), 'Another language is untouched.');
    }

    public function test_the_button_is_hidden_from_a_lesson_with_no_questions(): void
    {
        $course = Course::first();
        $lesson = $course->lessons()->create([
            'title' => 'A lesson with no quiz',
            'slug' => 'translation-test-lesson-no-quiz',
            'content' => '<p>Body.</p>',
        ]);

        Livewire::actingAs($this->admin())
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->assertActionHidden('translateQuestions');
    }

    // --- Translating from the course's final-quiz bank ----------------------

    public function test_translating_a_courses_final_quiz_bank_covers_a_course_only_question(): void
    {
        $course = Course::first();
        $question = Question::create(['prompt' => 'Course only', 'sort_order' => 1]);
        $question->options()->create(['text' => 'Alpha', 'is_correct' => true, 'sort_order' => 1]);
        $course->finalQuestions()->attach($question->id, ['sort_order' => 1]);

        Livewire::actingAs($this->admin())
            ->test(FinalQuestionsRelationManager::class, ['ownerRecord' => $course, 'pageClass' => EditCourse::class])
            ->callAction(TestAction::make('translateFinalQuestions')->table(), data: [
                'language' => 'fr',
                'engine' => 'manual',
                "question_{$question->id}" => ['prompt' => 'Seulement le cours'],
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('Seulement le cours', $question->fresh()->translated('prompt', 'fr'));
    }

    public function test_drafting_a_quiz_sends_only_the_empty_boxes(): void
    {
        $row = AiProvider::for('chatgpt');
        $row->fill(['enabled' => true, 'api_key' => 'sk-test'])->save();

        Http::fake(function (Request $request) {
            $texts = json_decode($request->data()['messages'][1]['content'], true);

            return Http::response(['choices' => [['message' => ['content' => json_encode(
                collect($texts)->map(fn (string $text): string => '[gpt] '.$text)->all(),
                JSON_UNESCAPED_UNICODE,
            )]]]]);
        });

        $lesson = $this->lessonWithQuestion();
        $question = $lesson->questions()->first();
        // Already written by a person — must not be sent to the engine.
        $question->setTranslation('prompt', 'fr', 'Écrit par une personne');

        $component = Livewire::actingAs($this->admin(User::PERMISSION_AI_TRANSLATE))
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->mountAction('translateQuestions')
            ->setActionData(['language' => 'fr', 'engine' => 'translateWithChatgpt'])
            ->goToNextWizardStep();

        $component->assertSet("mountedActions.0.data.question_{$question->id}.prompt", 'Écrit par une personne');

        Http::assertSent(fn (Request $request): bool => ! str_contains($request->data()['messages'][1]['content'], 'Écrit par une personne'));
        $this->assertSame(1, $question->contentTranslations()->count(), 'Nothing is stored until Save translations.');
    }

    // --- Student-facing ------------------------------------------------------

    public function test_a_students_reading_a_lesson_sees_the_quiz_in_their_language(): void
    {
        $lesson = $this->lessonWithQuestion();
        $question = $lesson->questions()->first();
        $right = $question->options()->where('text', 'Right')->first();
        $question->setTranslation('prompt', 'fr', 'Lequel ?');
        $right->setTranslation('text', 'fr', 'Correct');

        $this->withHeader('Accept-Language', 'fr')
            ->get(route('academy.lesson', [$lesson->course, $lesson]))
            ->assertSee('Lequel ?')
            ->assertSee('Correct')
            ->assertDontSee('Which one?');
    }

    public function test_a_lessons_quiz_without_a_translation_shows_the_original(): void
    {
        $lesson = $this->lessonWithQuestion();

        $this->withHeader('Accept-Language', 'fr')
            ->get(route('academy.lesson', [$lesson->course, $lesson]))
            ->assertSee('Which one?');
    }
}
