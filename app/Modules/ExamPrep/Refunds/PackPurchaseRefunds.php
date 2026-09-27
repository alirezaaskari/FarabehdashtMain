<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Refunds;

use App\Contracts\LedgerRecorder;
use App\Contracts\RefundablePurchases;
use App\Modules\ExamPrep\Domain\Enums\PurchaseStatus;
use App\Modules\ExamPrep\Domain\PackPurchase;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Ledger\LedgerEntryLine;
use App\Support\Ledger\LedgerTransactionRequest;
use App\Support\Money;
use App\Support\Payments\RefundablePurchase;
use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;

/**
 * بازگشت وجه بسته آزمون: کل مبلغ از درآمد سایت (نویسنده سهمی نداشت) به کیف
 * پول و بسته شدن تمرین و آزمون. کارنامه‌های گذشته برای خریدار می‌مانند.
 */
final readonly class PackPurchaseRefunds implements RefundablePurchases
{
    public function __construct(
        private LedgerRecorder $ledger,
        private ConnectionInterface $db,
    ) {}

    public function label(): string
    {
        return 'بسته آزمون';
    }

    public function paidBy(int $userId): array
    {
        return PackPurchase::query()
            ->with('pack')
            ->where('user_id', $userId)
            ->whereIn('status', [PurchaseStatus::Paid->value, PurchaseStatus::Refunded->value])
            ->where('price_toman', '>', 0)
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (PackPurchase $purchase): RefundablePurchase => new RefundablePurchase(
                uuid: $purchase->uuid,
                title: 'بسته «'.$purchase->pack->title.'»',
                paid: $purchase->price(),
                refundable: $purchase->price(),
                paidAt: $purchase->paid_at,
                effect: 'تمرین و آزمون این بسته بسته می‌شود؛ کارنامه‌های گذشته می‌مانند.',
                refunded: $purchase->status === PurchaseStatus::Refunded,
            ))
            ->all();
    }

    public function refund(string $uuid, int $actorId, string $reason): Money
    {
        return $this->db->transaction(function () use ($uuid, $actorId, $reason): Money {
            $purchase = PackPurchase::query()->where('uuid', $uuid)->lockForUpdate()->firstOrFail();

            if ($purchase->status !== PurchaseStatus::Paid || $purchase->price()->isZero()) {
                throw new InvalidArgumentException('فقط خرید پولی پرداخت‌شده وجه برگشتی دارد.');
            }

            $this->ledger->record(new LedgerTransactionRequest(
                kind: 'exam_prep.pack_refunded',
                idempotencyKey: 'exam_prep.pack_refunded:'.$purchase->uuid,
                entries: [
                    new LedgerEntryLine(new LedgerAccountRef(AccountType::PlatformRevenue), EntryDirection::Debit, $purchase->price()),
                    new LedgerEntryLine(LedgerAccountRef::wallet($purchase->user_id), EntryDirection::Credit, $purchase->price()),
                ],
                referenceType: PackPurchase::class,
                referenceId: $purchase->uuid,
                memo: 'بازگشت وجه بسته آزمون: '.$reason,
                createdBy: $actorId,
            ));

            $purchase->forceFill(['status' => PurchaseStatus::Refunded])->save();

            return $purchase->price();
        });
    }
}
