<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Admin;

use App\Contracts\ApprovalQueueSource;
use App\Modules\Marketplace\Domain\Enums\ProjectStatus;
use App\Modules\Marketplace\Domain\MarketProject;
use App\Support\Admin\PendingItem;
use Illuminate\Support\Facades\Route;

/**
 * پروژه‌های بازار در انتظار مدیر، برای صف یکپارچه داشبورد پنل.
 */
final readonly class PendingMarketItems implements ApprovalQueueSource
{
    public const ABILITY = 'admin.market.review';

    /** @return iterable<PendingItem> */
    public function pendingItems(): iterable
    {
        $url = Route::has('filament.fbh.pages.market-review') ? route('filament.fbh.pages.market-review') : url('/');

        foreach (MarketProject::query()->where('status', ProjectStatus::Pending)->oldest('submitted_at')->cursor() as $project) {
            yield new PendingItem(
                ability: self::ABILITY,
                kind: 'market_project',
                title: 'پروژه بازار — '.$project->title,
                url: $url,
                waitingSince: $project->submitted_at ?? $project->updated_at,
            );
        }
    }
}
