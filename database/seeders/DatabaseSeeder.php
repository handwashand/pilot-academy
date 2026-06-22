<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@pilot.local'],
            [
                'name' => 'Pilot Admin',
                'password' => Hash::make('password'),
                'is_admin' => true,
            ],
        );

        $this->call(PilotQuickStartSeeder::class);
    }
}
