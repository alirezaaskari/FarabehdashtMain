<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Actions;

use App\Contracts\LedgerRecorder;
use App\Modules\Commerce\Domain\Enums\OrderStatus;
use App\Modules\Commerce\Domain\Order;
use App\Modules\Commerce\Domain\OrderItem;
use App\Modules\Commerce\Domain\Refund;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Ledger\LedgerEntryLine;
use App\Support\Ledger\LedgerTransactionRequest;
use App\Support\Money;
use Illuminate\Support\Str;

/**
 * اجرای بازگشت وجه — کامل یا جزئی.
 *
 * وجه به **کیف پول خریدار** برمی‌گردد، نه به کارت بانکی از راه درگاه
 * (`docs/architecture/admin-panel.md` §۴)؛ به همین دلیل حساب خزانه اصلاً در
 * این تراکنش شرکت نمی‌کند — فقط بازتقسیم چیزی است که قبلاً به کیف پول
 * پلتفرم وارد شده: بخشی از درآمد پلتفرم و بدهی فروشنده به بستانکاری خریدار
 * تبدیل می‌شود.
 */
final readonly class IssueRefund
{
    public function __construct(
        private PreviewRefund $preview,
        private LedgerRecorder $ledger,
    ) {}

    public function handle(OrderItem $item, Money $amount, int $actorId, ?string $reason = null): Refund
    {
        $effect = $this->preview->handle($item, $amount);

        $refund = Refund::query()->create([
            'uuid' => (string) Str::uuid7(),
            'order_item_id' => $item->id,
            'amount_toman' => $amount->toman,
            'reason' => $reason,
            'created_by' => $actorId,
        ]);

        $entries = [
            new LedgerEntryLine(LedgerAccountRef::wallet($item->order->buyer_user_id), EntryDirection::Credit, $effect->walletCredit),
        ];

        if (! $effect->platformRevenueDebit->isZero()) {
            $entries[] = new LedgerEntryLine(new LedgerAccountRef(AccountType::PlatformRevenue), EntryDirection::Debit, $effect->platformRevenueDebit);
        }

        if (! $effect->vendorPayableDebit->isZero()) {
            $entries[] = new LedgerEntryLine(LedgerAccountRef::vendorPayable($item->vendor_user_id), EntryDirection::Debit, $effect->vendorPayableDebit);
        }

        $this->ledger->record(new LedgerTransactionRequest(
            kind: 'commerce.refund_issued',
            idempotencyKey: 'commerce.refund:'.$refund->uuid,
            entries: $entries,
            referenceType: Refund::class,
            referenceId: $refund->uuid,
            memo: $reason,
            createdBy: $actorId,
        ));

        $this->updateOrderStatus($item->order);

        return $refund;
    }

    private function updateOrderStatus(Order $order): void
    {
        $order->load('items.refunds');

        $allFullyRefunded = $order->items->every(fn (OrderItem $i): bool => $i->isFullyRefunded());
        $anyRefunded = $order->items->contains(fn (OrderItem $i): bool => ! $i->refundedAmount()->isZero());

        $status = match (true) {
            $allFullyRefunded => OrderStatus::Refunded,
            $anyRefunded => OrderStatus::PartiallyRefunded,
            default => $order->status,
        };

        if ($status !== $order->status) {
            $order->forceFill(['status' => $status])->save();
        }
    }
}
