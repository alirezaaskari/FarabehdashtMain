<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Admin;

use App\Contracts\ApprovalQueueSource;
use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Modules\Consulting\Domain\Enums\ProfileReviewStatus;
use App\Support\Admin\PendingItem;
use Illuminate\Support\Facades\Route;

/**
 * ویرایش‌های صفحه مشاور در انتظار مدیر، برای صف یکپارچه داشبورد پنل.
 */
final readonly class PendingConsultantProfiles implements ApprovalQueueSource
{
    public const ABILITY = 'admin.content.review';

    /** @return iterable<PendingItem> */
    public function pendingItems(): iterable
    {
        $url = Route::has('filament.fbh.pages.consultant-review') ? route('filament.fbh.pages.consultant-review') : url('/');

        foreach (ConsultantProfile::query()->where('status', ProfileReviewStatus::Pending)->oldest('submitted_at')->cursor() as $profile) {
            yield new PendingItem(
                ability: self::ABILITY,
                kind: 'consultant_profile',
                title: ($profile->published_at === null ? 'صفحه مشاور تازه — ' : 'ویرایش صفحه مشاور — ').($profile->pending['display_name'] ?? ''),
                url: $url,
                waitingSince: $profile->submitted_at ?? $profile->updated_at,
            );
        }
    }
}
