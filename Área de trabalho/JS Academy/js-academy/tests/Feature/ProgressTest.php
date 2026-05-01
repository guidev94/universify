<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Track;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProgressTest extends TestCase
{
    use RefreshDatabase;

    private function createLesson(int $xp = 100): Lesson
    {
        $track = Track::create(['name' => 'T', 'slug' => 'test-t-'.uniqid(), 'description' => 'x']);
        return $track->lessons()->create([
            'title' => 'L', 'slug' => 'test-l-'.uniqid(),
            'content' => 'c', 'xp_reward' => $xp, 'order' => 1,
        ]);
    }

    public function test_complete_lesson_returns_success_for_guest_using_fallback(): void
    {
        $user = User::factory()->create(['xp' => 0]);
        $lesson = $this->createLesson();

        $this->postJson("/progress/complete-lesson/{$lesson->id}")
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_complete_lesson_returns_404_for_invalid_lesson(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->postJson('/progress/complete-lesson/999999')
            ->assertStatus(404);
    }

    public function test_ranking_json_returns_paginated_users(): void
    {
        User::factory()->count(15)->create(['xp' => rand(100, 1000)]);

        $response = $this->getJson('/progress/ranking-json');
        $response->assertStatus(200);
        $this->assertCount(10, $response->json());
    }

    public function test_home_page_loads_without_auth(): void
    {
        Track::create(['name' => 'Trilha 1', 'slug' => 'trilha-1', 'description' => 'desc']);
        User::factory()->create();

        $this->get('/')->assertStatus(200)->assertSee('Javascript');
    }

    public function test_ranking_page_loads(): void
    {
        User::factory()->count(3)->create(['xp' => rand(100, 500)]);
        $this->get('/ranking')->assertStatus(200);
    }

    public function test_desafios_page_loads(): void
    {
        $this->get('/desafios')->assertStatus(200);
    }

    public function test_profile_requires_authentication(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_profile(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/profile')->assertStatus(200);
    }

    public function test_skip_track_deducts_diamonds(): void
    {
        $user = User::factory()->create(['diamonds' => 500]);
        $track = Track::create(['name' => 'T', 'slug' => 'skip-t-'.uniqid(), 'description' => 'x']);

        $this->actingAs($user)
            ->postJson("/progress/skip-track/{$track->id}")
            ->assertJson(['success' => true]);

        $this->assertEquals(300, $user->fresh()->diamonds);
    }

    public function test_skip_track_fails_without_diamonds(): void
    {
        $user = User::factory()->create(['diamonds' => 50]);
        $track = Track::create(['name' => 'T2', 'slug' => 'skip-t2-'.uniqid(), 'description' => 'x']);

        $this->actingAs($user)
            ->postJson("/progress/skip-track/{$track->id}")
            ->assertStatus(400);
    }
}
