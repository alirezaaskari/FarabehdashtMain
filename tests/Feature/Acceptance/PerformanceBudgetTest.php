<?php

declare(strict_types=1);

namespace Tests\Feature\Acceptance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\SeedsPublicSite;
use Tests\TestCase;

/**
 * بودجه سرعت نسخه ۱ (بخش ۱۷، DEC-34).
 *
 * دو چیز که روی هاست اشتراکی بیشترین اثر را دارند و بی‌سروصدا بد می‌شوند:
 * حجم CSS و JS که هر صفحه بار می‌کند، و تعداد پرس‌وجوی پایگاه داده هر صفحه
 * عمومی (نگهبان N+1 — یک حلقه بی‌eager-load روی صد مقاله، صد پرس‌وجوست).
 * LCP و CLS فقط روی هاست واقعی معنا دارند و در docs/acceptance/v1 دستی‌اند.
 */
final class PerformanceBudgetTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPublicSite;

    private const CSS_BUDGET_BYTES = 60 * 1024;

    private const JS_BUDGET_BYTES = 50 * 1024;

    private const QUERY_BUDGET = 25;

    public function test_the_built_css_and_js_stay_within_budget(): void
    {
        $manifest = json_decode((string) file_get_contents(public_path('build/manifest.json')), true);
        $this->assertIsArray($manifest, 'public/build/manifest.json باید ساخته شده باشد.');

        foreach (['resources/css/app.css' => self::CSS_BUDGET_BYTES, 'resources/js/app.js' => self::JS_BUDGET_BYTES] as $entry => $budget) {
            $this->assertArrayHasKey($entry, $manifest);

            $size = (int) filesize(public_path('build/'.$manifest[$entry]['file']));

            $this->assertLessThanOrEqual($budget, $size, sprintf('%s: %d بایت، سقف %d.', $entry, $size, $budget));
        }
    }

    public function test_every_public_page_stays_within_the_query_budget(): void
    {
        $this->seedPublicSite();

        foreach ($this->sitemapUrls() as $url) {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $this->get($url)->assertOk();

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            $this->assertLessThanOrEqual(self::QUERY_BUDGET, $count, "{$url}: {$count} پرس‌وجو، سقف ".self::QUERY_BUDGET.'.');
        }
    }

    public function test_no_public_page_lazy_loads_inside_a_loop(): void
    {
        // سقف پرس‌وجو با سه ماده نمونه، N+1 را پنهان می‌کند؛ با صد ماده نه.
        // Laravel هر بارگذاری تنبل روی مدلی که عضو یک مجموعه است را همین‌جا
        // خطا می‌کند — دقیقاً تعریف N+1 — و صفحه ۵۰۰ می‌دهد.
        $this->seedPublicSite();

        Model::preventLazyLoading();

        try {
            foreach ($this->sitemapUrls() as $url) {
                $this->get($url)->assertOk();
            }
        } finally {
            Model::preventLazyLoading(false);
        }
    }
}
