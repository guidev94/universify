<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Lesson;
use App\Models\Track;
use App\Models\Badge;
use App\Models\Progress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class GamificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeTrackWithLesson(int $xp = 100): array
    {
        $track = Track::create(['name' => 'Test Track', 'slug' => 'test-track', 'description' => 'Test']);
        $lesson = $track->lessons()->create([
            'title' => 'Test Lesson', 'slug' => 'test-lesson',
            'content' => 'Test', 'xp_reward' => $xp, 'order' => 1,
        ]);
        return [$track, $lesson];
    }

    public function test_user_gains_xp_on_lesson_completion(): void
    {
        $user = User::factory()->create(['xp' => 0]);
        [, $lesson] = $this->makeTrackWithLesson(100);

        $this->actingAs($user)
            ->postJson("/progress/complete-lesson/{$lesson->id}")
            ->assertJson(['success' => true]);

        $this->assertEquals(100, $user->fresh()->xp);
    }

    public function test_lesson_completion_is_idempotent(): void
    {
        $user = User::factory()->create(['xp' => 0]);
        [, $lesson] = $this->makeTrackWithLesson(100);

        $this->actingAs($user)->postJson("/progress/complete-lesson/{$lesson->id}");
        $this->actingAs($user)->postJson("/progress/complete-lesson/{$lesson->id}");

        $this->assertEquals(100, $user->fresh()->xp);
    }

    public function test_user_level_increases_at_1000_xp(): void
    {
        $user = User::factory()->create(['xp' => 950]);
        [, $lesson] = $this->makeTrackWithLesson(100);

        $this->actingAs($user)->postJson("/progress/complete-lesson/{$lesson->id}");

        $fresh = $user->fresh();
        $this->assertEquals(1050, $fresh->xp);
        $this->assertEquals(2, $fresh->level);
    }

    public function test_user_receives_badge_on_track_completion(): void
    {
        $user = User::factory()->create(['xp' => 0]);
        [$track, $lesson] = $this->makeTrackWithLesson(50);

        $badge = Badge::create([
            'name' => 'Test Badge', 'icon' => '🏆',
            'description' => 'Complete track', 'track_id' => $track->id,
        ]);

        $this->actingAs($user)->postJson("/progress/complete-lesson/{$lesson->id}");

        $this->assertTrue($user->fresh()->badges->contains($badge));
    }

    public function test_streak_increments_when_studying_consecutive_days(): void
    {
        $user = User::factory()->create([
            'streak' => 5,
            'streak_last_completed_at' => Carbon::yesterday(),
        ]);
        [, $lesson] = $this->makeTrackWithLesson(10);

        $this->actingAs($user)->postJson("/progress/complete-lesson/{$lesson->id}");

        $this->assertEquals(6, $user->fresh()->streak);
    }

    public function test_streak_stays_same_when_studying_twice_same_day(): void
    {
        $user = User::factory()->create([
            'streak' => 5,
            'streak_last_completed_at' => Carbon::today(),
        ]);
        [, $lesson] = $this->makeTrackWithLesson(10);

        $this->actingAs($user)->postJson("/progress/complete-lesson/{$lesson->id}");

        $this->assertEquals(5, $user->fresh()->streak);
    }

    public function test_streak_resets_when_day_is_skipped(): void
    {
        $user = User::factory()->create([
            'streak' => 10,
            'streak_last_completed_at' => Carbon::now()->subDays(2),
            'streak_freeze_count' => 0,
        ]);
        [, $lesson] = $this->makeTrackWithLesson(10);

        $this->actingAs($user)->postJson("/progress/complete-lesson/{$lesson->id}");

        $this->assertEquals(1, $user->fresh()->streak);
    }

    public function test_streak_freeze_prevents_reset(): void
    {
        $user = User::factory()->create([
            'streak' => 10,
            'streak_last_completed_at' => Carbon::now()->subDays(2),
            'streak_freeze_count' => 1,
        ]);
        [, $lesson] = $this->makeTrackWithLesson(10);

        $this->actingAs($user)->postJson("/progress/complete-lesson/{$lesson->id}");

        $fresh = $user->fresh();
        $this->assertEquals(10, $fresh->streak);
        $this->assertEquals(0, $fresh->streak_freeze_count);
    }

    public function test_freeze_streak_deducts_diamonds(): void
    {
        $user = User::factory()->create(['diamonds' => 100]);

        $this->actingAs($user)
            ->postJson('/progress/freeze-streak')
            ->assertJson(['success' => true]);

        $fresh = $user->fresh();
        $this->assertEquals(50, $fresh->diamonds);
        $this->assertEquals(1, $fresh->streak_freeze_count);
    }

    public function test_freeze_streak_fails_without_enough_diamonds(): void
    {
        $user = User::factory()->create(['diamonds' => 30]);

        $this->actingAs($user)
            ->postJson('/progress/freeze-streak')
            ->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    public function test_update_xp_endpoint_works(): void
    {
        $user = User::factory()->create(['xp' => 0]);

        $this->actingAs($user)
            ->postJson('/progress/update-xp', ['xp' => 200])
            ->assertJson(['success' => true, 'xp' => 200]);
    }

    public function test_ranking_endpoint_returns_top_users(): void
    {
        User::factory()->create(['xp' => 500, 'name' => 'Top User']);
        User::factory()->create(['xp' => 100, 'name' => 'Low User']);

        $response = $this->getJson('/progress/ranking-json');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(2, $data);
        $this->assertEquals('Top User', $data[0]['name']);
    }
}
