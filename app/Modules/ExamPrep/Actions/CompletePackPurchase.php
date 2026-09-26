<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Actions;

use App\Contracts\LedgerRecorder;
use App\Modules\ExamPrep\Domain\Enums\PurchaseStatus;
use App\Modules\ExamPrep\Domain\PackPurchase;
use App\Modules\ExamPrep\Events\PackPurchased;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Ledger\LedgerEntryLine;
use App\Support\Ledger\LedgerTransactionRequest;
use App\Support\Payments\PaymentSource;
use Illuminate\Contracts\Events\Dispatcher;
use RuntimeException;

/**
 * اثر مالی خرید بسته: بدهکار خزانه (درگاه) یا کیف پول خریدار، بستانکار
 * درآمد پلتفرم. بسته محصول خود پلتفرم است و سهم فروشنده‌ای ندارد. کلید
 * یکتایی دفتر کل بازگشت تکراری درگاه را بی‌اثر می‌کند.
 */
final readonly class CompletePackPurchase
{
    public function __construct(
        private LedgerRecorder $ledger,
        private Dispatcher $events,
    ) {}

    public function handle(PackPurchase $purchase, ?string $gatewayRefId, PaymentSource $source = PaymentSource::Gateway): PackPurchase
    {
        if ($purchase->status !== PurchaseStatus::Pending) {
            throw new RuntimeException('فقط خرید در انتظار پرداخت تکمیل می‌شود.');
        }

        if ($source === PaymentSource::Free || $purchase->price()->isZero()) {
            throw new RuntimeException('بسته آزمون رایگان نیست؛ بدون پرداخت تکمیل نمی‌شود.');
        }

        $this->ledger->record(new LedgerTransactionRequest(
            kind: 'exam_prep.pack_paid',
            idempotencyKey: 'exam_prep.pack_paid:'.$purchase->uuid,
            entries: [
                new LedgerEntryLine($source->debitAccount($purchase->user_id), EntryDirection::Debit, $purchase->price()),
                new LedgerEntryLine(new LedgerAccountRef(AccountType::PlatformRevenue), EntryDirection::Credit, $purchase->price()),
            ],
            referenceType: PackPurchase::class,
            referenceId: $purchase->uuid,
            memo: 'بسته آزمون '.$purchase->uuid,
        ));

        $purchase->forceFill([
            'status' => PurchaseStatus::Paid,
            'payment_source' => $source,
            'gateway_ref_id' => $gatewayRefId,
            'paid_at' => now(),
        ])->save();

        $this->events->dispatch(new PackPurchased($purchase));

        return $purchase->refresh();
    }
}
