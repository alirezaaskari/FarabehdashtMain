<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Actions;

use App\Modules\Workspace\Domain\ServiceIncident;
use App\Modules\Workspace\Events\IncidentResolved;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * پایان یک رویداد و خبر دادن به کسانی که منتظرش بودند.
 */
final readonly class ResolveIncident
{
    public function __construct(private Dispatcher $events) {}

    /** @throws InvalidArgumentException اگر رویداد از قبل رفع شده باشد */
    public function handle(ServiceIncident $incident, ?string $resolution, ?int $actorId): ServiceIncident
    {
        if ($incident->isResolved()) {
            throw new InvalidArgumentException('این رویداد پیش‌تر رفع شده است.');
        }

        $incident->forceFill([
            'resolved_at' => Carbon::now(),
            'resolution' => $resolution !== null && trim($resolution) !== '' ? trim($resolution) : null,
        ])->save();

        /** @var list<int> $subscribers */
        $subscribers = $incident->subscriptions()->pluck('user_id')->map(static fn (mixed $id): int => (int) $id)->values()->all();

        $this->events->dispatch(new IncidentResolved($incident, $subscribers, $actorId));

        return $incident;
    }
}
