<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Admin JS Academy',
            'email' => 'admin@jsacademy.com',
            'password' => bcrypt('password'),
            'xp' => 9999,
        ]);

        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'xp' => 1820,
            'streak' => 12,
            'diamonds' => 450,
            'league' => 'Gold III',
        ]);

        User::factory()->create([
            'name' => 'Alex S.',
            'email' => 'alex@example.com',
            'xp' => 2450,
            'league' => 'Gold III',
        ]);
        
        User::factory()->create([
            'name' => 'Maria G.',
            'email' => 'maria@example.com',
            'xp' => 2100,
            'league' => 'Gold III',
        ]);

        $this->call(TrackSeeder::class);
    }
}
