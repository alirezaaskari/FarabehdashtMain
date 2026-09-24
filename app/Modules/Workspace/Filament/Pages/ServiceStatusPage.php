<?php

declare(strict_types=1);

namespace App\Modules\Workspace\Filament\Pages;

use App\Modules\Workspace\Actions\ReportIncident;
use App\Modules\Workspace\Actions\ResolveIncident;
use App\Modules\Workspace\Domain\Enums\Service;
use App\Modules\Workspace\Domain\Enums\ServiceState;
use App\Modules\Workspace\Domain\ServiceIncident;
use App\Support\Admin\NavigationGroup;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use UnitEnum;

/**
 * ثبت و رفع اختلال روی صفحه عمومی وضعیت (DEC-23).
 *
 * زمان شروع خالی یعنی «همین حالا»؛ زمان آینده یعنی نگهداری برنامه‌ریزی‌شده.
 */
final class ServiceStatusPage extends Page
{
    public const ABILITY = 'admin.status.manage';

    protected static ?string $slug = 'service-status';

    protected static ?int $navigationSort = 70;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::System;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected string $view = 'workspace::filament.pages.service-status';

    public string $service = 'site';

    public string $state = 'degraded';

    public string $headline = '';

    public string $body = '';

    public string $startsAt = '';

    /** @var array<int, string> */
    public array $resolutions = [];

    public ?string $error = null;

    public static function getNavigationLabel(): string
    {
        return 'وضعیت سرویس';
    }

    public function getTitle(): string
    {
        return 'وضعیت سرویس';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    /** @return list<array{value: string, label: string}> */
    public function serviceOptions(): array
    {
        return array_map(static fn (Service $s): array => ['value' => $s->value, 'label' => $s->label()], Service::cases());
    }

    /** @return list<array{value: string, label: string}> */
    public function stateOptions(): array
    {
        return array_map(static fn (ServiceState $s): array => ['value' => $s->value, 'label' => $s->label()], ServiceState::reportable());
    }

    /** @return Collection<int, ServiceIncident> */
    public function unresolved(): Collection
    {
        return ServiceIncident::query()->whereNull('resolved_at')->orderBy('started_at')->get();
    }

    public function report(ReportIncident $report): void
    {
        $this->error = null;

        $service = Service::tryFrom($this->service);
        $state = ServiceState::tryFrom($this->state);

        if ($service === null || $state === null) {
            $this->error = 'سرویس یا وضعیت نامعتبر است.';

            return;
        }

        try {
            $report->handle(
                $service,
                $state,
                $this->headline,
                $this->body,
                $this->startsAt !== '' ? Carbon::parse($this->startsAt) : null,
                $this->actorId(),
            );
        } catch (InvalidArgumentException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->reset(['headline', 'body', 'startsAt']);

        Notification::make()->title('رویداد روی صفحه وضعیت ثبت شد')->success()->send();
    }

    public function resolve(int $incidentId, ResolveIncident $resolve): void
    {
        $this->error = null;

        $incident = ServiceIncident::query()->find($incidentId);

        if ($incident === null) {
            return;
        }

        try {
            $resolve->handle($incident, $this->resolutions[$incidentId] ?? null, $this->actorId());
        } catch (InvalidArgumentException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        unset($this->resolutions[$incidentId]);

        Notification::make()->title('رویداد رفع‌شده علامت خورد')->success()->send();
    }

    private function actorId(): ?int
    {
        $id = Auth::id();

        return is_int($id) ? $id : null;
    }
}
