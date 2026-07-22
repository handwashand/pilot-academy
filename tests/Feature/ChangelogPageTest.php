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
            'is_admin' => true,
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
            'is_admin' => false,
        ]);

        $this->actingAs($student)
            ->get('/admin/changelog')
            ->assertStatus(403);
    }

    public function test_dashboard_shows_the_whats_new_block(): void
    {
        $admin = User::create([
            'name' => 'Pilot Admin',
            'email' => 'admin@pilot.local',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        // One of the newest changelog entries, shown in the block.
        $recentEntry = 'Certificate check no longer shows a score';

        $this->actingAs($admin)
            ->get('/admin')
            ->assertStatus(200)
            ->assertSee($recentEntry)
            ->assertSee('See all updates', false);
    }
}
