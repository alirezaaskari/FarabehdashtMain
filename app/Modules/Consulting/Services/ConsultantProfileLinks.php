<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Services;

use App\Contracts\ConsultantDirectory;
use App\Modules\Consulting\Domain\ConsultantProfile;
use App\Modules\Consulting\Domain\Enums\ProviderKind;
use Illuminate\Support\Facades\Route;

/**
 * نام و نشانی صفحه عمومی مشاوران، برای پیوند از پاسخ‌های «پرسش از متخصص».
 */
final readonly class ConsultantProfileLinks implements ConsultantDirectory
{
    public function profilesOf(array $userIds): array
    {
        if ($userIds === [] || ! Route::has('consulting.show')) {
            return [];
        }

        return ConsultantProfile::query()
            ->listed()
            ->where('kind', ProviderKind::Consultant)
            ->whereIn('user_id', $userIds)
            ->get(['user_id', 'slug', 'display_name'])
            ->mapWithKeys(static fn (ConsultantProfile $profile): array => [
                $profile->user_id => ['name' => (string) $profile->display_name, 'url' => route('consulting.show', $profile->slug)],
            ])
            ->all();
    }
}
