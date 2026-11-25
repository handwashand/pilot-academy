<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\SlugRedirect;
use Database\Seeders\PilotQuickStartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Renaming a course or lesson keeps its old links working: they are sent on
 * (301) to the same page under the new slug, so shared links, bookmarks and
 * emails do not break (owner, 2026-11-25, after the introduction course's
 * rename sent every old link to a 404).
 */
class SlugRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotQuickStartSeeder::class);
    }

    public function test_a_renamed_course_sends_old_links_on_with_the_rest_of_the_address(): void
    {
        $course = Course::first();
        $lesson = $course->lessons()->first();
        $old = $course->slug;

        $course->update(['slug' => 'pilot-introduction']);

        $this->get("/courses/{$old}")
            ->assertStatus(301)
            ->assertRedirect(url('/courses/pilot-introduction'));

        $this->get("/courses/{$old}/lessons/{$lesson->slug}?audio=original")
            ->assertStatus(301)
            ->assertRedirect(url("/courses/pilot-introduction/lessons/{$lesson->slug}?audio=original"));

        $this->get('/courses/pilot-introduction')->assertOk();
    }

    public function test_a_renamed_lesson_sends_old_links_on(): void
    {
        $course = Course::first();
        $lesson = $course->lessons()->first();
        $old = $lesson->slug;

        $lesson->update(['slug' => 'creating-a-client-account']);

        $this->get("/courses/{$course->slug}/lessons/{$old}")
            ->assertStatus(301)
            ->assertRedirect(url("/courses/{$course->slug}/lessons/creating-a-client-account"));

        $this->get("/courses/{$course->slug}/lessons/creating-a-client-account")->assertOk();
    }

    public function test_changing_back_makes_the_old_address_live_again_without_a_loop(): void
    {
        $course = Course::first();
        $original = $course->slug;

        $course->update(['slug' => 'pilot-introduction']);
        $course->update(['slug' => $original]);

        $this->assertFalse(SlugRedirect::where('model_type', 'course')->where('slug', $original)->exists());
        $this->get("/courses/{$original}")->assertOk();
        $this->get('/courses/pilot-introduction')->assertRedirect(url("/courses/{$original}"));
    }

    public function test_an_address_that_never_existed_is_still_a_404(): void
    {
        $this->get('/courses/no-such-course')->assertNotFound();
        $this->get('/courses/'.Course::first()->slug.'/lessons/no-such-lesson')->assertNotFound();
    }

    public function test_a_form_posted_to_an_old_address_is_served_not_redirected(): void
    {
        $course = Course::first();
        $lesson = $course->lessons()->first();
        $old = $lesson->slug;
        $lesson->update(['slug' => 'renamed-lesson']);

        // The quiz submit route: it must not answer with a redirect to a GET.
        $response = $this->post("/courses/{$course->slug}/lessons/{$old}/quiz", []);

        $this->assertNotSame(301, $response->getStatusCode());
        $this->assertNotSame(404, $response->getStatusCode());
    }
}
