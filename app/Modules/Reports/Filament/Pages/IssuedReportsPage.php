<?php

declare(strict_types=1);

namespace App\Modules\Reports\Filament\Pages;

use App\Modules\Reports\Actions\RevokeReport;
use App\Modules\Reports\Domain\Report;
use App\Modules\Reports\Domain\TrackingCode;
use App\Support\Admin\NavigationGroup;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use LogicException;
use UnitEnum;

/**
 * گزارش‌های صادرشده، برای رسیدگی به سوءاستفاده.
 *
 * مدیر فقط فراداده می‌بیند — شناسه، عنوان، وضعیت و تاریخ — نه محتوا و نه
 * فایل. گزارش داده کاری کارشناس و کارفرمای اوست؛ تنها کار مدیر این‌جا ابطال
 * سندی است که با آن سوءاستفاده شده.
 */
final class IssuedReportsPage extends Page
{
    public const ABILITY = 'admin.reports.manage';

    private const LIMIT = 50;

    protected static ?string $slug = 'issued-reports';

    protected static ?int $navigationSort = 75;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::System;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected string $view = 'reports::filament.pages.issued-reports';

    public string $search = '';

    /** @var array<int, string> */
    public array $reasons = [];

    public ?string $error = null;

    public static function getNavigationLabel(): string
    {
        return 'گزارش‌های صادرشده';
    }

    public function getTitle(): string
    {
        return 'گزارش‌های صادرشده';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    /** @return Collection<int, Report> */
    public function reports(): Collection
    {
        $query = Report::query()->issued()->latest('issued_at')->limit(self::LIMIT);

        if (trim($this->search) !== '') {
            $code = TrackingCode::parse($this->search);

            $code === null
                ? $query->where('title', 'like', '%'.trim($this->search).'%')
                : $query->where('tracking_code', $code->value);
        }

        return $query->get();
    }

    public function revoke(int $reportId, RevokeReport $revoke): void
    {
        $this->error = null;

        $report = Report::query()->issued()->find($reportId);

        if ($report === null) {
            $this->error = 'این گزارش پیدا نشد.';

            return;
        }

        try {
            $revoke->handle($report, $this->reasons[$reportId] ?? '', (int) Auth::id());
        } catch (InvalidArgumentException|LogicException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        unset($this->reasons[$reportId]);

        Notification::make()->title('گزارش '.$report->tracking_code.' باطل شد')->success()->send();
    }
}
