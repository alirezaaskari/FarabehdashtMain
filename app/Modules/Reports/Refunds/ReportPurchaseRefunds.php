<?php

declare(strict_types=1);

namespace App\Modules\Reports\Refunds;

use App\Contracts\LedgerRecorder;
use App\Contracts\RefundablePurchases;
use App\Modules\Reports\Actions\RevokeReport;
use App\Modules\Reports\Domain\Enums\ReportPurchaseStatus;
use App\Modules\Reports\Domain\Enums\ReportStatus;
use App\Modules\Reports\Domain\Report;
use App\Modules\Reports\Domain\ReportPurchase;
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
 * بازگشت وجه صدور تکی گزارش. خرید همه نسخه‌های بعدی همان گزارش را هم
 * می‌پوشاند، پس با برگشت پول، گزارش صادرشده و نسخه‌های بعدی‌اش باطل می‌شوند
 * و صفحه تأیید اصالت «باطل‌شده» نشان می‌دهد.
 */
final readonly class ReportPurchaseRefunds implements RefundablePurchases
{
    public function __construct(
        private LedgerRecorder $ledger,
        private RevokeReport $revoke,
        private ConnectionInterface $db,
    ) {}

    public function label(): string
    {
        return 'صدور گزارش';
    }

    public function paidBy(int $userId): array
    {
        return ReportPurchase::query()
            ->with('report')
            ->where('user_id', $userId)
            ->whereIn('status', [ReportPurchaseStatus::Paid->value, ReportPurchaseStatus::Refunded->value])
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (ReportPurchase $purchase): RefundablePurchase => new RefundablePurchase(
                uuid: $purchase->uuid,
                title: 'گزارش «'.($purchase->report->title ?? 'بی‌عنوان').'»',
                paid: $purchase->price(),
                refundable: $purchase->price(),
                paidAt: $purchase->paid_at,
                effect: 'گزارش صادرشده و نسخه‌های بعدی‌اش باطل می‌شوند.',
                refunded: $purchase->status === ReportPurchaseStatus::Refunded,
            ))
            ->all();
    }

    public function refund(string $uuid, int $actorId, string $reason): Money
    {
        return $this->db->transaction(function () use ($uuid, $actorId, $reason): Money {
            $purchase = ReportPurchase::query()->where('uuid', $uuid)->lockForUpdate()->firstOrFail();

            if ($purchase->status !== ReportPurchaseStatus::Paid) {
                throw new InvalidArgumentException('فقط خرید پرداخت‌شده وجه برگشتی دارد.');
            }

            $this->ledger->record(new LedgerTransactionRequest(
                kind: 'reports.purchase_refunded',
                idempotencyKey: 'reports.purchase_refunded:'.$purchase->uuid,
                entries: [
                    new LedgerEntryLine(new LedgerAccountRef(AccountType::PlatformRevenue), EntryDirection::Debit, $purchase->price()),
                    new LedgerEntryLine(LedgerAccountRef::wallet($purchase->user_id), EntryDirection::Credit, $purchase->price()),
                ],
                referenceType: ReportPurchase::class,
                referenceId: $purchase->uuid,
                memo: 'بازگشت وجه صدور گزارش: '.$reason,
                createdBy: $actorId,
            ));

            $purchase->forceFill(['status' => ReportPurchaseStatus::Refunded])->save();

            $report = Report::query()->find($purchase->report_id);

            for ($depth = 0; $report !== null && $depth < 50; $depth++) {
                if (in_array($report->status, [ReportStatus::Issued, ReportStatus::Superseded], true)) {
                    $this->revoke->handle($report, 'بازگشت وجه صدور گزارش', $actorId);
                }

                $report = $report->superseded_by_id === null ? null : Report::query()->find($report->superseded_by_id);
            }

            return $purchase->price();
        });
    }
}
