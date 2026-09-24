<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Filament\Pages;

use App\Modules\Commerce\Actions\IssueRefund;
use App\Modules\Commerce\Actions\PreviewRefund;
use App\Modules\Commerce\Domain\Order;
use App\Modules\Commerce\Domain\OrderItem;
use App\Support\Admin\NavigationGroup;
use App\Support\Money;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use UnitEnum;

/**
 * بازگشت وجه با پیش‌نمایش اثر مالی (`docs/architecture/admin-panel.md` §۴).
 *
 * دو گام دقیقاً جدا: «پیش‌نمایش» فقط می‌خواند و اثر را در `$preview` نگه
 * می‌دارد (بین دو درخواست Livewire ماندگار است)؛ «اجرا» با همان مبلغ دوباره
 * محاسبه و می‌نویسد — همان الگوی دوگام صفحه ورود CSV بانک مواد شیمیایی.
 */
final class RefundPage extends Page
{
    public const ABILITY = 'admin.refund.issue';

    protected static ?string $slug = 'commerce-refund';

    protected static ?int $navigationSort = 48;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Finance;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptRefund;

    protected string $view = 'commerce::filament.pages.refund';

    public string $orderUuid = '';

    public ?string $error = null;

    /** @var list<array<string, mixed>>|null */
    public ?array $items = null;

    /** @var array<int, string> */
    public array $amounts = [];

    /** @var array<int, array{wallet: string, vendor: string, platform: string}> */
    public array $previews = [];

    public static function getNavigationLabel(): string
    {
        return 'بازگشت وجه';
    }

    public function getTitle(): string
    {
        return 'بازگشت وجه سفارش';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(self::ABILITY) === true;
    }

    public function find(): void
    {
        $this->error = null;
        $this->items = null;
        $this->previews = [];

        $order = Order::query()->where('uuid', trim($this->orderUuid))->with('items.product')->first();

        if ($order === null) {
            $this->error = 'سفارشی با این شناسه پیدا نشد.';

            return;
        }

        $this->items = $order->items->map(static fn (OrderItem $item): array => [
            'id' => $item->id,
            'product' => $item->product->title,
            'unitPrice' => $item->unitPrice()->format(),
            'remaining' => $item->remainingRefundable()->format(),
            'remainingToman' => $item->remainingRefundable()->toman,
        ])->all();
    }

    public function preview(int $itemId, PreviewRefund $preview): void
    {
        $item = OrderItem::query()->find($itemId);
        $amount = trim($this->amounts[$itemId] ?? '');

        if ($item === null || $amount === '') {
            return;
        }

        try {
            $effect = $preview->handle($item, Money::fromInput($amount));
        } catch (InvalidArgumentException $exception) {
            Notification::make()->title('مبلغ نامعتبر')->body($exception->getMessage())->danger()->send();

            return;
        }

        $this->previews[$itemId] = [
            'wallet' => $effect->walletCredit->format(),
            'vendor' => $effect->vendorPayableDebit->format(),
            'platform' => $effect->platformRevenueDebit->format(),
        ];
    }

    public function confirm(int $itemId, IssueRefund $issue): void
    {
        $item = OrderItem::query()->find($itemId);
        $amount = trim($this->amounts[$itemId] ?? '');
        $actorId = Auth::id();

        if ($item === null || $amount === '' || ! is_int($actorId)) {
            return;
        }

        try {
            $issue->handle($item, Money::fromInput($amount), $actorId);

            Notification::make()->title('بازگشت وجه ثبت شد')->success()->send();
        } catch (InvalidArgumentException $exception) {
            Notification::make()->title('ثبت نشد')->body($exception->getMessage())->danger()->send();
        }

        unset($this->previews[$itemId], $this->amounts[$itemId]);
        $this->find();
    }
}
