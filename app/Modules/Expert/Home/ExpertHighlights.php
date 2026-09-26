<?php

declare(strict_types=1);

namespace App\Modules\Expert\Home;

use App\Contracts\HomepageSource;
use App\Modules\Expert\Domain\ExpertQuestion;
use App\Support\Home\HomeItem;
use App\Support\Home\HomeLayout;
use App\Support\Home\HomeSection;
use Illuminate\Support\Facades\Route;

/**
 * کاشی «پرسش از متخصص» صفحه اصلی، با تازه‌ترین پرسش منتشرشده.
 *
 * خود کاشی دعوت به پرسیدن است؛ پس حتی وقتی هنوز پرسشی منتشر نشده، روی صفحه
 * می‌ماند (`keepWhenEmpty`).
 */
final readonly class ExpertHighlights implements HomepageSource
{
    public function homeSection(): ?HomeSection
    {
        if (! Route::has('expert.index') || ! Route::has('expert.show')) {
            return null;
        }

        $latest = ExpertQuestion::query()->listed()->latest('published_at')->first();

        return new HomeSection(
            key: 'expert',
            title: 'پرسش از متخصص',
            lede: 'پرسشت را بی‌نام بپرس؛ مشاوران تأییدشده در فرابهداشت پاسخ می‌دهند و هر پاسخ پیش از انتشار بازبینی می‌شود.',
            items: $latest === null ? [] : [new HomeItem(
                title: $latest->title,
                url: route('expert.show', $latest->uuid),
                kicker: 'پرسش',
            )],
            order: 40,
            art: 'home-section-expert',
            moreUrl: route('expert.index'),
            moreLabel: 'بپرس',
            layout: HomeLayout::Tile,
            keepWhenEmpty: true,
            icon: 'info',
        );
    }
}
