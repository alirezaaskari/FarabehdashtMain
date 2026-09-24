<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Product;
use App\Modules\Courses\Domain\Course;
use App\Modules\Courses\Domain\Enums\CourseStatus;
use App\Modules\Workspace\Actions\PublishLegalVersion;
use App\Modules\Workspace\Domain\Enums\LegalChange;
use App\Modules\Workspace\Domain\Enums\LegalDocument;
use Illuminate\Support\Str;

/**
 * سایت عمومی با یک نمونه از هر نوع صفحه، و خزیدن در آن از راه نقشه سایت —
 * همان‌طور که موتور جست‌وجو می‌خزد. پایه نگهبان‌های سئو و بودجه سرعت (بخش ۱۷).
 */
trait SeedsPublicSite
{
    private function seedPublicSite(): void
    {
        $this->artisan('fbh:seed-encyclopedia')->assertSuccessful();
        $this->artisan('fbh:seed-chemicals')->assertSuccessful();

        // دو نمونه از هر کدام: بارگذاری تنبل در حلقه فقط وقتی دیده می‌شود که
        // مجموعه بیش از یک عضو داشته باشد (PerformanceBudgetTest).
        foreach ([['noise-basics', 'مبانی ارزیابی صدا'], ['heat-stress-basics', 'مبانی ارزیابی تنش گرمایی']] as [$slug, $title]) {
            Course::query()->create([
                'uuid' => (string) Str::uuid7(),
                'instructor_user_id' => User::factory()->create()->id,
                'slug' => $slug,
                'title' => $title,
                'description' => 'اندازه‌گیری میدانی و محاسبه شاخص‌ها در محیط کار، با مثال‌های واقعی.',
                'price_toman' => 150_000,
                'status' => CourseStatus::Published,
            ]);
        }

        foreach ([['noise-report-template', 'قالب گزارش ارزیابی صدا'], ['heat-report-template', 'قالب گزارش ارزیابی تنش گرمایی']] as [$slug, $title]) {
            Product::query()->create([
                'uuid' => (string) Str::uuid7(),
                'vendor_user_id' => User::factory()->create()->id,
                'slug' => $slug,
                'title' => $title,
                'description' => 'قالب آماده گزارش اندازه‌گیری.',
                'price_toman' => 90_000,
                'status' => ProductStatus::Published,
            ]);
        }

        $this->app->make(PublishLegalVersion::class)
            ->handle(LegalDocument::Terms, 'متن شرایط استفاده.', LegalChange::Minor, null, null, null);
    }

    /** @return list<string> */
    private function sitemapUrls(): array
    {
        $urls = [];
        $index = simplexml_load_string($this->get('/sitemap.xml')->assertOk()->getContent() ?: '');
        $this->assertNotFalse($index);

        foreach ($index->sitemap as $file) {
            $urlset = simplexml_load_string($this->get((string) $file->loc)->assertOk()->getContent() ?: '');
            $this->assertNotFalse($urlset);

            foreach ($urlset->url as $url) {
                $urls[] = (string) $url->loc;
            }
        }

        return $urls;
    }
}
