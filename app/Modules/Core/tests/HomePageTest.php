<?php

declare(strict_types=1);

namespace App\Modules\Core\Tests;

use App\Contracts\HomepageSource;
use App\Modules\Core\Providers\CoreServiceProvider;
use App\Modules\Core\Services\HomePage;
use App\Support\Home\HomeItem;
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
        $response->assertSee('یاد بگیر، محاسبه کن،', false);
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
    private static function section(string $key, int $order, ?array $items = null): HomeSection
    {
        return new HomeSection(
            key: $key,
            title: 'عنوان بخش '.$key,
            lede: 'توضیح بخش.',
            items: $items ?? [new HomeItem(title: 'ردیف نمونه', url: '/', kicker: 'نمونه')],
            order: $order,
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
