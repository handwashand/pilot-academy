<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangelogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_the_changelog_page(): void
    {
        $admin = User::create([
            'name' => 'Pilot Admin',
            'email' => 'admin@pilot.local',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get('/admin/changelog')
            ->assertStatus(200)
            ->assertSee('Final quiz');
    }

    public function test_students_cannot_open_the_changelog_page(): void
    {
        $student = User::create([
            'name' => 'Student',
            'email' => 'student@example.com',
            'password' => bcrypt('secret'),
            'role' => 'learner',
        ]);

        $this->actingAs($student)
            ->get('/admin/changelog')
            ->assertStatus(403);
    }
}
