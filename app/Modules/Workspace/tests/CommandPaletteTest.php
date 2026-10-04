<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Tests;

use App\Models\User;
use App\Support\Search\QuickAction;
use App\Support\Search\SearchQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * پنل فرمان (Ctrl+K): جست‌وجوی سراسری به‌اضافه کارهای هر ماژول.
 */
final class CommandPaletteTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_page_carries_the_palette_dialog(): void
    {
        $this->get('/tools')
            ->assertOk()
            ->assertSee('<dialog data-palette', escape: false)
            ->assertSee('data-search-palette', escape: false)
            ->assertSee('data-palette-open', escape: false);
    }

    public function test_an_empty_palette_lists_the_signed_in_users_actions(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('workspace.search.suggest', ['palette' => 1]))
            ->assertOk()
            ->assertSee('کارها')
            ->assertSee('گزارش تازه')
            ->assertSee(route('reports.create'), escape: false);
    }

    public function test_a_guest_sees_only_public_actions(): void
    {
        $this->get(route('workspace.search.suggest', ['palette' => 1]))
            ->assertOk()
            ->assertSee('همه ابزارها')
            ->assertDontSee('گزارش تازه')
            ->assertDontSee('کیف پول');
    }

    public function test_typing_narrows_actions_by_title_or_hidden_keywords(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('workspace.search.suggest', ['palette' => 1, 'q' => 'ساخت گزارش']))
            ->assertOk()
            ->assertSee('گزارش تازه')
            ->assertDontSee('کیف پول');
    }

    public function test_the_header_suggestions_never_carry_actions(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('workspace.search.suggest', ['q' => 'گزارش']))
            ->assertOk()
            ->assertDontSee('aria-label="کارها"', escape: false);
    }

    public function test_an_action_matches_every_word_in_any_order(): void
    {
        $action = new QuickAction('پروژه تازه', '/p', 'ساخت جدید اندازه‌گیری');

        $this->assertTrue($action->matches(SearchQuery::from('اندازه گیری پروژه')));
        $this->assertTrue($action->matches(SearchQuery::from('جديد')));
        $this->assertFalse($action->matches(SearchQuery::from('پروژه گزارش')));
    }
}
