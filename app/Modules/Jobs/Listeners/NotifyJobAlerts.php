<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Listeners;

use App\Modules\Jobs\Domain\JobAlert;
use App\Modules\Jobs\Domain\JobAlertMatch;
use App\Modules\Jobs\Domain\PostingPayment;
use App\Modules\Jobs\Events\JobAlertMatched;
use App\Modules\Jobs\Events\PostingPublished;
use App\Modules\Jobs\Services\JobCatalog;
use App\Modules\Jobs\Services\SkillPassport;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;

/**
 * آگهی تازه را با هشدارهای ذخیره‌شده می‌سنجد (۲۰-۴). فقط اولین دوره انتشار
 * هشدار می‌دهد؛ تمدید آگهی تازه نیست. صاحب آگهی و کسی که پیش‌تر برای همین
 * آگهی هشدار گرفته دوباره خبر نمی‌گیرد.
 */
final readonly class NotifyJobAlerts
{
    public function __construct(
        private JobCatalog $catalog,
        private SkillPassport $passport,
        private Dispatcher $events,
    ) {}

    public function handle(PostingPublished $event): void
    {
        $posting = $event->posting;

        if (PostingPayment::query()->paid()->where('posting_id', $posting->id)->count() > 1 || ! $posting->isLive()) {
            return;
        }

        $postingSkills = $this->catalog->skillIdsOf($posting);
        $owner = $posting->company->user_id;
        $matched = [];

        $alerts = JobAlert::query()
            ->where('user_id', '!=', $owner)
            ->where(static fn ($query) => $query->whereNull('city')->orWhere('city', $posting->city))
            ->get()
            ->groupBy('user_id');

        foreach ($alerts as $userId => $userAlerts) {
            $passportSkills = $userAlerts->contains('match_passport', true) ? $this->passport->skillIds((int) $userId) : [];

            if ($userAlerts->contains(static fn (JobAlert $alert): bool => $alert->matches($posting, $postingSkills, $passportSkills))) {
                $matched[] = (int) $userId;
            }
        }

        $already = JobAlertMatch::query()->where('posting_id', $posting->id)->whereIn('user_id', $matched)->pluck('user_id')->all();
        $fresh = array_values(array_diff($matched, $already));

        if ($fresh === []) {
            return;
        }

        $now = Carbon::now();
        JobAlertMatch::query()->insert(array_map(static fn (int $userId): array => ['user_id' => $userId, 'posting_id' => $posting->id, 'created_at' => $now], $fresh));

        $this->events->dispatch(new JobAlertMatched($posting, $fresh));
    }
}
