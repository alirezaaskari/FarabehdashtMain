<?php

declare(strict_types=1);

namespace App\Modules\Tools\Tests;

use App\Modules\Tools\Actions\UpdateToolSettings;
use App\Modules\Tools\Services\ToolAdvisor;
use App\Modules\Tools\Services\ToolCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * دستیار انتخاب ابزار.
 */
final class ToolAdvisorTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_situation_points_at_a_tool_that_exists(): void
    {
        // موقعیتی که به ابزار ناموجود اشاره کند، بی‌صدا از فهرست می‌افتد و
        // کسی نمی‌فهمد. این تست همان را می‌گیرد.
        /** @var list<array<string, string>> $situations */
        $situations = (array) config('tools.advisor', []);
        $catalog = $this->app->make(ToolCatalog::class);

        $this->assertNotEmpty($situations);

        foreach ($situations as $entry) {
            $this->assertTrue(
                $catalog->has($entry['tool']),
                sprintf('موقعیت «%s» به ابزار ناموجود «%s» اشاره می‌کند.', $entry['situation'], $entry['tool']),
            );
        }
    }

    public function test_every_situation_gives_a_reason_not_just_a_name(): void
    {
        /** @var list<array<string, string>> $situations */
        $situations = (array) config('tools.advisor', []);

        foreach ($situations as $entry) {
            $this->assertNotSame('', trim($entry['reason']));
        }
    }

    public function test_a_disabled_tool_is_never_suggested(): void
    {
        // پیشنهاد ابزاری که باز نمی‌شود، بدتر از نبودِ پیشنهاد است.
        $this->app->make(UpdateToolSettings::class)->setAvailability('noise-dose', false, null);

        $slugs = array_map(
            static fn (array $s): string => $s['tool']->slug(),
            $this->app->make(ToolAdvisor::class)->suggestions(),
        );

        $this->assertNotContains('noise-dose', $slugs);
    }

    public function test_the_page_renders_every_suggestion(): void
    {
        $response = $this->get(route('tools.advisor'))->assertOk();

        foreach ($this->app->make(ToolAdvisor::class)->suggestions() as $suggestion) {
            $response->assertSee($suggestion['situation']);
        }
    }

    public function test_the_hub_links_to_the_advisor(): void
    {
        $this->get(route('tools.index'))
            ->assertOk()
            ->assertSee(route('tools.advisor'), escape: false);
    }
}
