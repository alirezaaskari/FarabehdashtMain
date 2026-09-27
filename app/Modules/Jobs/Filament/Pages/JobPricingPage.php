<?php

declare(strict_types=1);

namespace App\Modules\Jobs\Filament\Pages;

use App\Modules\Jobs\Actions\UpdateJobPricing;
use App\Modules\Jobs\Domain\Enums\PaymentStatus;
use App\Modules\Jobs\Domain\PostingPayment;
use App\Modules\Jobs\Services\JobPricing;
use App\Support\Admin\NavigationGroup;
use App\Support\Money;
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
 * قیمت و مدت آگهی شغلی (DEC-63، DEC-66): هر عددی که کارفرما می‌پردازد یا
 * مدتی که آگهی می‌ماند، این‌جا به دست مدیر است.
 */
final class JobPricingPage extends Page
{
    public const ABILITY = 'admin.finance.reports';

    protected static ?string $slug = 'job-pricing';

    protected static ?int $navigationSort = 42;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Finance;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected string $view = 'jobs::filament.pages.job-pricing';

    public string $price = '';

    public string $days = '';

    public bool $firstFree = true;

    public string $goneDays = '';

    public ?string $error = null;

    public static function getNavigationLabel(): string
    {
        return 'قیمت آگهی شغلی';
    }

    public function getTitle(): string
    {
        return 'قیمت و مدت آگهی شغلی';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    public function mount(JobPricing $pricing): void
    {
        $this->price = (string) $pricing->price()->toman;
        $this->days = (string) $pricing->days();
        $this->firstFree = $pricing->firstFree();
        $this->goneDays = (string) $pricing->goneAfterDays();
    }

    public function save(UpdateJobPricing $update): void
    {
        $this->error = null;

        try {
            $update->handle(
                Money::fromInput($this->price),
                (int) Money::fromInput($this->days)->toman,
                $this->firstFree,
                (int) Money::fromInput($this->goneDays)->toman,
                (int) Auth::id(),
            );
        } catch (InvalidArgumentException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        Notification::make()->title('ذخیره شد')->body('از پرداخت بعدی اثر می‌کند.')->success()->send();
    }

    /** @return Collection<int, PostingPayment> */
    public function payments(): Collection
    {
        return PostingPayment::query()->paid()->with('posting.company')->latest('paid_at')->limit(20)->get();
    }

    /** @return array{count: int, free: int, revenue: Money} */
    public function lastThirtyDays(): array
    {
        $query = PostingPayment::query()->where('status', PaymentStatus::Paid)->where('paid_at', '>=', Carbon::now()->subDays(30));

        return [
            'count' => (clone $query)->count(),
            'free' => (clone $query)->where('is_free', true)->count(),
            'revenue' => Money::toman((int) $query->sum('price_toman')),
        ];
    }
}
