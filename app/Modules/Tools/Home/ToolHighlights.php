<?php

declare(strict_types=1);

namespace App\Modules\Tools\Home;

use App\Contracts\HomepageSource;
use App\Modules\Tools\Domain\ResolvedTool;
use App\Modules\Tools\Services\ToolCatalog;
use App\Support\Home\HomeItem;
use App\Support\Home\HomeSection;
use App\Support\PersianNumber;
use Illuminate\Support\Facades\Route;

/**
 * ابزارها بر اساس عامل زیان‌آور: یک کارت برای هر گروه، با شمار ابزارها و
 * نام چند ابزارش، به‌اضافه دستیار انتخاب ابزار برای کسی که نمی‌داند از کجا
 * شروع کند.
 *
 * کارشناس با «صدا دارم» یا «ماده شیمیایی دارم» می‌آید، نه با نام فرمول؛ پس
 * صفحه اصلی گستره گروه‌ها را نشان می‌دهد و فهرست کامل در مرکز ابزارهاست.
 *
 * فقط ابزارهای قابل استفاده شمرده می‌شوند: ابزاری که مدیر خاموشش کرده روی
 * صفحه اصلی تبلیغ نمی‌شود.
 */
final readonly class ToolHighlights implements HomepageSource
{
    /** چند نام ابزار زیر عنوان هر گروه. */
    private const SAMPLES = 3;

    public function __construct(private ToolCatalog $catalog) {}

    public function homeSection(): ?HomeSection
    {
        if (! Route::has('tools.index')) {
            return null;
        }

        $groups = $this->catalog->grouped();

        // گروه پرابزارتر اول؛ برای گروه‌های هم‌اندازه ترتیب enum می‌ماند.
        uasort($groups, static fn (array $a, array $b): int => count($b['tools']) <=> count($a['tools']));

        $items = [];
        $total = 0;

        foreach ($groups as $group) {
            $total += count($group['tools']);

            $items[] = new HomeItem(
                title: $group['category']->label(),
                url: route('tools.index').'#group-'.$group['category']->value,
                kicker: PersianNumber::format(count($group['tools'])).' ابزار',
                summary: implode('، ', array_map(
                    static fn (ResolvedTool $tool): string => $tool->definition->title,
                    array_slice($group['tools'], 0, self::SAMPLES),
                )),
                icon: $group['category']->icon(),
            );
        }

        return new HomeSection(
            key: 'tools',
            title: PersianNumber::format($total).' ابزار محاسباتی، بر اساس عامل زیان‌آور',
            lede: 'هر ابزار فرمول نسخه‌دار، منبع علمی و راهنمای تفسیر نتیجه دارد.',
            items: $items,
            order: 10,
            art: 'home-section-tools',
            moreUrl: route('tools.index'),
            moreLabel: 'همه ابزارها',
            feature: Route::has('tools.advisor') ? new HomeItem(
                title: 'نمی‌دانی کدام ابزار؟',
                url: route('tools.advisor'),
                kicker: 'شروع دستیار',
                summary: 'دستیار انتخاب ابزار با سه پرسش کوتاه ابزار، مقاله و فایل مناسب را پیشنهاد می‌دهد.',
                icon: 'compass',
            ) : null,
        );
    }
}
