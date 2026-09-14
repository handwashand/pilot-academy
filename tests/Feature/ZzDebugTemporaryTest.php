<?php

namespace Tests\Feature;

use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/** Temporary diagnostics — deleted once read. */
class ZzDebugTemporaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_print_diagnostics(): void
    {
        $admin = User::create(['name' => 'A', 'email' => 'a@pilot.local', 'password' => 'password', 'role' => User::ROLE_ADMIN]);
        $a = Course::create(['title' => 'Course A', 'slug' => 'course-a', 'level' => 'beginner', 'status' => Course::STATUS_PUBLISHED]);
        $b = Course::create(['title' => 'Course B', 'slug' => 'course-b', 'level' => 'beginner', 'status' => Course::STATUS_PUBLISHED]);
        $lesson = $a->lessons()->create(['title' => 'Shared', 'slug' => Str::slug('Shared'), 'content' => '<p>Body.</p>', 'sort_order' => 1]);
        $b->lessons()->attach($lesson);

        $component = Livewire::actingAs($admin)
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()]);

        fwrite(STDERR, "\nHYDRATED course_ids: ".json_encode($component->get('data.course_ids')));

        $component->fillForm(['course_ids' => [$b->id]])->call('save');

        fwrite(STDERR, "\nSHARED SAVE ERRORS: ".json_encode($component->errors()->toArray()));
        fwrite(STDERR, "\nAFTER home: ".$lesson->fresh()->course_id.' courses: '.json_encode($lesson->courses()->pluck('courses.id'))."\n");

        $this->assertTrue(true);
    }
}
