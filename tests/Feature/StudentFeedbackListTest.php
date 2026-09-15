<?php

namespace Tests\Feature;

use App\Filament\Resources\CourseFeedback\CourseFeedbackResource;
use App\Filament\Resources\CourseFeedback\Pages\ListCourseFeedback;
use App\Models\Course;
use App\Models\CourseFeedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Results → Student feedback: every course's verdicts in one list. */
class StudentFeedbackListTest extends TestCase
{
    use RefreshDatabase;

    private function feedback(string $slug, bool $positive, string $comment): CourseFeedback
    {
        $course = Course::create(['title' => "Course {$slug}", 'slug' => $slug, 'level' => 'beginner', 'status' => Course::STATUS_PUBLISHED]);
        $student = User::create(['name' => "Student {$slug}", 'email' => "{$slug}@partner.com", 'password' => 'secret123', 'role' => User::ROLE_LEARNER]);

        return CourseFeedback::create(['user_id' => $student->id, 'course_id' => $course->id, 'is_positive' => $positive, 'comment' => $comment]);
    }

    public function test_an_admin_sees_feedback_from_every_course_and_can_filter_the_complaints(): void
    {
        $useful = $this->feedback('garm', true, 'Clear and short.');
        $notUseful = $this->feedback('ptm', false, 'Too long.');

        $admin = User::create(['name' => 'Admin', 'email' => 'admin@pilot.local', 'password' => 'password', 'role' => User::ROLE_ADMIN]);

        Livewire::actingAs($admin)
            ->test(ListCourseFeedback::class)
            ->assertCanSeeTableRecords([$useful, $notUseful])
            ->filterTable('is_positive', false)
            ->assertCanSeeTableRecords([$notUseful])
            ->assertCanNotSeeTableRecords([$useful]);
    }

    public function test_creators_cannot_open_the_cross_course_list(): void
    {
        $creator = User::create(['name' => 'Creator', 'email' => 'creator@pilot.local', 'password' => 'secret123', 'role' => User::ROLE_CREATOR]);

        $this->actingAs($creator)
            ->get(CourseFeedbackResource::getUrl())
            ->assertForbidden();
    }
}
