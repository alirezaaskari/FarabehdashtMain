<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Contracts\HomepageSource;
use App\Support\Home\HomeSection;

/**
 * چیدن بخش‌های صفحه اصلی از روی ماژول‌های ثبت‌شده.
 *
 * بخش بدون ردیف اصلاً ساخته نمی‌شود: صفحه اصلی یک سایت تازه‌نصب باید کوتاه
 * باشد، نه پر از قاب خالی.
 */
final readonly class HomePage
{
    /** @param  iterable<HomepageSource>  $sources */
    public function __construct(private iterable $sources) {}

    /** @return list<HomeSection> */
    public function sections(): array
    {
        $sections = [];

        foreach ($this->sources as $source) {
            $section = $source->homeSection();

            if ($section instanceof HomeSection && $section->items !== []) {
                $sections[] = $section;
            }
        }

        usort($sections, static fn (HomeSection $a, HomeSection $b): int => $a->order <=> $b->order);

        return $sections;
    }

    /**
     * بخش‌ها در ردیف‌های صفحه: دو بخش نیمه‌عرض پشت‌سرهم یک ردیف‌اند و بقیه
     * هرکدام ردیف خودشان. نیمه‌عرض تنها، تمام‌عرض می‌شود تا نیمه صفحه خالی نماند.
     *
     * @return list<list<HomeSection>>
     */
    public function rows(): array
    {
        $rows = [];
        $pending = null;

        foreach ($this->sections() as $section) {
            if (! $section->layout->isHalf()) {
                if ($pending !== null) {
                    $rows[] = [$pending];
                    $pending = null;
                }

                $rows[] = [$section];

                continue;
            }

            if ($pending === null) {
                $pending = $section;

                continue;
            }

            $rows[] = [$pending, $section];
            $pending = null;
        }

        if ($pending !== null) {
            $rows[] = [$pending];
        }

        return $rows;
    }
}
