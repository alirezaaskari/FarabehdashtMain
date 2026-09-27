<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Actions;

use App\Modules\Jobs\Domain\Enums\EmploymentType;
use App\Modules\Jobs\Domain\JobAlert;
use Illuminate\Contracts\Config\Repository;
use RuntimeException;

/**
 * ساختن، عوض‌کردن پیامک و برداشتن هشدار شغل. هشدار بی هیچ پالایشی ساخته
 * نمی‌شود، چون هر آگهی تازه را می‌فرستاد.
 */
final readonly class ManageJobAlerts
{
    public const ABILITY = 'jobs.alerts';

    public function __construct(private Repository $config) {}

    public function create(int $userId, ?string $city, ?int $skillId, ?EmploymentType $type, bool $matchPassport, bool $sms): JobAlert
    {
        if ($city === null && $skillId === null && $type === null && ! $matchPassport) {
            throw new RuntimeException('دست‌کم یکی را انتخاب کنید: شهر، مهارت، نوع همکاری یا «مطابق گذرنامه من».');
        }

        $max = (int) $this->config->get('jobs.alerts.max', 5);

        if (JobAlert::query()->where('user_id', $userId)->count() >= $max) {
            throw new RuntimeException('بیش از '.$max.' هشدار نمی‌شود ساخت؛ یکی را بردارید.');
        }

        return JobAlert::query()->create([
            'user_id' => $userId,
            'city' => $city,
            'skill_id' => $skillId,
            'employment_type' => $type,
            'match_passport' => $matchPassport,
            'sms' => $sms,
        ]);
    }

    public function setSms(int $userId, int $alertId, bool $sms): void
    {
        $this->own($userId, $alertId)->forceFill(['sms' => $sms])->save();
    }

    public function delete(int $userId, int $alertId): void
    {
        $this->own($userId, $alertId)->delete();
    }

    private function own(int $userId, int $alertId): JobAlert
    {
        return JobAlert::query()->where('user_id', $userId)->whereKey($alertId)->first()
            ?? throw new RuntimeException('این هشدار پیدا نشد.');
    }
}
