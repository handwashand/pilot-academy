<?php

namespace Tests\Feature;

use App\Filament\Pages\AdminGuide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGuidePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_the_guide_page(): void
    {
        $this->actingAs($this->user('admin@pilot.local', 'admin'))
            ->get('/admin/admin-guide')
            ->assertStatus(200)
            ->assertSee('Quick start')
            ->assertSee('Certificates');
    }

    public function test_creators_can_open_the_guide_page(): void
    {
        $this->actingAs($this->user('creator@pilot.local', 'creator'))
            ->get('/admin/admin-guide')
            ->assertStatus(200);
    }

    public function test_students_cannot_open_the_guide_page(): void
    {
        $this->actingAs($this->user('student@example.com', 'learner'))
            ->get('/admin/admin-guide')
            ->assertStatus(403);
    }

    public function test_the_guide_sits_first_in_the_docs_group(): void
    {
        $this->assertSame('Docs', AdminGuide::getNavigationGroup());
        $this->assertSame(1, AdminGuide::getNavigationSort());
    }

    public function test_the_guide_splits_into_sections_at_its_level_two_headings(): void
    {
        $sections = AdminGuide::parse(<<<'MD'
            # Pilot Academy — Admin Guide

            An introduction.

            <!-- an authoring note -->

            ---

            ## 1. Log in

            Open the panel.

            ---

            ## 2. The menu

            ### Courses
            Create courses.
            MD);

        $this->assertSame(['introduction', '1-log-in', '2-the-menu'], array_column($sections, 'id'));
        $this->assertSame('', $sections[0]['heading']);
        $this->assertSame('1. Log in', $sections[1]['heading']);

        // The file's own title is not repeated under the page heading.
        $this->assertStringNotContainsString('Admin Guide', $sections[0]['html']);
        // Section separators and authoring notes never render.
        $this->assertStringNotContainsString('<hr', $sections[1]['html']);
        $this->assertStringNotContainsString('authoring note', $sections[0]['html']);
        // Search text is lowercased and covers the heading and nested headings.
        $this->assertStringContainsString('2. the menu courses create courses', $sections[2]['text']);
    }

    public function test_the_page_has_a_contents_list_and_a_labelled_search(): void
    {
        $sections = AdminGuide::parse(file_get_contents(AdminGuide::guidePath()));
        $chapter = collect($sections)->firstWhere('heading', '!=', '');

        $this->actingAs($this->user('toc@pilot.local', 'admin'))
            ->get('/admin/admin-guide')
            ->assertSee('<label for="guide-search"', false)
            ->assertSee('href="#'.$chapter['id'].'"', false)
            ->assertSee('id="'.$chapter['id'].'"', false);
    }

    private function user(string $email, string $role): User
    {
        return User::create([
            'name' => 'Test '.$role,
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => $role,
        ]);
    }
}
