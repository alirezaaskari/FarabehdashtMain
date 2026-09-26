<?php

declare(strict_types=1);

namespace App\Modules\Core\Tests;

use App\Contracts\HomepageSource;
use App\Modules\Core\Providers\CoreServiceProvider;
use App\Modules\Core\Services\HomePage;
use App\Support\Home\HomeItem;
use App\Support\Home\HomeLayout;
use App\Support\Home\HomeSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * صفحه اصلی از بخش‌های ماژول‌ها ساخته می‌شود، نه از قالب.
 *
 * معیار پذیرش همان قاعده ۲ است: خاموش‌کردن یک ماژول نباید صفحه اصلی را
 * بشکند یا قاب خالی روی آن جا بگذارد.
 */
final class HomePageTest extends TestCase
{
    // ماژول‌های محتوایی واقعی روی صفحه اصلی پرس‌وجو می‌زنند.
    use RefreshDatabase;

    public function test_homepage_opens_even_when_no_module_has_content(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('از اندازه‌گیری در کارگاه', false);
    }

    public function test_sections_come_in_their_declared_order_not_registration_order(): void
    {
        $page = new HomePage([
            new FakeHomepageSource(self::section('courses', order: 50)),
            new FakeHomepageSource(self::section('tools', order: 10)),
            new FakeHomepageSource(self::section('encyclopedia', order: 20)),
        ]);

        $this->assertSame(
            ['tools', 'encyclopedia', 'courses'],
            array_map(static fn (HomeSection $s): string => $s->key, $page->sections()),
        );
    }

    public function test_a_section_without_items_never_reaches_the_page(): void
    {
        $page = new HomePage([
            new FakeHomepageSource(self::section('tools', order: 10, items: [])),
            new FakeHomepageSource(null),
            new FakeHomepageSource(self::section('courses', order: 50)),
        ]);

        $sections = $page->sections();

        $this->assertCount(1, $sections);
        $this->assertSame('courses', $sections[0]->key);
    }

    public function test_two_half_width_sections_share_a_row_and_a_lonely_one_goes_full_width(): void
    {
        // دانشنامه و بانک مواد کنار هم؛ اگر یکی خاموش باشد، دیگری نیمه صفحه
        // را خالی نمی‌گذارد.
        $page = new HomePage([
            new FakeHomepageSource(self::section('tools', order: 10)),
            new FakeHomepageSource(self::section('encyclopedia', order: 20, layout: HomeLayout::List)),
            new FakeHomepageSource(self::section('chemicals', order: 30, layout: HomeLayout::Panel)),
            new FakeHomepageSource(self::section('shop', order: 40, layout: HomeLayout::List)),
        ]);

        $this->assertSame(
            [['tools'], ['encyclopedia', 'chemicals'], ['shop']],
            array_map(
                static fn (array $row): array => array_map(static fn (HomeSection $s): string => $s->key, $row),
                $page->rows(),
            ),
        );
    }

    public function test_up_to_three_tiles_share_a_row_after_the_half_width_pair(): void
    {
        $page = new HomePage([
            new FakeHomepageSource(self::section('encyclopedia', order: 20, layout: HomeLayout::List)),
            new FakeHomepageSource(self::section('chemicals', order: 25, layout: HomeLayout::Panel)),
            new FakeHomepageSource(self::section('expert', order: 40, layout: HomeLayout::Tile)),
            new FakeHomepageSource(self::section('courses', order: 45, layout: HomeLayout::Tile)),
            new FakeHomepageSource(self::section('shop', order: 50, layout: HomeLayout::Tile)),
            new FakeHomepageSource(self::section('extra', order: 60, layout: HomeLayout::Tile)),
        ]);

        $this->assertSame(
            [['encyclopedia', 'chemicals'], ['expert', 'courses', 'shop'], ['extra']],
            array_map(
                static fn (array $row): array => array_map(static fn (HomeSection $s): string => $s->key, $row),
                $page->rows(),
            ),
        );
    }

    public function test_an_invitation_tile_stays_even_without_fresh_content(): void
    {
        $invitation = new HomeSection(
            key: 'expert',
            title: 'پرسش از متخصص',
            lede: 'بپرس.',
            items: [],
            order: 40,
            layout: HomeLayout::Tile,
            keepWhenEmpty: true,
        );

        $this->assertCount(1, (new HomePage([new FakeHomepageSource($invitation)]))->sections());
    }

    public function test_the_hero_shows_the_live_calculator_instead_of_a_decorative_scene(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['از اندازه‌گیری در کارگاه', 'محاسبه سریع غلظت', 'امروز چه کاری داری؟'], false)
            ->assertDontSee('data-motion', false);
    }

    public function test_a_registered_module_section_is_rendered(): void
    {
        $this->app->bind('test.home.source', static fn (): HomepageSource => new FakeHomepageSource(
            self::section('tools', order: 10),
        ));
        $this->app->tag(['test.home.source'], CoreServiceProvider::HOMEPAGE_SOURCES);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('عنوان بخش tools', false);
        $response->assertSee('ردیف نمونه', false);
    }

    /** @param  list<HomeItem>|null  $items */
    private static function section(string $key, int $order, ?array $items = null, HomeLayout $layout = HomeLayout::Cards): HomeSection
    {
        return new HomeSection(
            key: $key,
            title: 'عنوان بخش '.$key,
            lede: 'توضیح بخش.',
            items: $items ?? [new HomeItem(title: 'ردیف نمونه', url: '/', kicker: 'نمونه')],
            order: $order,
            layout: $layout,
        );
    }
}

/** منبع ساختگی بخش صفحه اصلی، به‌جای یک ماژول محتوایی واقعی. */
final readonly class FakeHomepageSource implements HomepageSource
{
    public function __construct(private ?HomeSection $section) {}

    public function homeSection(): ?HomeSection
    {
        return $this->section;
    }
}
