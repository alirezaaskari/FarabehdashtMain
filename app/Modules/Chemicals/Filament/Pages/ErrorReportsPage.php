<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Filament\Pages;

use App\Modules\Chemicals\Actions\ResolveErrorReport;
use App\Modules\Chemicals\Domain\Enums\ErrorReportStatus;
use App\Modules\Chemicals\Domain\SubstanceErrorReport;
use App\Support\Admin\NavigationGroup;
use App\Support\JalaliDate;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use UnitEnum;

/**
 * گزارش‌های اشتباه کاربران درباره صفحه مواد.
 *
 * مدیر گزارش را با منبع اصلی مقایسه می‌کند، ماده را از ویرایشگر اصلاح می‌کند
 * و گزارش را می‌بندد. بستن گزارش خودش داده ماده را دست نمی‌زند.
 */
final class ErrorReportsPage extends Page
{
    private const CLOSED_SHOWN = 20;

    protected static ?string $slug = 'chemical-error-reports';

    protected static ?int $navigationSort = 47;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Review;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected string $view = 'chemicals::filament.pages.error-reports';

    /** @var array<int, string> یادداشت مدیر برای هر گزارش باز */
    public array $notes = [];

    /** @var list<array{id: int, substance: string, edit: string|null, public: string|null, topic: string, message: string, source: string|null, meta: string, status: string, note: string|null}> */
    public array $open = [];

    /** @var list<array{id: int, substance: string, edit: string|null, public: string|null, topic: string, message: string, source: string|null, meta: string, status: string, note: string|null}> */
    public array $closed = [];

    public static function getNavigationLabel(): string
    {
        return 'گزارش‌های اشتباه بانک مواد';
    }

    public function getTitle(): string
    {
        return 'گزارش‌های اشتباه بانک مواد';
    }

    public static function getNavigationBadge(): ?string
    {
        $open = SubstanceErrorReport::query()->where('status', ErrorReportStatus::Open->value)->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(ChemicalImportPage::ABILITY) === true;
    }

    public function mount(): void
    {
        $this->load();
    }

    public function markFixed(int $id, ResolveErrorReport $resolve): void
    {
        $this->close($id, ErrorReportStatus::Fixed, $resolve);
    }

    public function dismiss(int $id, ResolveErrorReport $resolve): void
    {
        $this->close($id, ErrorReportStatus::Dismissed, $resolve);
    }

    private function close(int $id, ErrorReportStatus $outcome, ResolveErrorReport $resolve): void
    {
        abort_unless(self::canAccess(), 403);

        $report = SubstanceErrorReport::query()->with('substance')->findOrFail($id);
        $resolve->handle($report, $outcome, (int) Auth::id(), $this->notes[$id] ?? null);
        unset($this->notes[$id]);

        Notification::make()->title('گزارش بسته شد: '.$outcome->label())->success()->send();
        $this->load();
    }

    private function load(): void
    {
        $this->open = $this->rows(
            SubstanceErrorReport::query()->with('substance')->where('status', ErrorReportStatus::Open->value)->oldest('id')->get()->all(),
        );
        $this->closed = $this->rows(
            SubstanceErrorReport::query()->with('substance')->where('status', '!=', ErrorReportStatus::Open->value)
                ->latest('resolved_at')->limit(self::CLOSED_SHOWN)->get()->all(),
        );
    }

    /**
     * @param  list<SubstanceErrorReport>  $reports
     * @return list<array{id: int, substance: string, edit: string|null, public: string|null, topic: string, message: string, source: string|null, meta: string, status: string, note: string|null}>
     */
    private function rows(array $reports): array
    {
        $editable = Route::has('filament.fbh.resources.substances.edit');
        $public = Route::has('chemicals.show');

        return array_map(static fn (SubstanceErrorReport $report): array => [
            'id' => $report->id,
            'substance' => $report->substance->name_fa.' ('.$report->substance->cas_number.')',
            'edit' => $editable ? route('filament.fbh.resources.substances.edit', ['record' => $report->substance]) : null,
            'public' => $public ? route('chemicals.show', $report->substance->slug) : null,
            'topic' => $report->topic->label(),
            'message' => $report->message,
            'source' => $report->source_url,
            'meta' => implode(' · ', array_filter([
                $report->user_id !== null ? 'کاربر #'.$report->user_id : null,
                JalaliDate::long($report->created_at),
            ])),
            'status' => $report->status->label(),
            'note' => $report->admin_note,
        ], $reports);
    }
}
