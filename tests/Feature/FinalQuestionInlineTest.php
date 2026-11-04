<?php

namespace Tests\Feature;

use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\RelationManagers\FinalQuestionsRelationManager;
use App\Models\Course;
use App\Models\Question;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A single question written straight into a course's final-quiz bank — the
 * inline counterpart of the Excel upload.
 */
class FinalQuestionInlineTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::firstOrCreate(['email' => 'admin@pilot.local'], [
            'name' => 'Pilot Admin',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    private function course(string $language): Course
    {
        return Course::create([
            'title' => 'Course in '.$language,
            'slug' => 'course-in-'.$language,
            'level' => 'beginner',
            'language' => $language,
        ]);
    }

    public function test_a_question_written_in_the_bank_is_the_courses_language_and_lands_last(): void
    {
        $course = $this->course('fr');

        $existing = Question::create([
            'prompt' => 'Question existante',
            'type' => Question::TYPE_SINGLE,
            'language' => 'fr',
            'sort_order' => 1,
        ]);
        $existing->options()->create(['text' => 'Oui', 'is_correct' => true, 'sort_order' => 1]);
        $course->finalQuestions()->attach($existing->id, ['sort_order' => 3]);

        $component = Livewire::actingAs($this->admin())
            ->test(FinalQuestionsRelationManager::class, [
                'ownerRecord' => $course,
                'pageClass' => EditCourse::class,
            ])
            ->mountAction(TestAction::make('create')->table());

        // Set rather than fillForm: the options repeater already holds its two
        // empty default rows, and filling a repeater through the form helper
        // leaves those in place, failing their "answer is required" rule.
        $component
            ->set('mountedActions.0.data.prompt', 'Nouvelle question')
            ->set('mountedActions.0.data.type', Question::TYPE_SINGLE)
            ->set('mountedActions.0.data.options', [
                ['text' => 'Oui', 'is_correct' => true],
                ['text' => 'Non', 'is_correct' => false],
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $question = Question::where('prompt', 'Nouvelle question')->firstOrFail();

        $this->assertNull($question->lesson_id, 'A question written in the bank belongs to no lesson.');
        $this->assertSame('fr', $question->language, 'Written in the course\'s language, like the Excel import.');

        $inBank = $course->finalQuestions()->whereKey($question->id)->first();
        $this->assertNotNull($inBank, 'Added to the course\'s bank.');
        $this->assertSame(4, (int) $inBank->pivot->sort_order, 'Lands after the questions already in the bank.');

        $this->assertSame(2, $question->options()->count(), 'Both answer options were saved.');
        $this->assertSame(1, $question->options()->where('is_correct', true)->count());
    }
}
