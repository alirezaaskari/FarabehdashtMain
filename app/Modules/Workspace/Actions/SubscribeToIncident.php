<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Actions;

use App\Modules\Workspace\Domain\IncidentSubscription;
use App\Modules\Workspace\Domain\ServiceIncident;
use InvalidArgumentException;

/**
 * «خبرم کن وقتی رفع شد» — فقط برای کاربر واردشده و از راه اعلان درون‌سایتی.
 *
 * تکرار بی‌اثر است: دوبار زدن دکمه دو اعلان نمی‌سازد.
 */
final readonly class SubscribeToIncident
{
    /** @throws InvalidArgumentException اگر رویداد رفع شده باشد */
    public function handle(ServiceIncident $incident, int $userId): void
    {
        if ($incident->isResolved()) {
            throw new InvalidArgumentException('این رویداد رفع شده است.');
        }

        IncidentSubscription::query()->firstOrCreate([
            'incident_id' => $incident->getKey(),
            'user_id' => $userId,
        ]);
    }
}
