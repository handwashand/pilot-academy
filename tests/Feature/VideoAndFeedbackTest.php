<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseFeedback;
use App\Models\Lesson;
use App\Models\User;
use App\Models\VideoPosition;
use Database\Seeders\PilotQuickStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoAndFeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotQuickStartSeeder::class);
    }

    private function course(): Course
    {
        return Course::published()->firstOrFail();
    }

    private function lesson(): Lesson
    {
        return $this->course()->publishedLessons()->firstOrFail();
    }

    private function student(string $email = 'watcher@partner.com'): User
    {
        return User::firstOrCreate(['email' => $email], [
            'name' => 'Watcher',
            'password' => bcrypt('secret'),
            'role' => 'learner',
        ]);
    }

    // -------------------------------------------------- video position

    public function test_a_students_place_in_a_video_is_remembered(): void
    {
        $course = $this->course();
        $lesson = $this->lesson();
        $student = $this->student();

        $this->actingAs($student)
            ->post(route('academy.lesson.position', [$course, $lesson]), ['seconds' => 1080])
            ->assertNoContent();

        $this->assertDatabaseHas('video_positions', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'seconds' => 1080,
        ]);
    }

    public function test_the_saved_position_is_handed_to_the_lesson_page(): void
    {
        $course = $this->course();
        $lesson = $this->lesson();
        $student = $this->student();

        VideoPosition::create(['user_id' => $student->id, 'lesson_id' => $lesson->id, 'seconds' => 742]);

        $this->actingAs($student)
            ->get(route('academy.lesson', [$course, $lesson]))
            ->assertOk()
            ->assertViewHas('videoPosition', 742);
    }

    public function test_saving_a_position_repeatedly_keeps_one_row(): void
    {
        $course = $this->course();
        $lesson = $this->lesson();
        $student = $this->student();

        foreach ([10, 20, 30] as $seconds) {
            $this->actingAs($student)
                ->post(route('academy.lesson.position', [$course, $lesson]), ['seconds' => $seconds]);
        }

        $this->assertSame(1, VideoPosition::where('user_id', $student->id)->where('lesson_id', $lesson->id)->count());
        $this->assertSame(30, VideoPosition::where('user_id', $student->id)->where('lesson_id', $lesson->id)->value('seconds'));
    }

    /**
     * The whole reason playback position is not a column on the lesson_user
     * pivot: completedLessons() does not filter on completed_at, so a row there
     * would count as a completion in progress bars, partner reports and the
     * gate that unlocks the final quiz.
     */
    public function test_watching_a_video_does_not_mark_the_lesson_complete(): void
    {
        $course = $this->course();
        $lesson = $this->lesson();
        $student = $this->student();

        $this->actingAs($student)
            ->post(route('academy.lesson.position', [$course, $lesson]), ['seconds' => 900]);

        $this->assertSame(0, $student->fresh()->completedLessons()->count());
        $this->assertDatabaseMissing('lesson_user', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
        ]);
        $this->assertFalse($course->isCompletedBy($student->fresh()));
    }

    public function test_an_anonymous_visitor_cannot_save_a_position(): void
    {
        $this->post(route('academy.lesson.position', [$this->course(), $this->lesson()]), ['seconds' => 60])
            ->assertRedirect(route('login'));

        $this->assertSame(0, VideoPosition::count());
    }

    public function test_a_position_cannot_be_saved_against_a_draft_lesson(): void
    {
        $course = $this->course();
        $draft = $course->lessons()->create([
            'title' => 'Draft lesson',
            'slug' => 'draft-lesson',
            'summary' => 'Hidden.',
            'sort_order' => 99,
            'status' => Lesson::STATUS_DRAFT,
        ]);

        $this->actingAs($this->student())
            ->post(route('academy.lesson.position', [$course, $draft]), ['seconds' => 60])
            ->assertNotFound();
    }

    public function test_a_nonsense_position_is_rejected(): void
    {
        $this->actingAs($this->student())
            ->post(route('academy.lesson.position', [$this->course(), $this->lesson()]), ['seconds' => -5])
            ->assertSessionHasErrors('seconds');
    }

    // ------------------------------------------------------ transcript

    public function test_a_transcript_is_shown_on_the_lesson_page(): void
    {
        $lesson = $this->lesson();
        $lesson->update(['transcript' => 'Open the client cabinet and choose Objects.']);

        $this->get(route('academy.lesson', [$this->course(), $lesson]))
            ->assertOk()
            ->assertSee('Open the client cabinet and choose Objects.');
    }

    public function test_search_finds_words_spoken_only_in_a_video(): void
    {
        $lesson = $this->lesson();
        $lesson->update(['transcript' => 'Remember to check the odometer calibration.']);

        $this->get(route('academy.search', ['q' => 'odometer']))
            ->assertOk()
            ->assertSee($lesson->title, false);
    }

    public function test_a_draft_lessons_transcript_is_not_searchable(): void
    {
        $draft = $this->course()->lessons()->create([
            'title' => 'Draft lesson',
            'slug' => 'draft-lesson',
            'summary' => 'Hidden.',
            'transcript' => 'Secret zeppelin calibration procedure.',
            'sort_order' => 99,
            'status' => Lesson::STATUS_DRAFT,
        ]);

        $this->get(route('academy.search', ['q' => 'zeppelin']))
            ->assertOk()
            ->assertDontSee($draft->title);
    }

    // -------------------------------------------------------- feedback

    private function finish(Course $course, User $student): void
    {
        foreach ($course->publishedLessons as $lesson) {
            $student->completedLessons()->syncWithoutDetaching([$lesson->id => ['completed_at' => now()]]);
        }
    }

    public function test_a_student_who_finished_can_say_what_they_thought(): void
    {
        $course = $this->course();
        $student = $this->student();
        $this->finish($course, $student);

        $this->actingAs($student)
            ->post(route('academy.course.feedback', $course), [
                'is_positive' => '0',
                'comment' => 'Too long for one sitting.',
            ])
            ->assertRedirect(route('academy.course', $course));

        $feedback = CourseFeedback::where('user_id', $student->id)->where('course_id', $course->id)->first();

        $this->assertNotNull($feedback);
        $this->assertFalse($feedback->is_positive);
        $this->assertSame('Too long for one sitting.', $feedback->comment);
    }

    public function test_a_student_who_has_not_finished_cannot(): void
    {
        $course = $this->course();

        $this->actingAs($this->student())
            ->post(route('academy.course.feedback', $course), ['is_positive' => '1'])
            ->assertForbidden();

        $this->assertSame(0, CourseFeedback::count());
    }

    public function test_changing_your_mind_replaces_the_verdict_rather_than_adding_one(): void
    {
        $course = $this->course();
        $student = $this->student();
        $this->finish($course, $student);

        $this->actingAs($student)->post(route('academy.course.feedback', $course), ['is_positive' => '1']);
        $this->actingAs($student)->post(route('academy.course.feedback', $course), ['is_positive' => '0']);

        $this->assertSame(1, CourseFeedback::where('user_id', $student->id)->where('course_id', $course->id)->count());
        $this->assertFalse(CourseFeedback::where('user_id', $student->id)->value('is_positive'));
    }

    public function test_feedback_is_never_shown_to_another_student(): void
    {
        $course = $this->course();
        $author = $this->student('author@partner.com');
        $other = $this->student('other@partner.com');

        $this->finish($course, $author);
        $this->finish($course, $other);

        $this->actingAs($author)->post(route('academy.course.feedback', $course), [
            'is_positive' => '0',
            'comment' => 'The sensors section made no sense.',
        ]);

        $this->actingAs($other)
            ->get(route('academy.course', $course))
            ->assertOk()
            ->assertDontSee('The sensors section made no sense.');
    }

    public function test_a_comment_that_is_too_long_is_rejected(): void
    {
        $course = $this->course();
        $student = $this->student();
        $this->finish($course, $student);

        $this->actingAs($student)
            ->post(route('academy.course.feedback', $course), [
                'is_positive' => '1',
                'comment' => str_repeat('a', 1001),
            ])
            ->assertSessionHasErrors('comment');
    }
}
