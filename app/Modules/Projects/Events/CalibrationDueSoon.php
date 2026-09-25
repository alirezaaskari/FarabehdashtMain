<?php

declare(strict_types=1);

namespace App\Modules\Projects\Events;

use App\Contracts\UserNotifiableEvent;
use App\Support\JalaliDate;
use App\Support\Notifications\UserNotice;
use App\Support\PersianDigits;
use Carbon\CarbonInterface;

/**
 * اعتبار کالیبراسیون یک یا چند تجهیز کاربر به‌زودی تمام می‌شود.
 *
 * یک اعلان برای همه تجهیزات همان روز، نه یکی برای هرکدام: کارشناسی که ده
 * دستگاه دارد نباید ده پیامک بگیرد. نام و سریال تجهیز در اعلان نمی‌آید؛
 * دفترچه تجهیزات جزئیات را نشان می‌دهد.
 */
final readonly class CalibrationDueSoon implements UserNotifiableEvent
{
    public function __construct(
        public int $userId,
        public int $count,
        public CarbonInterface $earliest,
    ) {}

    public function userNotices(): array
    {
        return [new UserNotice(
            recipientId: $this->userId,
            kind: 'projects.calibration_due',
            title: $this->count === 1
                ? 'کالیبراسیون یک تجهیز به‌زودی تمام می‌شود'
                : sprintf('کالیبراسیون %s تجهیز به‌زودی تمام می‌شود', PersianDigits::from($this->count)),
            body: sprintf('نخستین پایان اعتبار: %s', JalaliDate::long($this->earliest)),
            routeName: 'projects.equipment.index',
        )];
    }
}
