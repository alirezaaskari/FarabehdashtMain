<?php

declare(strict_types=1);

namespace App\Modules\Reports\Actions;

use App\Contracts\LedgerRecorder;
use App\Modules\Reports\Domain\Enums\ReportPurchaseStatus;
use App\Modules\Reports\Domain\ReportPurchase;
use App\Modules\Reports\Events\ReportPurchased;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Ledger\LedgerEntryLine;
use App\Support\Ledger\LedgerTransactionRequest;
use App\Support\Payments\PaymentSource;
use Illuminate\Contracts\Events\Dispatcher;
use RuntimeException;

/**
 * ثبت اثر مالی خرید تکی گزارش: بدهکار خزانه (درگاه) یا کیف پول خریدار،
 * بستانکار درآمد پلتفرم — سهم فروشنده‌ای در کار نیست.
 *
 * کلید یکتایی دفتر کل بازگشت تکراری درگاه را بی‌اثر می‌کند.
 */
final readonly class CompleteReportPurchase
{
    public function __construct(
        private LedgerRecorder $ledger,
        private Dispatcher $events,
    ) {}

    public function handle(ReportPurchase $purchase, ?string $gatewayRefId, PaymentSource $source = PaymentSource::Gateway): ReportPurchase
    {
        if ($purchase->status !== ReportPurchaseStatus::Pending) {
            throw new RuntimeException('فقط خرید در انتظار پرداخت تکمیل می‌شود.');
        }

        if ($source === PaymentSource::Free || $purchase->price()->isZero()) {
            throw new RuntimeException('خرید گزارش رایگان نیست؛ بدون پرداخت تکمیل نمی‌شود.');
        }

        $this->ledger->record(new LedgerTransactionRequest(
            kind: 'reports.purchase_paid',
            idempotencyKey: 'reports.purchase_paid:'.$purchase->uuid,
            entries: [
                new LedgerEntryLine($source->debitAccount($purchase->user_id), EntryDirection::Debit, $purchase->price()),
                new LedgerEntryLine(new LedgerAccountRef(AccountType::PlatformRevenue), EntryDirection::Credit, $purchase->price()),
            ],
            referenceType: ReportPurchase::class,
            referenceId: $purchase->uuid,
            memo: 'صدور گزارش '.$purchase->uuid,
        ));

        $purchase->forceFill([
            'status' => ReportPurchaseStatus::Paid,
            'payment_source' => $source,
            'gateway_ref_id' => $gatewayRefId,
            'paid_at' => now(),
        ])->save();

        $this->events->dispatch(new ReportPurchased($purchase));

        return $purchase->refresh();
    }
}
