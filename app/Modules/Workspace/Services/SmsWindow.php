<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Services;

use Illuminate\Support\Carbon;

/**
 * قاعده‌های زمانی پیامک اعلان (DEC-39): ساعت سکوت و سقف روزانه.
 *
 * ساعت‌ها به وقت برنامه (`app.timezone`، تهران) خوانده می‌شوند؛ «امروز» در
 * سقف روزانه هم همان روز تهران است، نه روز UTC.
 */
final readonly class SmsWindow
{
    public function __construct(
        public int $dailyLimit,
        private int $quietFrom,
        private int $quietUntil,
        private int $staleAfterHours,
    ) {}

    public function isQuiet(Carbon $at): bool
    {
        $hour = $at->hour;

        return $this->quietFrom > $this->quietUntil
            ? $hour >= $this->quietFrom || $hour < $this->quietUntil
            : $hour >= $this->quietFrom && $hour < $this->quietUntil;
    }

    /**
     * نخستین لحظه‌ای که پیامک ساخته‌شده در `$at` مجاز است برود.
     */
    public function nextSendableAt(Carbon $at): Carbon
    {
        if (! $this->isQuiet($at)) {
            return $at->copy();
        }

        $opening = $at->copy()->setTime($this->quietUntil, 0);

        return $opening->isAfter($at) ? $opening : $opening->addDay();
    }

    /**
     * پیامکی که از موعدش خیلی گذشته (cron مدتی خوابیده) دیگر خبر نیست؛
     * فرستادنش فقط سیلی از پیامک کهنه می‌سازد.
     */
    public function isStale(Carbon $dueAt, Carbon $at): bool
    {
        return $dueAt->copy()->addHours($this->staleAfterHours)->isBefore($at);
    }
}
