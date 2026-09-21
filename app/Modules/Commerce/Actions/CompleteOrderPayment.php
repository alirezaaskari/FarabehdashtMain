<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Actions;

use App\Contracts\LedgerRecorder;
use App\Modules\Commerce\Domain\Enums\OrderStatus;
use App\Modules\Commerce\Domain\Order;
use App\Modules\Commerce\Domain\OrderItem;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Ledger\LedgerEntryLine;
use App\Support\Ledger\LedgerTransactionRequest;
use App\Support\Money;
use RuntimeException;

/**
 * ثبت اثر مالی یک سفارش تأییدشده در دفتر کل.
 *
 * طرف بدهکار تراکنش حساب خزانه است، نه «حساب واسط درگاه» — ADR-0003 خودش
 * `gateway_clearing` را «فاز آینده» می‌داند (تفاوت لحظه تأیید زرین‌پال با
 * لحظه واقعی تسویه به حساب بانکی). تا آن فاز، این تصمیم پول تأییدشده درگاه
 * را بلافاصله به‌عنوان نقدینگی خزانه در نظر می‌گیرد — همان حسابی که DEC-20
 * برای شارژ دستی ساخته بود، این‌جا برای دومین‌بار و با همان استدلال
 * («پول از بیرون سیستم وارد می‌شود») به‌کار می‌رود.
 *
 * یک سفارش می‌تواند چند فروشنده داشته باشد؛ برای هرکدام یک ردیف بستانکار
 * جدا به `vendor_payable` خودش نوشته می‌شود.
 *
 * idempotency_key از uuid سفارش ساخته می‌شود، پس Callback تکراری زرین‌پال
 * (بازگشت به صفحه، تلاش دوباره) هرگز اثر مالی دوم نمی‌گذارد.
 */
final readonly class CompleteOrderPayment
{
    public function __construct(private LedgerRecorder $ledger) {}

    public function handle(Order $order, string $gatewayRefId): Order
    {
        if ($order->status !== OrderStatus::Pending) {
            throw new RuntimeException('فقط سفارش در انتظار پرداخت تکمیل می‌شود.');
        }

        $entries = [
            new LedgerEntryLine(new LedgerAccountRef(AccountType::Treasury), EntryDirection::Debit, $order->total()),
        ];

        $totalCommission = Money::zero();

        foreach ($this->vendorAmounts($order->items) as $vendorUserId => $amount) {
            $entries[] = new LedgerEntryLine(LedgerAccountRef::vendorPayable($vendorUserId), EntryDirection::Credit, $amount);
        }

        foreach ($order->items as $item) {
            $totalCommission = $totalCommission->plus($item->commission());
        }

        if (! $totalCommission->isZero()) {
            $entries[] = new LedgerEntryLine(new LedgerAccountRef(AccountType::PlatformRevenue), EntryDirection::Credit, $totalCommission);
        }

        $this->ledger->record(new LedgerTransactionRequest(
            kind: 'commerce.order_paid',
            idempotencyKey: 'commerce.order_paid:'.$order->uuid,
            entries: $entries,
            referenceType: Order::class,
            referenceId: $order->uuid,
            memo: 'پرداخت سفارش '.$order->uuid,
        ));

        $order->forceFill([
            'status' => OrderStatus::Paid,
            'gateway_ref_id' => $gatewayRefId,
            'paid_at' => now(),
        ])->save();

        return $order->refresh();
    }

    /**
     * @param  iterable<OrderItem>  $items
     * @return array<int, Money>
     */
    private function vendorAmounts(iterable $items): array
    {
        /** @var array<int, Money> $amounts */
        $amounts = [];

        foreach ($items as $item) {
            if ($item->vendorAmount()->isZero()) {
                continue;
            }

            $amounts[$item->vendor_user_id] = ($amounts[$item->vendor_user_id] ?? Money::zero())
                ->plus($item->vendorAmount());
        }

        return $amounts;
    }
}
