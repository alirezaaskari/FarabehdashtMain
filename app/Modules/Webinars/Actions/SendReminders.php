<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Actions;

use App\Modules\Webinars\Domain\Enums\RegistrationStatus;
use App\Modules\Webinars\Domain\Enums\WebinarStatus;
use App\Modules\Webinars\Domain\WebinarRegistration;
use App\Modules\Webinars\Events\WebinarStartingSoon;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;

/**
 * یک یادآور برای هر ثبت‌نام قطعی رویدادی که تا `remind_before_minutes`
 * دیگر شروع می‌شود. `reminded_at` تکرار را می‌بندد.
 */
final readonly class SendReminders
{
    public function __construct(
        private Dispatcher $events,
        private int $beforeMinutes,
    ) {}

    public function handle(): int
    {
        $due = WebinarRegistration::query()
            ->where('status', RegistrationStatus::Confirmed)
            ->whereNull('reminded_at')
            ->whereHas('webinar', fn (Builder $q) => $q
                ->where('status', WebinarStatus::Published)
                ->where('starts_at', '>', now())
                ->where('starts_at', '<=', now()->addMinutes($this->beforeMinutes)))
            ->with('webinar')
            ->get();

        foreach ($due as $registration) {
            $registration->forceFill(['reminded_at' => now()])->save();
            $this->events->dispatch(new WebinarStartingSoon($registration));
        }

        return $due->count();
    }
}
