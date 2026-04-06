<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Lesson;
use App\Models\Track;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LessonFlowTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * A basic browser test example.
     */
    public function testLessonFlow(): void
    {
        // Seed needed data
        $track = Track::create(['name' => 'Basic', 'slug' => 'basic', 'description' => '...']);
        $lesson = $track->lessons()->create([
            'title' => 'Hello World',
            'slug' => 'hello-world',
            'content' => 'Print Hello World',
            'starter_code' => '',
            'solution_code' => 'console.log("Hello World")',
            'xp_reward' => 100,
            'order' => 1
        ]);

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'xp' => 0
        ]);

        $this->browse(function (Browser $browser) use ($user, $lesson) {
            $browser->loginAs($user)
                    ->visit('/')
                    ->assertSee('The Path')
                    // Open lesson (clicking the link/button)
                    ->clickLink('Continuar Aula')
                    // Wait for Monaco
                    ->waitFor('#monaco-editor')
                    // Type code (Simulated since Monaco is complex to type in directly via Dusk without custom JS)
                    ->script("window.editor.setValue('console.log(\"Hello World\")')");

            $browser->click('#validate-btn')
                    ->waitForText('XP +100')
                    ->assertSee('XP +100');
        });
    }
}
