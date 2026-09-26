<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Filament\Pages;

use App\Modules\Commerce\Actions\MarkPayoutPaid;
use App\Modules\Commerce\Actions\RejectPayout;
use App\Modules\Commerce\Domain\PayoutRequest;
use App\Modules\Commerce\Services\Payouts;
use App\Support\Admin\NavigationGroup;
use App\Support\JalaliDate;
use App\Support\Mobile;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use UnitEnum;

/**
 * صف درخواست‌های تسویه (DEC-45): مدیر مالی هفته‌ای یک بار به شبای هر
 * درخواست واریز می‌کند و شماره پیگیری بانک را همین‌جا ثبت می‌کند.
 *
 * شبای کامل فقط این‌جا دیده می‌شود، چون واریز دستی بدون آن ممکن نیست.
 */
final class PayoutRequestsPage extends Page
{
    public const ABILITY = VendorSettlementPage::ABILITY;

    protected static ?string $slug = 'payout-requests';

    protected static ?int $navigationSort = 48;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Finance;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected string $view = 'commerce::filament.pages.payout-requests';

    /** @var list<array{id: int, amount: string, owed: string, short: bool, holder: string, sheba: string, vendor: string, since: string}> */
    public array $open = [];

    /** @var list<array{amount: string, status: string, vendor: string, decided: string, detail: string}> */
    public array $recent = [];

    /** @var array<int, string> شماره پیگیری هر درخواست باز، کلید شناسه درخواست */
    public array $references = [];

    /** @var array<int, string> دلیل رد هر درخواست باز */
    public array $reasons = [];

    public static function getNavigationLabel(): string
    {
        return 'درخواست‌های تسویه';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = PayoutRequest::query()->open()->count();

        return $count > 0 ? (string) $count : null;
    }

    public function getTitle(): string
    {
        return 'درخواست‌های تسویه';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    public function mount(): void
    {
        $this->load();
    }

    public function markPaid(int $id, MarkPayoutPaid $markPaid): void
    {
        $this->decide(fn () => $markPaid->handle($this->payout($id), (int) Auth::id(), $this->references[$id] ?? ''), 'واریز ثبت شد');
    }

    public function reject(int $id, RejectPayout $reject): void
    {
        $this->decide(fn () => $reject->handle($this->payout($id), (int) Auth::id(), $this->reasons[$id] ?? ''), 'درخواست رد شد');
    }

    private function decide(callable $action, string $done): void
    {
        try {
            $action();
        } catch (InvalidArgumentException $exception) {
            Notification::make()->title('انجام نشد')->body($exception->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title($done)->success()->send();
        $this->load();
    }

    private function payout(int $id): PayoutRequest
    {
        return PayoutRequest::query()->findOrFail($id);
    }

    private function load(): void
    {
        $payouts = app(Payouts::class);
        $this->references = [];
        $this->reasons = [];

        $this->open = PayoutRequest::query()
            ->open()
            ->with('user:id,name,mobile')
            ->oldest('id')
            ->get()
            ->map(static function (PayoutRequest $payout) use ($payouts): array {
                $owed = $payouts->owed($payout->user_id);

                return [
                    'id' => $payout->id,
                    'amount' => $payout->amount()->format(),
                    'owed' => $owed->format(),
                    'short' => $payout->amount()->isGreaterThan($owed),
                    'holder' => $payout->holder_name,
                    'sheba' => $payout->sheba()->grouped(),
                    'vendor' => self::vendor($payout),
                    'since' => JalaliDate::long($payout->created_at),
                ];
            })
            ->values()
            ->all();

        $this->recent = PayoutRequest::query()
            ->whereNotNull('decided_by')
            ->with('user:id,name,mobile')
            ->latest('decided_at')
            ->limit(15)
            ->get()
            ->map(static fn (PayoutRequest $payout): array => [
                'amount' => $payout->amount()->format(),
                'status' => $payout->status->label(),
                'vendor' => self::vendor($payout),
                'decided' => $payout->decided_at !== null ? JalaliDate::short($payout->decided_at) : '',
                'detail' => (string) ($payout->bank_reference ?? $payout->note),
            ])
            ->values()
            ->all();
    }

    private static function vendor(PayoutRequest $payout): string
    {
        $user = $payout->user;
        $mobile = Mobile::tryFromInput((string) $user->mobile);

        return ($user->name ?? 'کاربر').' · '.($mobile?->masked() ?? '#'.$payout->user_id);
    }
}
