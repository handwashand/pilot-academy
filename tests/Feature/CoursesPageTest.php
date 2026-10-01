<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Explore is the catalogue: every published course with filters for level and
 * audience. The home page stays the student's own starting point, and Help now
 * sits under Resources in the header.
 */
class CoursesPageTest extends TestCase
{
    use RefreshDatabase;

    private function course(array $attributes): Course
    {
        $course = Course::create([
            'slug' => $attributes['slug'],
            'title' => $attributes['title'],
            'description' => $attributes['description'] ?? 'A course.',
            'level' => $attributes['level'] ?? 'beginner',
            'audience' => $attributes['audience'] ?? null,
            'status' => $attributes['status'] ?? Course::STATUS_PUBLISHED,
        ]);

        $course->lessons()->create([
            'title' => 'Lesson one',
            'slug' => $attributes['slug'].'-one',
            'summary' => 'The first lesson.',
            'content' => '<p>Body.</p>',
            'status' => Lesson::STATUS_PUBLISHED,
        ]);

        return $course;
    }

    public function test_the_courses_page_lists_published_courses_and_hides_drafts(): void
    {
        $this->course(['slug' => 'monitoring', 'title' => 'Pilot Monitoring']);
        $this->course(['slug' => 'secret', 'title' => 'Unfinished course', 'status' => Course::STATUS_DRAFT]);

        $this->get(route('academy.courses'))
            ->assertOk()
            ->assertSee('Courses')
            ->assertSee('Pilot Monitoring')
            ->assertSee('1 course')
            ->assertDontSee('Unfinished course');
    }

    public function test_the_filters_narrow_the_catalogue(): void
    {
        $this->course(['slug' => 'monitoring', 'title' => 'Pilot Monitoring', 'level' => 'beginner', 'audience' => 'technical']);
        $this->course(['slug' => 'selling', 'title' => 'Selling Pilot', 'level' => 'advanced', 'audience' => 'sales']);

        $this->get(route('academy.courses', ['level' => 'advanced']))
            ->assertOk()
            ->assertSee('Selling Pilot')
            ->assertDontSee('Pilot Monitoring');

        $this->get(route('academy.courses', ['audience' => 'technical']))
            ->assertOk()
            ->assertSee('Pilot Monitoring')
            ->assertDontSee('Selling Pilot');

        $this->get(route('academy.courses', ['q' => 'selling']))
            ->assertOk()
            ->assertSee('Selling Pilot')
            ->assertDontSee('Pilot Monitoring');

        $this->get(route('academy.courses', ['q' => 'nothing-like-this']))
            ->assertOk()
            ->assertSee('No courses matched.');
    }

    public function test_the_courses_page_reads_in_the_visitors_language(): void
    {
        $this->seed(LanguageSeeder::class);
        $this->course(['slug' => 'monitoring', 'title' => 'Pilot Monitoring']);

        $this->withHeader('Accept-Language', 'ru-RU,ru;q=0.9')
            ->get(route('academy.courses'))
            ->assertOk()
            ->assertSee('Курсы')
            ->assertSee('Любой уровень')
            ->assertDontSee('All levels');
    }

    public function test_the_header_offers_the_four_content_areas(): void
    {
        $this->get(route('academy.home'))
            ->assertOk()
            ->assertSeeInOrder([
                '<header',
                route('academy.courses'),
                route('academy.case-studies.index'),
                route('academy.tutorials'),
                route('academy.webinars'),
                '</header>',
            ], false);
    }

    /** Help left the header for the account menu; the footer carries it for guests. */
    public function test_help_is_in_the_account_menu_and_the_footer(): void
    {
        $learner = User::create([
            'name' => 'Ana Pereira',
            'email' => 'learner@partner.test',
            'password' => 'secret123',
            'role' => User::ROLE_LEARNER,
        ]);

        $this->actingAs($learner)
            ->get(route('academy.home'))
            ->assertOk()
            ->assertSeeInOrder(['data-account-menu', route('academy.help'), '</header>'], false);

        // A guest has no account menu, so the footer is the way in.
        $this->get(route('academy.home'))
            ->assertOk()
            ->assertSeeInOrder(['<footer', route('academy.help')], false);
    }
}
