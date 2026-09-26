<?php

declare(strict_types=1);

namespace App\Modules\Reports\Filament\Pages;

use App\Modules\Reports\Actions\UpdateReportPrice;
use App\Modules\Reports\Domain\ReportPurchase;
use App\Modules\Reports\Services\ReportSale;
use App\Support\Admin\NavigationGroup;
use App\Support\Money;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use UnitEnum;

/**
 * خرید تکی صدور گزارش (بخش ۱۸-۵): قیمت و فروش‌های اخیر، برای مدیر مالی.
 *
 * روشن و خاموش کردن فروش این‌جا نیست؛ کلید «تک‌فروشی گزارش» در صفحه
 * «کلیدهای درآمدزایی» است تا همه جریان‌ها یک‌جا دیده شوند.
 */
final class ReportSalesPage extends Page
{
    public const ABILITY = 'admin.finance.reports';

    private const LIMIT = 30;

    protected static ?string $slug = 'report-sales';

    protected static ?int $navigationSort = 40;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Finance;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    protected string $view = 'reports::filament.pages.report-sales';

    public string $price = '';

    public ?string $error = null;

    public static function getNavigationLabel(): string
    {
        return 'فروش تکی گزارش';
    }

    public function getTitle(): string
    {
        return 'فروش تکی گزارش';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    public function mount(ReportSale $sale): void
    {
        $this->price = (string) $sale->price()->toman;
    }

    public function savePrice(UpdateReportPrice $update): void
    {
        $this->error = null;

        try {
            $update->handle(Money::fromInput($this->price), (int) Auth::id());
        } catch (InvalidArgumentException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        Notification::make()->title('قیمت ذخیره شد')->success()->send();
    }

    /** @return Collection<int, ReportPurchase> */
    public function purchases(): Collection
    {
        return ReportPurchase::query()
            ->paid()
            ->with('buyer:id,name')
            ->latest('paid_at')
            ->limit(self::LIMIT)
            ->get();
    }

    /** @return array{count: int, revenue: Money} */
    public function lastThirtyDays(): array
    {
        $query = ReportPurchase::query()->paid()->where('paid_at', '>=', now()->subDays(30));

        return [
            'count' => (clone $query)->count(),
            'revenue' => Money::toman((int) $query->sum('price_toman')),
        ];
    }
}
