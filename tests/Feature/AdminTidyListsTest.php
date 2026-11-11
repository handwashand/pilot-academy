<?php

namespace Tests\Feature;

use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\Pages\ListCourses;
use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Filament\Resources\Lessons\Pages\ListLessons;
use App\Filament\Resources\Lessons\Schemas\LessonForm;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The lists fit a laptop screen without scrolling sideways, edit pages are
 * headed by the record's own name, and each video row says which video it is.
 */
class AdminTidyListsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Pilot Admin',
            'email' => 'admin@pilot.local',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);
    }

    private function course(): Course
    {
        return Course::create(['title' => 'Fuel basics', 'slug' => 'fuel-basics', 'level' => 'beginner']);
    }

    public function test_rarely_filled_columns_start_hidden_and_problem_flags_stay(): void
    {
        $admin = $this->admin();
        $this->course();

        Livewire::actingAs($admin)
            ->test(ListCourses::class)
            ->assertCanNotRenderTableColumn('product.name')
            ->assertCanNotRenderTableColumn('updated_at')
            ->assertCanRenderTableColumn('attention')
            ->assertCanRenderTableColumn('title');

        Livewire::actingAs($admin)
            ->test(ListLessons::class)
            ->assertCanNotRenderTableColumn('cover')
            ->assertCanRenderTableColumn('title');
    }

    public function test_edit_pages_are_headed_by_the_records_name_alone(): void
    {
        $admin = $this->admin();
        $course = $this->course();
        $lesson = Lesson::create(['course_id' => $course->id, 'title' => 'Reading the fuel report', 'slug' => 'fuel-report']);

        $this->assertSame('Reading the fuel report', (string) Livewire::actingAs($admin)
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->instance()
            ->getHeading());

        $this->assertSame('Fuel basics', (string) Livewire::actingAs($admin)
            ->test(EditCourse::class, ['record' => $course->getRouteKey()])
            ->instance()
            ->getHeading());
    }

    public function test_a_video_row_is_named_by_its_link_or_file(): void
    {
        $this->assertStringEndsWith(
            'https://youtu.be/0cBjPiCOHLI',
            LessonForm::videoLabel(['type' => 'youtube', 'youtube_url' => 'https://youtu.be/0cBjPiCOHLI']),
        );
        $this->assertStringEndsWith(
            'intro.mp4',
            LessonForm::videoLabel(['type' => 'upload', 'video_path' => ['a1' => 'lesson-videos/intro.mp4']]),
        );
        $this->assertSame(__t('admin_lessons.form.youtube_video'), LessonForm::videoLabel(['type' => 'youtube']));
        $this->assertSame(__t('admin_lessons.form.uploaded_video'), LessonForm::videoLabel(['type' => 'upload', 'video_path' => []]));
    }
}
