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
}
