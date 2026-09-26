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

            if ($section instanceof HomeSection && ($section->items !== [] || $section->keepWhenEmpty)) {
                $sections[] = $section;
            }
        }

        usort($sections, static fn (HomeSection $a, HomeSection $b): int => $a->order <=> $b->order);

        return $sections;
    }

    /**
     * بخش‌ها در ردیف‌های صفحه: بخش‌های کم‌عرض هم‌خانواده پشت‌سرهم (دو نیمه‌عرض،
     * یا تا سه کاشی) یک ردیف‌اند و بقیه هرکدام ردیف خودشان. نیمه‌عرض تنها،
     * تمام‌عرض می‌شود تا نیمه صفحه خالی نماند.
     *
     * @return list<list<HomeSection>>
     */
    public function rows(): array
    {
        $rows = [];
        $pending = [];

        foreach ($this->sections() as $section) {
            $width = $section->layout->perRow();

            // perRow هم ظرفیت ردیف است و هم خانواده: نیمه‌عرض‌ها با هم، کاشی‌ها با هم.
            if ($pending !== [] && ($pending[0]->layout->perRow() !== $width || count($pending) === $width)) {
                $rows[] = $pending;
                $pending = [];
            }

            $pending[] = $section;
        }

        if ($pending !== []) {
            $rows[] = $pending;
        }

        return $rows;
    }
}
