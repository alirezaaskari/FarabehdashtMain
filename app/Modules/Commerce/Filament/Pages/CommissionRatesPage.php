<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Filament\Pages;

use App\Modules\Commerce\Actions\SetCommissionRate;
use App\Modules\Commerce\Domain\CommissionRate;
use App\Modules\Commerce\Services\CommissionService;
use App\Support\Admin\NavigationGroup;
use BackedEnum;
use Carbon\Exceptions\InvalidFormatException;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use UnitEnum;

/**
 * نرخ کمیسیون هر جریان فروش (DEC-52): نرخ امروز، ثبت نرخ تازه با تاریخ اثر
 * و سابقه. نرخ ثبت‌شده ویرایش و حذف نمی‌شود.
 */
final class CommissionRatesPage extends Page
{
    public const ABILITY = 'admin.monetization.manage';

    /** نام فارسی جریان‌ها؛ کلیدها همان کلیدهای `commerce.commission.default_rate_bp`. */
    public const FLOWS = [
        'shop' => 'فروشگاه فایل',
        'course' => 'دوره',
        'consulting' => 'خدمت مشاوره',
        'report_review' => 'بررسی گزارش توسط متخصص',
        'project_market' => 'بازار پروژه (از سهم مجری)',
    ];

    protected static ?string $slug = 'commission-rates';

    protected static ?int $navigationSort = 60;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Finance;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected string $view = 'commerce::filament.pages.commission-rates';

    /** @var array<string, string> درصد پیشنهادی هر جریان */
    public array $percents = [];

    /** @var array<string, string> تاریخ اثر به میلادی، YYYY-MM-DD */
    public array $dates = [];

    public ?string $error = null;

    public static function getNavigationLabel(): string
    {
        return 'نرخ کمیسیون';
    }

    public function getTitle(): string
    {
        return 'نرخ کمیسیون';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    public function mount(CommissionService $rates): void
    {
        foreach (array_keys(self::FLOWS) as $flow) {
            $this->percents[$flow] = (string) ($rates->currentRateBp($flow) / 100);
            $this->dates[$flow] = Carbon::today()->toDateString();
        }
    }

    public function save(string $flow, SetCommissionRate $set): void
    {
        $this->error = null;
        $percent = trim($this->percents[$flow] ?? '');

        if (! is_numeric($percent)) {
            $this->error = 'درصد را به عدد بنویسید؛ مثلاً 15 یا 12.5.';

            return;
        }

        try {
            $from = Carbon::createFromFormat('!Y-m-d', trim($this->dates[$flow] ?? ''));
        } catch (InvalidFormatException) {
            $from = null;
        }

        if (! $from instanceof Carbon) {
            $this->error = 'تاریخ اثر را به شکل 2026-10-01 بنویسید.';

            return;
        }

        try {
            $set->handle($flow, (int) round((float) $percent * 100), $from, (int) Auth::id());
        } catch (InvalidArgumentException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        Notification::make()->title('نرخ تازه ثبت شد')->success()->send();
    }

    /** @return array<string, int> نرخ امروز هر جریان به Basis Point */
    public function current(): array
    {
        $rates = app(CommissionService::class);
        $current = [];

        foreach (array_keys(self::FLOWS) as $flow) {
            $current[$flow] = $rates->currentRateBp($flow);
        }

        return $current;
    }

    /** @return Collection<int, CommissionRate> */
    public function history(): Collection
    {
        return CommissionRate::query()->latest('id')->limit(30)->get();
    }
}
