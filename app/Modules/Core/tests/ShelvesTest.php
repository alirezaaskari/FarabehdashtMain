<?php

declare(strict_types=1);

namespace App\Modules\Core\Tests;

use App\Contracts\ShelfSource;
use App\Support\Site\Shelves;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * پیوند ورود به فهرست خالی در منو و صفحه اصلی نمی‌آید؛ پاسخ همه فهرست‌ها یک پرس‌وجوست.
 */
final class ShelvesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/__shelf-empty', static fn () => '')->name('test.shelf.empty');
        Route::get('/__shelf-filled', static fn () => '')->name('test.shelf.filled');
        Route::get('/__shelf-unknown', static fn () => '')->name('test.shelf.unknown');
        Route::getRoutes()->refreshNameLookups();
    }

    public function test_only_a_list_with_something_published_is_open(): void
    {
        $shelves = $this->shelves();

        $this->assertFalse($shelves->open('test.shelf.empty'));
        $this->assertTrue($shelves->open('test.shelf.filled'));
    }

    public function test_a_route_no_module_reports_on_stays_open_but_a_missing_route_does_not(): void
    {
        $shelves = $this->shelves();

        $this->assertTrue($shelves->open('test.shelf.unknown'));
        $this->assertFalse($shelves->open('test.shelf.missing'));
    }

    public function test_every_list_is_answered_by_one_query_per_request(): void
    {
        $shelves = $this->shelves();

        DB::enableQueryLog();
        $shelves->open('test.shelf.empty');
        $shelves->open('test.shelf.filled');
        $shelves->open('test.shelf.empty');

        $this->assertCount(1, DB::getQueryLog());
    }

    public function test_empty_sections_lose_their_homepage_tiles_and_menu_links(): void
    {
        // پایگاه داده تازه: کاریابی، مشاوران و آزمون هنوز چیزی ندارند؛ ابزارها همیشه هستند.
        $this->get('/')
            ->assertOk()
            ->assertDontSee('دنبال کار می‌گردم')
            ->assertDontSee('مشاور می‌خواهم')
            ->assertDontSee('برای آزمون آماده می‌شوم')
            ->assertDontSee('id="home-career"', false)
            ->assertSee('در محل اندازه می‌گیرم');
    }

    private function shelves(): Shelves
    {
        return new Shelves(
            [new FakeShelfSource([
                'test.shelf.empty' => DB::table('users'),
                'test.shelf.filled' => DB::table('migrations'),
            ])],
            DB::connection(),
            $this->app->make('router'),
        );
    }
}

final readonly class FakeShelfSource implements ShelfSource
{
    /** @param  array<string, Builder>  $shelves */
    public function __construct(private array $shelves) {}

    public function shelves(): array
    {
        return $this->shelves;
    }
}
