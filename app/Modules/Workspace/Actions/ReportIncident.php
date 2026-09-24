<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Actions;

use App\Modules\Workspace\Domain\Enums\Service;
use App\Modules\Workspace\Domain\Enums\ServiceState;
use App\Modules\Workspace\Domain\ServiceIncident;
use App\Modules\Workspace\Events\IncidentReported;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * ثبت اختلال یا نگهداری روی صفحه وضعیت.
 *
 * زمان شروع آینده یعنی نگهداری برنامه‌ریزی‌شده: تا آن لحظه در بخش «پیش‌رو»
 * دیده می‌شود و وضعیت سرویس را تغییر نمی‌دهد.
 */
final readonly class ReportIncident
{
    public function __construct(private Dispatcher $events) {}

    /** @throws InvalidArgumentException */
    public function handle(
        Service $service,
        ServiceState $state,
        string $title,
        ?string $body,
        ?Carbon $startedAt,
        ?int $actorId,
    ): ServiceIncident {
        if ($state === ServiceState::Operational) {
            throw new InvalidArgumentException('رویداد با وضعیت «برقرار» معنا ندارد؛ برای پایان اختلال، آن را رفع‌شده علامت بزنید.');
        }

        if (trim($title) === '') {
            throw new InvalidArgumentException('عنوان رویداد اجباری است.');
        }

        $incident = ServiceIncident::query()->create([
            'uuid' => (string) Str::uuid7(),
            'service' => $service,
            'state' => $state,
            'title' => trim($title),
            'body' => $body !== null && trim($body) !== '' ? trim($body) : null,
            'started_at' => $startedAt ?? Carbon::now(),
            'reported_by' => $actorId,
        ]);

        $this->events->dispatch(new IncidentReported($incident, $actorId));

        return $incident;
    }
}
