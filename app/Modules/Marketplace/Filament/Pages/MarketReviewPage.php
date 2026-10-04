<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Filament\Pages;

use App\Modules\Marketplace\Actions\ReviewProject;
use App\Modules\Marketplace\Admin\PendingMarketItems;
use App\Modules\Marketplace\Domain\Enums\ProjectStatus;
use App\Modules\Marketplace\Domain\MarketProject;
use App\Modules\Marketplace\Domain\ProjectFile;
use App\Modules\Marketplace\Services\MarketCatalog;
use App\Support\Admin\NavigationGroup;
use App\Support\JalaliDate;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use UnitEnum;

/**
 * صف تأیید بازار پروژه (DEC-76): هر پروژه و هر اصلاح آن پیش از انتشار.
 *
 * دکمه‌ها همان Action را صدا می‌زنند تا رویداد، اعلان و دفتر رویداد دور زده نشود.
 */
final class MarketReviewPage extends Page
{
    protected static ?string $slug = 'market-review';

    protected static ?int $navigationSort = 49;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Review;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected string $view = 'marketplace::filament.pages.market-review';

    /** @var list<array{id: int, title: string, meta: string, fields: list<array{label: string, value: string}>, files: list<array{name: string, url: string}>}> */
    public array $rows = [];

    /** @var array<int, string> */
    public array $notes = [];

    public static function getNavigationLabel(): string
    {
        return 'پروژه‌های بازار';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = MarketProject::query()->where('status', ProjectStatus::Pending)->count();

        return $count > 0 ? (string) $count : null;
    }

    public function getTitle(): string
    {
        return 'بازار پروژه — صف تأیید';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(PendingMarketItems::ABILITY) === true;
    }

    public function mount(): void
    {
        $this->load();
    }

    public function approve(int $id, ReviewProject $review): void
    {
        $this->decide(fn () => $review->approve(MarketProject::query()->findOrFail($id), (int) Auth::id()), 'پروژه منتشر شد');
    }

    public function reject(int $id, ReviewProject $review): void
    {
        $this->decide(fn () => $review->reject(MarketProject::query()->findOrFail($id), (int) Auth::id(), $this->notes[$id] ?? ''), 'برای اصلاح برگشت');
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

    private function load(): void
    {
        $catalog = app(MarketCatalog::class);

        $this->notes = [];
        $this->rows = MarketProject::query()
            ->where('status', ProjectStatus::Pending)
            ->with('files')
            ->oldest('submitted_at')
            ->get()
            ->map(static fn (MarketProject $project): array => [
                'id' => $project->id,
                'title' => $project->title,
                'meta' => implode(' · ', array_filter([
                    $project->reviewed_at === null ? 'پروژه تازه' : 'اصلاح پس از برگشت',
                    'کاربر #'.$project->client_user_id,
                    $project->is_private ? 'خصوصی' : null,
                    JalaliDate::long($project->submitted_at ?? $project->updated_at),
                ])),
                'fields' => [
                    ['label' => 'نوع کار', 'value' => (string) $catalog->serviceName($project->service)],
                    ['label' => 'محل', 'value' => $catalog->place($project)],
                    ['label' => 'بودجه', 'value' => $catalog->budgetLabel($project)],
                    ['label' => 'مهلت دلخواه', 'value' => $project->wanted_by === null ? '—' : JalaliDate::long($project->wanted_by)],
                    ['label' => 'نمایش کارفرما', 'value' => $catalog->clientLabel($project)],
                    ['label' => 'شرح کار', 'value' => $project->description],
                ],
                'files' => $project->files->map(static fn (ProjectFile $file): array => [
                    'name' => $file->original_name,
                    'url' => route('market.files.download', $file->uuid),
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }
}
