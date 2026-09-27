<?php

declare(strict_types=1);

namespace App\Modules\Consulting\Filament\Pages;

use App\Modules\Consulting\Actions\ManageConsultingService;
use App\Modules\Consulting\Admin\PendingConsultingItems;
use App\Modules\Consulting\Domain\ConsultingService;
use App\Modules\Consulting\Domain\Enums\ServiceKind;
use App\Modules\Consulting\Domain\Enums\ServiceStatus;
use App\Support\Admin\NavigationGroup;
use App\Support\JalaliDate;
use App\Support\PersianNumber;
use App\Support\Regions\Regions;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use UnitEnum;

/**
 * صف تأیید خدمت‌های مشاوره. خدمت تا تأیید فروخته نمی‌شود.
 */
final class ConsultingServicesPage extends Page
{
    protected static ?string $slug = 'consulting-services';

    protected static ?int $navigationSort = 48;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Review;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected string $view = 'consulting::filament.pages.consulting-services';

    /** @var list<array{id: int, title: string, meta: string, description: string, url: string|null}> */
    public array $rows = [];

    /** @var array<string, string> */
    public array $notes = [];

    public static function getNavigationLabel(): string
    {
        return 'خدمت‌های مشاوره';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ConsultingService::query()->where('status', ServiceStatus::Pending)->count();

        return $count > 0 ? (string) $count : null;
    }

    public function getTitle(): string
    {
        return 'خدمت‌های مشاوره — صف تأیید';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(PendingConsultingItems::REVIEW_ABILITY) === true;
    }

    public function mount(): void
    {
        $this->load();
    }

    public function approve(int $id, ManageConsultingService $manage): void
    {
        $this->decide(fn () => $manage->approve($this->service($id), (int) Auth::id()), 'خدمت منتشر شد');
    }

    public function reject(int $id, ManageConsultingService $manage): void
    {
        $this->decide(fn () => $manage->reject($this->service($id), (int) Auth::id(), $this->notes['s'.$id] ?? ''), 'برای اصلاح برگشت');
    }

    private function decide(callable $action, string $done): void
    {
        try {
            $action();
        } catch (RuntimeException $exception) {
            Notification::make()->title('انجام نشد')->body($exception->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title($done)->success()->send();
        $this->load();
    }

    private function service(int $id): ConsultingService
    {
        return ConsultingService::query()->with('profile')->findOrFail($id);
    }

    private function load(): void
    {
        $regions = app(Regions::class);

        $this->notes = [];
        $this->rows = ConsultingService::query()
            ->where('status', ServiceStatus::Pending)
            ->with('profile')
            ->oldest('submitted_at')
            ->get()
            ->map(static fn (ConsultingService $service): array => [
                'id' => $service->id,
                'title' => $service->title,
                'meta' => implode(' · ', array_filter([
                    (string) $service->profile->display_name,
                    $service->kind->label(),
                    PersianNumber::format($service->duration_minutes).' دقیقه',
                    $service->price()->format(),
                    $service->kind === ServiceKind::Visit
                        ? 'شهرها: '.implode('، ', array_map(static fn (string $city): string => (string) $regions->cityName($city), $service->cities ?? []))
                        : null,
                    JalaliDate::long($service->submitted_at ?? $service->updated_at),
                ])),
                'description' => $service->description,
                'url' => $service->profile->isListed() ? route('consulting.show', $service->profile->slug) : null,
            ])
            ->values()
            ->all();
    }
}
