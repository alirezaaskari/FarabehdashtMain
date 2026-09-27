<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Filament\Pages;

use App\Contracts\RefundablePurchases;
use App\Models\User;
use App\Modules\Ledger\Events\PurchaseRefunded;
use App\Support\Admin\NavigationGroup;
use App\Support\Mobile;
use App\Support\Payments\RefundablePurchase;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use UnitEnum;

/**
 * بازگشت وجه خریدهایی که صفحه جدا ندارند: بسته راه‌حل، اشتراک حرفه‌ای، تیم،
 * بسته آزمون و صدور گزارش. هر ماژول با `RefundablePurchases` خریدهایش را
 * می‌دهد؛ این صفحه فقط پیدا می‌کند، اثر را نشان می‌دهد و پس از تأیید اجرا می‌کند.
 */
final class PurchaseRefundPage extends Page
{
    public const ABILITY = 'admin.refund.issue';

    protected static ?string $slug = 'purchase-refunds';

    protected static ?int $navigationSort = 50;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Finance;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnLeft;

    protected string $view = 'ledger::filament.pages.purchase-refund';

    public string $mobile = '';

    public ?int $buyerId = null;

    public ?string $error = null;

    /** @var array<string, string> دلیل هر خرید، با کلید uuid */
    public array $reasons = [];

    public ?string $confirming = null;

    public static function getNavigationLabel(): string
    {
        return 'بازگشت وجه خریدهای دیگر';
    }

    public function getTitle(): string
    {
        return 'بازگشت وجه خریدهای دیگر';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    public function find(): void
    {
        $this->error = null;
        $this->buyerId = null;
        $this->confirming = null;

        try {
            $mobile = Mobile::fromInput($this->mobile);
        } catch (InvalidArgumentException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $user = User::query()->where('mobile', $mobile->value)->first();

        if ($user === null) {
            $this->error = 'کاربری با این شماره موبایل پیدا نشد.';

            return;
        }

        $this->buyerId = (int) $user->getKey();
    }

    /** @return list<array{label: string, kind: string, purchases: list<RefundablePurchase>}> */
    public function groups(): array
    {
        if ($this->buyerId === null) {
            return [];
        }

        $groups = [];

        foreach ($this->sources() as $kind => $source) {
            $purchases = $source->paidBy($this->buyerId);

            if ($purchases !== []) {
                $groups[] = ['label' => $source->label(), 'kind' => $kind, 'purchases' => $purchases];
            }
        }

        return $groups;
    }

    public function confirm(string $uuid): void
    {
        $this->error = null;

        if (mb_strlen(trim($this->reasons[$uuid] ?? '')) < 10) {
            $this->error = 'دلیل بازگشت را دست‌کم در ده حرف بنویسید؛ در دفتر رویداد و شرح تراکنش می‌ماند.';

            return;
        }

        $this->confirming = $uuid;
    }

    public function refund(string $kind, string $uuid): void
    {
        $this->error = null;
        $source = $this->sources()[$kind] ?? null;
        $reason = trim($this->reasons[$uuid] ?? '');

        if ($source === null || $this->buyerId === null || $this->confirming !== $uuid) {
            return;
        }

        try {
            $amount = $source->refund($uuid, (int) Auth::id(), $reason);
        } catch (InvalidArgumentException $exception) {
            $this->error = $exception->getMessage();
            $this->confirming = null;

            return;
        }

        event(new PurchaseRefunded($kind, $uuid, $this->buyerId, $amount->toman, $reason, (int) Auth::id()));

        $this->confirming = null;

        Notification::make()->title($amount->format().' به کیف پول خریدار برگشت')->success()->send();
    }

    /** @return array<string, RefundablePurchases> با کلید نام کوتاه کلاس، که در فرم پایدار است */
    private function sources(): array
    {
        $sources = [];

        foreach (app()->tagged(RefundablePurchases::TAG) as $source) {
            $sources[class_basename($source)] = $source;
        }

        return $sources;
    }
}
