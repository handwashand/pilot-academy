<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGuidePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_the_guide_page(): void
    {
        $admin = User::create([
            'name' => 'Pilot Admin',
            'email' => 'admin@pilot.local',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get('/admin/admin-guide')
            ->assertStatus(200)
            ->assertSee('Quick start')
            ->assertSee('Certificates');
    }

    public function test_students_cannot_open_the_guide_page(): void
    {
        $student = User::create([
            'name' => 'Student',
            'email' => 'student@example.com',
            'password' => bcrypt('secret'),
            'role' => 'learner',
        ]);

        $this->actingAs($student)
            ->get('/admin/admin-guide')
            ->assertStatus(403);
    }
}
