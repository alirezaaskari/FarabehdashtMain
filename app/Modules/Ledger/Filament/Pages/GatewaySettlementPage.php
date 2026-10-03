<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Filament\Pages;

use App\Contracts\LedgerBalanceReader;
use App\Modules\Ledger\Actions\SettleGatewayClearing;
use App\Support\Admin\NavigationGroup;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Money;
use BackedEnum;
use DomainException;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use UnitEnum;

/**
 * «واریز درگاه»: پولی که زرین‌پال تأیید کرده ولی هنوز به حساب بانکی نرسیده،
 * و ثبت هر واریز با شماره پیگیری‌اش (بخش ۱۹-۱). مانده دو حساب امانت (خدمت و
 * پروژه) هم جدا نشان داده می‌شود (بخش ۲۱-۱).
 */
final class GatewaySettlementPage extends Page
{
    public const ABILITY = 'admin.settlement.approve';

    protected static ?string $slug = 'gateway-settlement';

    protected static ?int $navigationSort = 55;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Finance;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected string $view = 'ledger::filament.pages.gateway-settlement';

    public string $amount = '';

    public string $reference = '';

    public ?string $error = null;

    public static function getNavigationLabel(): string
    {
        return 'واریز درگاه';
    }

    public function getTitle(): string
    {
        return 'واریز درگاه به حساب بانکی';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    /** @return array{outstanding: string, escrows: array<string, string>} */
    protected function getViewData(): array
    {
        $balances = app(LedgerBalanceReader::class);

        return [
            'outstanding' => app(SettleGatewayClearing::class)->outstanding()->format(),
            // پول خدمت و پروژه جدا نگه داشته می‌شود؛ هر کدام بدهی سایت به مشتری است تا کار تمام شود.
            'escrows' => collect([AccountType::ServiceEscrow, AccountType::ProjectEscrow])
                ->mapWithKeys(fn (AccountType $type): array => [$type->label() => $balances->balanceOf(new LedgerAccountRef($type))->format()])
                ->all(),
        ];
    }

    public function settle(SettleGatewayClearing $action): void
    {
        $this->error = null;

        try {
            $receipt = $action->handle(Money::fromInput($this->amount), $this->reference, (int) Auth::id());
        } catch (InvalidArgumentException|DomainException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        Notification::make()
            ->title($receipt->alreadyRecorded ? 'این واریز پیش‌تر ثبت شده بود' : 'واریز ثبت شد')
            ->success()
            ->send();

        $this->amount = '';
        $this->reference = '';
    }
}
