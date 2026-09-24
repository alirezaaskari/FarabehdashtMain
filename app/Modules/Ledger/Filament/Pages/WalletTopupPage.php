<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Filament\Pages;

use App\Models\User;
use App\Modules\Ledger\Actions\CreditWalletManually;
use App\Modules\Ledger\Domain\Wallet;
use App\Support\Admin\NavigationGroup;
use App\Support\Mobile;
use App\Support\Money;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use UnitEnum;

/**
 * شارژ دستی کیف پول — تنها راه ورود پول به سیستم در نسخه یک (ADR-0003).
 *
 * کاربر با شماره موبایل پیدا می‌شود، نه شناسه عددی: مدیر مالی شماره را از
 * تیکت پشتیبانی یا رسید بانکی دارد، نه شناسه دیتابیس.
 *
 * دکمه ثبت هنگام ارسال غیرفعال می‌شود (`wire:target`) تا دوبار کلیک، دو
 * تراکنش نسازد؛ `CreditWalletManually` برای هر فراخوان یک `idempotency_key`
 * تازه می‌سازد، پس این محافظت در همین لایه UI لازم است، نه در سرویس.
 */
final class WalletTopupPage extends Page
{
    public const ABILITY = 'admin.wallet.manage';

    protected static ?string $slug = 'wallet-topup';

    protected static ?int $navigationSort = 50;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Finance;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected string $view = 'ledger::filament.pages.wallet-topup';

    public string $mobile = '';

    public string $amount = '';

    public string $memo = '';

    public ?string $error = null;

    /** @var array{mobile: string, balance: string}|null */
    public ?array $result = null;

    public static function getNavigationLabel(): string
    {
        return 'شارژ کیف پول';
    }

    public function getTitle(): string
    {
        return 'شارژ دستی کیف پول';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    public function credit(CreditWalletManually $action): void
    {
        $this->error = null;
        $this->result = null;

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

        try {
            $amount = Money::fromInput($this->amount);
        } catch (InvalidArgumentException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        if ($amount->isZero()) {
            $this->error = 'مبلغ باید بزرگ‌تر از صفر باشد.';

            return;
        }

        $action->handle($user->id, $amount, $this->actorId(), $this->memo !== '' ? $this->memo : null);

        $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();

        $this->result = ['mobile' => $mobile->masked(), 'balance' => $wallet->balance()->format()];

        Notification::make()
            ->title(sprintf('کیف پول %s شارژ شد', $mobile->masked()))
            ->body(sprintf('موجودی تازه: %s', $wallet->balance()->format()))
            ->success()
            ->send();

        $this->mobile = '';
        $this->amount = '';
        $this->memo = '';
    }

    private function actorId(): ?int
    {
        $id = Auth::id();

        return is_int($id) ? $id : null;
    }
}
