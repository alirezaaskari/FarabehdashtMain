<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Actions;

use App\Modules\Workspace\Domain\LegalAcceptance;
use App\Modules\Workspace\Events\LegalVersionsAccepted;
use App\Modules\Workspace\Services\LegalLibrary;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;

/**
 * پذیرش همه نسخه‌هایی که کاربر پیش از ادامه باید بپذیرد.
 *
 * فهرست نسخه‌ها از سرور می‌آید، نه از فرم: کاربر همان چیزی را می‌پذیرد که
 * همین لحظه جاری است، حتی اگر صفحه پذیرش را پیش از انتشار نسخه تازه‌تری باز
 * کرده باشد.
 */
final readonly class AcceptLegalVersions
{
    public function __construct(
        private LegalLibrary $library,
        private Dispatcher $events,
    ) {}

    /** @return int شمار نسخه‌های پذیرفته‌شده */
    public function handle(int $userId): int
    {
        $pending = $this->library->pendingFor($userId);

        if ($pending === []) {
            return 0;
        }

        foreach ($pending as $version) {
            LegalAcceptance::query()->firstOrCreate(
                ['user_id' => $userId, 'legal_version_id' => $version->getKey()],
                ['accepted_at' => Carbon::now()],
            );
        }

        $this->events->dispatch(new LegalVersionsAccepted($userId, $pending));

        return count($pending);
    }
}
