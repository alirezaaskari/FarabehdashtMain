<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Admin;

use App\Contracts\ApprovalQueueSource;
use App\Modules\Marketplace\Domain\Enums\MessageStatus;
use App\Modules\Marketplace\Domain\Enums\MilestoneStatus;
use App\Modules\Marketplace\Domain\Enums\ProjectStatus;
use App\Modules\Marketplace\Domain\MarketMessage;
use App\Modules\Marketplace\Domain\MarketMilestone;
use App\Modules\Marketplace\Domain\MarketProject;
use App\Support\Admin\PendingItem;
use Illuminate\Support\Facades\Route;

/**
 * پروژه‌ها و پیام‌های نگه‌داشته بازار در انتظار مدیر، برای صف یکپارچه داشبورد پنل.
 */
final readonly class PendingMarketItems implements ApprovalQueueSource
{
    public const ABILITY = 'admin.market.review';

    /** رأی اختلاف پول جابه‌جا می‌کند؛ همان توانایی رأی اعتراض خدمت مشاوره. */
    public const DISPUTE_ABILITY = 'admin.refund.issue';

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

        $messages = Route::has('filament.fbh.pages.market-messages') ? route('filament.fbh.pages.market-messages') : url('/');

        foreach (MarketMessage::query()->where('status', MessageStatus::Held)->oldest('id')->cursor() as $message) {
            yield new PendingItem(
                ability: self::ABILITY,
                kind: 'market_message',
                title: 'پیام نگه‌داشته بازار پروژه',
                url: $messages,
                waitingSince: $message->created_at,
            );
        }

        $disputes = Route::has('filament.fbh.pages.market-disputes') ? route('filament.fbh.pages.market-disputes') : url('/');

        foreach (MarketMilestone::query()->where('status', MilestoneStatus::Disputed)->with('contract.project')->oldest('updated_at')->cursor() as $milestone) {
            yield new PendingItem(
                ability: self::DISPUTE_ABILITY,
                kind: 'market_dispute',
                title: 'اختلاف بازار پروژه — '.$milestone->contract->project->title,
                url: $disputes,
                waitingSince: $milestone->updated_at,
            );
        }
    }
}
