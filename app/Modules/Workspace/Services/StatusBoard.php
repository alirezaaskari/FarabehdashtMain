<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Services;

use App\Modules\Workspace\Domain\Enums\Service;
use App\Modules\Workspace\Domain\Enums\ServiceState;
use App\Modules\Workspace\Domain\ServiceIncident;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * صفحه وضعیت: وضعیت فعلی شش سرویس، نوار روزانه و سابقه رویدادها.
 *
 * همه‌چیز از جدول رویدادها ساخته می‌شود (DEC-23). وضعیت یک سرویس بدترین
 * وضعیت میان رویدادهای باز آن است؛ رنگ هر روز نوار، بدترین وضعیت رویدادهایی
 * که آن روز را لمس کرده‌اند.
 */
final readonly class StatusBoard
{
    public function __construct(private int $days) {}

    public function days(): int
    {
        return $this->days;
    }

    /** @return array<string, ServiceState> کلید: مقدار سرویس */
    public function current(?Carbon $at = null): array
    {
        $states = [];

        foreach (Service::cases() as $service) {
            $states[$service->value] = ServiceState::Operational;
        }

        foreach (ServiceIncident::query()->open($at)->get() as $incident) {
            $key = $incident->service->value;
            $states[$key] = $states[$key]->worse($incident->state);
        }

        return $states;
    }

    public function overall(?Carbon $at = null): ServiceState
    {
        return array_reduce(
            $this->current($at),
            static fn (ServiceState $carry, ServiceState $state): ServiceState => $carry->worse($state),
            ServiceState::Operational,
        );
    }

    /**
     * نوار روزانه هر سرویس، قدیمی‌ترین روز اول.
     *
     * @return array<string, list<array{date: Carbon, state: ServiceState}>>
     */
    public function history(?Carbon $at = null): array
    {
        $today = ($at ?? Carbon::now())->copy()->startOfDay();
        $from = $today->copy()->subDays($this->days - 1);

        $incidents = ServiceIncident::query()
            ->overlapping($from, $today->copy()->endOfDay())
            ->get();

        $bars = [];

        foreach (Service::cases() as $service) {
            $mine = $incidents->filter(fn (ServiceIncident $incident): bool => $incident->service === $service);
            $bars[$service->value] = [];

            for ($day = $from->copy(); $day->lte($today); $day->addDay()) {
                $bars[$service->value][] = ['date' => $day->copy(), 'state' => $this->worstOn($mine, $day, $at)];
            }
        }

        return $bars;
    }

    /** @return Collection<int, ServiceIncident> */
    public function upcoming(?Carbon $at = null): Collection
    {
        return ServiceIncident::query()->upcoming($at)->orderBy('started_at')->get();
    }

    /** @return Collection<int, ServiceIncident> */
    public function open(?Carbon $at = null): Collection
    {
        return ServiceIncident::query()->open($at)->latest('started_at')->get();
    }

    /**
     * رویدادهای رفع‌شده در بازه نوار، تازه‌ترین اول.
     *
     * @return Collection<int, ServiceIncident>
     */
    public function resolved(?Carbon $at = null): Collection
    {
        $from = ($at ?? Carbon::now())->copy()->startOfDay()->subDays($this->days - 1);

        return ServiceIncident::query()
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', $from)
            ->latest('started_at')
            ->get();
    }

    /** @param  Collection<int, ServiceIncident>  $incidents */
    private function worstOn(Collection $incidents, Carbon $day, ?Carbon $at): ServiceState
    {
        $start = $day->copy()->startOfDay();
        $end = $day->copy()->endOfDay();
        $now = $at ?? Carbon::now();

        $state = ServiceState::Operational;

        foreach ($incidents as $incident) {
            $until = $incident->resolved_at ?? $now;

            if ($incident->started_at->lte($end) && $until->gte($start) && $incident->started_at->lte($now)) {
                $state = $state->worse($incident->state);
            }
        }

        return $state;
    }
}
