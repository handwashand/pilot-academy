<?php

namespace Tests\Feature;

use App\Filament\Widgets\TopCreators;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/** The dashboard's Top creators panel: creators ranked by the lessons they wrote. */
class TopCreatorsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email, string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => $role,
        ]);
    }

    private function lessonBy(User $author, string $slug): Lesson
    {
        $lesson = new Lesson([
            'title' => Str::headline($slug),
            'slug' => $slug,
            'status' => Lesson::STATUS_DRAFT,
        ]);

        // Set directly, so Lesson::creating() does not fall back to the request.
        $lesson->created_by = $author->id;
        $lesson->save();

        return $lesson;
    }

    public function test_creators_are_ranked_by_the_lessons_they_wrote(): void
    {
        $busy = $this->user(User::ROLE_CREATOR, 'busy@partner.com', 'Busy Author');
        $quiet = $this->user(User::ROLE_CREATOR, 'quiet@partner.com', 'Quiet Author');
        $idle = $this->user(User::ROLE_CREATOR, 'idle@partner.com', 'Idle Author');

        $this->lessonBy($busy, 'busy-one');
        $this->lessonBy($busy, 'busy-two');
        $this->lessonBy($quiet, 'quiet-one');

        Livewire::actingAs($this->user(User::ROLE_ADMIN, 'admin@pilot.local', 'Pilot Admin'))
            ->test(TopCreators::class)
            ->assertCanSeeTableRecords([$busy, $quiet, $idle], inOrder: true)
            ->assertTableColumnStateSet('created_lessons_count', 2, $busy)
            ->assertTableColumnStateSet('created_lessons_count', 1, $quiet)
            ->assertTableColumnStateSet('created_lessons_count', 0, $idle);
    }

    public function test_a_deleted_lesson_is_not_counted(): void
    {
        $creator = $this->user(User::ROLE_CREATOR, 'creator@partner.com', 'Course Author');
        $this->lessonBy($creator, 'kept');
        $this->lessonBy($creator, 'removed')->delete();

        Livewire::actingAs($this->user(User::ROLE_ADMIN, 'admin@pilot.local', 'Pilot Admin'))
            ->test(TopCreators::class)
            ->assertCanSeeTableRecords([$creator])
            ->assertTableColumnStateSet('created_lessons_count', 1, $creator);
    }

    public function test_only_creators_are_listed(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'admin@pilot.local', 'Pilot Admin');
        $learner = $this->user(User::ROLE_LEARNER, 'student@partner.com', 'Partner Student');
        $creator = $this->user(User::ROLE_CREATOR, 'creator@partner.com', 'Course Author');

        $this->lessonBy($admin, 'written-by-an-admin');
        $this->lessonBy($learner, 'written-by-a-student');
        $this->lessonBy($creator, 'written-by-a-creator');

        Livewire::actingAs($admin)
            ->test(TopCreators::class)
            ->assertCanSeeTableRecords([$creator])
            ->assertCanNotSeeTableRecords([$admin, $learner]);
    }

    public function test_only_admins_see_the_widget(): void
    {
        $this->actingAs($this->user(User::ROLE_ADMIN, 'admin@pilot.local', 'Pilot Admin'));
        $this->assertTrue(TopCreators::canView());

        $this->actingAs($this->user(User::ROLE_CREATOR, 'creator@partner.com', 'Course Author'));
        $this->assertFalse(TopCreators::canView());

        $this->actingAs($this->user(User::ROLE_LEARNER, 'student@partner.com', 'Partner Student'));
        $this->assertFalse(TopCreators::canView());
    }

    public function test_the_list_stops_at_ten_creators(): void
    {
        $creators = [];

        // Eleven creators with 1..11 lessons each: a clear order, no ties.
        foreach (range(1, 11) as $number) {
            $creator = $this->user(User::ROLE_CREATOR, "creator{$number}@partner.com", "Creator {$number}");

            foreach (range(1, $number) as $lesson) {
                $this->lessonBy($creator, "creator-{$number}-lesson-{$lesson}");
            }

            $creators[$number] = $creator;
        }

        $top = array_reverse(array_slice($creators, 1, 10, true));

        Livewire::actingAs($this->user(User::ROLE_ADMIN, 'admin@pilot.local', 'Pilot Admin'))
            ->test(TopCreators::class)
            ->assertCanSeeTableRecords(array_values($top), inOrder: true)
            ->assertCanNotSeeTableRecords([$creators[1]]);
    }
}
