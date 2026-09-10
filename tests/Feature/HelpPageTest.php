<?php

namespace Tests\Feature;

use App\Filament\Pages\AdminGuide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_learner_guide_file_exists(): void
    {
        $this->assertFileExists(base_path('docs/learner-guide.md'));
    }

    /** Anonymous visitors take lessons, so they need help as much as anyone. */
    public function test_a_guest_can_open_help(): void
    {
        $this->get(route('academy.help'))
            ->assertStatus(200)
            ->assertSee('Finishing a lesson')
            ->assertSee('Your certificate');
    }

    public function test_a_logged_in_student_can_open_help(): void
    {
        $student = User::create([
            'name' => 'Student',
            'email' => 'student@example.com',
            'password' => bcrypt('secret'),
            'role' => 'learner',
        ]);

        $this->actingAs($student)->get(route('academy.help'))->assertStatus(200);
    }

    public function test_every_section_is_in_the_contents_list(): void
    {
        $chapters = collect(AdminGuide::parse(file_get_contents(base_path('docs/learner-guide.md'))))
            ->where('heading', '!=', '');

        $this->assertNotEmpty($chapters);

        $response = $this->get(route('academy.help'));

        foreach ($chapters as $chapter) {
            $response->assertSee('href="#'.$chapter['id'].'"', false)
                ->assertSee('id="'.$chapter['id'].'"', false);
        }
    }

    public function test_authoring_notes_in_the_file_are_not_rendered(): void
    {
        $this->get(route('academy.help'))
            ->assertDontSee('This file IS the Help page');
    }

    public function test_the_header_links_to_help_on_every_page(): void
    {
        $this->get(route('academy.home'))
            ->assertSee('href="'.route('academy.help').'"', false)
            ->assertSee('aria-label="Help"', false);
    }
}
