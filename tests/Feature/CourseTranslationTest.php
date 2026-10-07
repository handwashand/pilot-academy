<?php

namespace Tests\Feature;

use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Models\AiProvider;
use App\Models\Course;
use App\Models\DescriptImport;
use App\Models\Lesson;
use App\Models\User;
use App\Models\VideoTranslation;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\PilotQuickStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Translating a whole course: every lesson video through Descript, and the
 * course's and lessons' text through the chosen engine, in one window.
 */
class CourseTranslationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
        $this->seed(PilotQuickStartSeeder::class);
        Storage::fake('public');
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'api.openai.com')) {
                $texts = json_decode($request->data()['messages'][1]['content'], true);

                return Http::response(['choices' => [['message' => ['content' => json_encode(
                    collect($texts)->map(fn (string $text): string => '[gpt] '.$text)->all(),
                    JSON_UNESCAPED_UNICODE,
                )]]]]);
            }

            return Http::response(['error' => 'unexpected'], 500);
        });
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

    /** The seeded course, with a video in each of its first two lessons. */
    private function courseWithVideos(): Course
    {
        $course = Course::first();

        foreach ($course->lessons()->take(2)->get() as $i => $lesson) {
            Storage::disk('public')->put("lesson-videos/v{$i}.mp4", 'video');
            $lesson->update(['video_sources' => [['type' => 'upload', 'video_path' => "lesson-videos/v{$i}.mp4"]]]);
        }

        return $course;
    }

    private function descriptOn(): void
    {
        config(['services.descript.enabled' => true, 'services.descript.token' => 'tok']);
    }

    public function test_every_lesson_video_in_the_course_is_queued_for_each_language_and_nothing_is_sent(): void
    {
        $this->descriptOn();
        $course = $this->courseWithVideos();

        Livewire::actingAs($this->admin(User::PERMISSION_DESCRIPT_TRANSLATE))
            ->test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->callAction('translateCourseVideosWithDescript', data: ['languages' => ['fr', 'es'], 'confirmed' => true])
            ->assertHasNoActionErrors();

        $this->assertSame(2, DescriptImport::count(), 'One import per video.');
        $this->assertSame(4, VideoTranslation::count(), '2 videos × 2 languages.');
        $this->assertSame(4, VideoTranslation::where('status', VideoTranslation::STATUS_PENDING)->count());
        Http::assertNothingSent();
    }

    public function test_asking_again_queues_nothing_new(): void
    {
        $this->descriptOn();
        $course = $this->courseWithVideos();
        $editor = $this->admin(User::PERMISSION_DESCRIPT_TRANSLATE);
        $data = ['languages' => ['fr'], 'confirmed' => true];

        Livewire::actingAs($editor)->test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->callAction('translateCourseVideosWithDescript', data: $data);
        Livewire::actingAs($editor)->test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->callAction('translateCourseVideosWithDescript', data: $data);

        $this->assertSame(2, VideoTranslation::count());
    }

    public function test_the_acknowledgement_is_required(): void
    {
        $this->descriptOn();
        $course = $this->courseWithVideos();

        Livewire::actingAs($this->admin(User::PERMISSION_DESCRIPT_TRANSLATE))
            ->test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->callAction('translateCourseVideosWithDescript', data: ['languages' => ['fr'], 'confirmed' => false])
            ->assertHasActionErrors(['confirmed']);

        $this->assertSame(0, VideoTranslation::count());
    }

    public function test_the_course_button_needs_the_right_and_a_video(): void
    {
        $this->descriptOn();
        $course = $this->courseWithVideos();

        Livewire::actingAs($this->admin())
            ->test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->assertActionHidden('translateCourseVideosWithDescript');
    }

    public function test_a_course_without_uploaded_videos_has_no_video_button(): void
    {
        $this->descriptOn();
        $course = Course::first();

        Livewire::actingAs($this->admin(User::PERMISSION_DESCRIPT_TRANSLATE))
            ->test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->assertActionHidden('translateCourseVideosWithDescript');
    }

    public function test_check_progress_on_a_course_shows_only_while_something_is_under_way(): void
    {
        $this->descriptOn();
        $course = $this->courseWithVideos();
        $editor = $this->admin(User::PERMISSION_DESCRIPT_TRANSLATE);

        Livewire::actingAs($editor)->test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->assertActionHidden('checkDescriptCourseProgress')
            ->callAction('translateCourseVideosWithDescript', data: ['languages' => ['fr'], 'confirmed' => true]);

        Livewire::actingAs($editor)->test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->assertActionVisible('checkDescriptCourseProgress');
    }

    public function test_the_whole_course_is_saved_from_one_window(): void
    {
        $course = Course::first();
        $lesson = $course->lessons()->first();

        Livewire::actingAs($this->admin())
            ->test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->callAction('translateWholeCourse', data: [
                'language' => 'fr',
                'engine' => 'manual',
                'course' => ['title' => 'Démarrage rapide', 'description' => 'Tout pour commencer.'],
                "lesson_{$lesson->id}" => ['title' => 'Première leçon'],
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('Démarrage rapide', $course->fresh()->translated('title', 'fr'));
        $this->assertSame('Première leçon', $lesson->fresh()->translated('title', 'fr'));
        $this->assertSame($course->title, $course->fresh()->translated('title', 'es'), 'Another language is untouched.');
    }

    public function test_drafting_the_whole_course_sends_all_empty_boxes_together_and_stores_nothing(): void
    {
        $row = AiProvider::for('chatgpt');
        $row->fill(['enabled' => true, 'api_key' => 'sk-test'])->save();
        $course = Course::first();
        $lesson = $course->lessons()->first();
        // One box is already written by a person: it must not go out.
        $course->setTranslation('title', 'fr', 'Écrit par une personne');

        $component = Livewire::actingAs($this->admin(User::PERMISSION_AI_TRANSLATE))
            ->test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->mountAction('translateWholeCourse')
            ->setActionData(['language' => 'fr', 'engine' => 'translateWithChatgpt'])
            ->goToNextWizardStep();

        $component->assertSet('mountedActions.0.data.course.title', 'Écrit par une personne');
        $component->assertSet("mountedActions.0.data.lesson_{$lesson->id}.title", '[gpt] '.$lesson->title);

        Http::assertSent(fn (Request $request): bool => ! str_contains($request->data()['messages'][1]['content'], 'course.title'));
        $this->assertSame(1, $course->contentTranslations()->count(), 'Only the person’s own text is stored.');
        $this->assertNull(Lesson::find($lesson->id)->contentTranslations()->first());
    }
}
