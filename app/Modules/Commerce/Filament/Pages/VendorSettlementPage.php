<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Filament\Pages;

use App\Contracts\LedgerBalanceReader;
use App\Models\User;
use App\Modules\Commerce\Actions\SettleVendor;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Mobile;
use App\Support\Money;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * تسویه دستی فروشنده — بدون آستانه یا بازه خودکار (DEC-17 هنوز باز است).
 */
final class VendorSettlementPage extends Page
{
    public const ABILITY = 'admin.settlement.approve';

    protected static ?string $slug = 'commerce-settlement';

    protected static ?int $navigationSort = 49;

    protected string $view = 'commerce::filament.pages.settlement';

    public string $mobile = '';

    public string $amount = '';

    public string $memo = '';

    public ?string $error = null;

    /** @var array{mobile: string, owed: string}|null */
    public ?array $vendor = null;

    public ?int $vendorUserId = null;

    public static function getNavigationLabel(): string
    {
        return 'تسویه فروشنده';
    }

    public function getTitle(): string
    {
        return 'تسویه دستی فروشنده';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    public function find(LedgerBalanceReader $balances): void
    {
        $this->error = null;
        $this->vendor = null;

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

        $this->vendorUserId = $user->id;
        $owed = $balances->balanceOf(LedgerAccountRef::vendorPayable($user->id));

        $this->vendor = ['mobile' => $mobile->masked(), 'owed' => $owed->format()];
    }

    public function settle(SettleVendor $settle): void
    {
        $this->error = null;

        if ($this->vendorUserId === null) {
            return;
        }

        $actorId = Auth::id();

        if (! is_int($actorId)) {
            return;
        }

        try {
            $amount = Money::fromInput($this->amount);
            $settle->handle($this->vendorUserId, $amount, $actorId, $this->memo !== '' ? $this->memo : null);
        } catch (InvalidArgumentException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        Notification::make()->title('تسویه ثبت شد')->success()->send();

        $this->amount = '';
        $this->memo = '';
        $this->find(app(LedgerBalanceReader::class));
    }
}
