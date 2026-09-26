<?php

declare(strict_types=1);

namespace App\Modules\Webinars\Services;

use App\Modules\Webinars\Domain\Enums\RegistrationStatus;
use App\Modules\Webinars\Domain\Webinar;
use Illuminate\Database\Eloquent\Builder;

/**
 * صندلی‌های گرفته‌شده: ثبت‌نام قطعی، به‌اضافه ثبت‌نام در انتظار پرداختی که
 * کمتر از `hold_minutes` از آن گذشته. این‌طور دو نفر هم‌زمان آخرین صندلی را
 * به درگاه نمی‌برند و پرداخت رهاشده صندلی را برای همیشه قفل نمی‌کند.
 */
final readonly class Seats
{
    public function __construct(private int $holdMinutes) {}

    public function taken(Webinar $webinar, ?int $exceptUserId = null): int
    {
        return $webinar->registrations()
            ->when($exceptUserId !== null, static fn (Builder $q) => $q->where('user_id', '!=', $exceptUserId))
            ->where(fn (Builder $q) => $q
                ->where('status', RegistrationStatus::Confirmed)
                ->orWhere(fn (Builder $pending) => $pending
                    ->where('status', RegistrationStatus::Pending)
                    ->where('updated_at', '>=', now()->subMinutes($this->holdMinutes))))
            ->count();
    }

    public function remaining(Webinar $webinar, ?int $exceptUserId = null): int
    {
        return max(0, $webinar->capacity - $this->taken($webinar, $exceptUserId));
    }

    public function isRegistered(Webinar $webinar, int $userId): bool
    {
        return $webinar->registrations()
            ->where('user_id', $userId)
            ->where('status', RegistrationStatus::Confirmed)
            ->exists();
    }
}
