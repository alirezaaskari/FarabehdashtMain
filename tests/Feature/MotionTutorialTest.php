<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Support\Help\Motion\MotionScript;
use App\Support\Help\Motion\MotionTutorials;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * آموزش‌های متحرک کنار راهنمای بخش‌ها (resources/motion هر ماژول).
 */
final class MotionTutorialTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_tutorial_belongs_to_a_real_help_topic(): void
    {
        $topics = [...array_keys(config('help')), ...array_keys(config('help.panel')), 'tool'];

        foreach ($this->app->make(MotionTutorials::class)->topics() as $topic) {
            $this->assertContains($topic, $topics, "آموزش {$topic} راهنمایی با این کلید ندارد.");
        }
    }

    public function test_every_tutorial_is_short_captioned_and_has_chapters(): void
    {
        $tutorials = $this->app->make(MotionTutorials::class);
        $this->assertNotEmpty($tutorials->topics());

        foreach ($tutorials->topics() as $topic) {
            $script = $tutorials->for($topic);
            $this->assertInstanceOf(MotionScript::class, $script);

            // کوتاه بماند: آموزش بلندتر از یک دقیقه و نیم را کسی تا آخر نمی‌بیند.
            $this->assertGreaterThan(15, $script->duration, $topic);
            $this->assertLessThanOrEqual(90, $script->duration, $topic);
            $this->assertNotEmpty($script->chapters(), $topic);

            foreach ($script->scenes as $scene) {
                $this->assertGreaterThan($scene['start'], $scene['end'], $topic);
                $this->assertNotSame('', $scene['caption'], $topic);
            }
        }
    }

    public function test_a_scene_without_a_caption_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        MotionScript::build('broken', ['title' => 'آزمون', 'scenes' => [['chapter' => 'یک', 'widgets' => []]]]);
    }

    public function test_the_cursor_clicks_every_action_in_order(): void
    {
        $script = MotionScript::build('sample', [
            'title' => 'نمونه',
            'scenes' => [[
                'chapter' => 'یک',
                'caption' => 'یک صحنه آزمایشی با دو کار پشت هم.',
                'widgets' => [
                    ['type' => 'fields', 'items' => [['label' => 'نام', 'value' => 'سارا', 'typed' => true]]],
                    ['type' => 'button', 'label' => 'ذخیره'],
                ],
            ]],
        ]);

        $clicks = array_values(array_filter($script->path, fn (array $point): bool => ($point[2] ?? false) === true));

        $this->assertSame(['w1-0', 'w2'], array_column($clicks, 1));
        $this->assertLessThan($clicks[1][0], $clicks[0][0]);
        $this->assertLessThanOrEqual($script->scenes[0]['end'], $clicks[1][0]);
    }

    public function test_the_report_builder_page_plays_its_tutorial_with_a_text_fallback(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('data-motion="reports"', false)
            ->assertSee('قدم ۱، منبع');
    }
}
