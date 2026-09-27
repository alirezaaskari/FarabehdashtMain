<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Refunds;

use App\Contracts\LedgerRecorder;
use App\Contracts\RefundablePurchases;
use App\Modules\Bundles\Domain\BundlePurchase;
use App\Modules\Bundles\Domain\Enums\PurchaseStatus;
use App\Modules\Bundles\Services\ComponentCatalog;
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
 * بازگشت کامل وجه بسته راه‌حل: همان سهم‌های منجمد خرید برعکس می‌شوند (سهم
 * صاحب هر جزء از «بدهی به فروشنده» و بقیه از درآمد سایت) و هر جزئی که با
 * بسته داده شده بود پس گرفته می‌شود. اگر یک جزء پس‌گرفتنی نباشد، هیچ‌چیز
 * برنمی‌گردد.
 */
final readonly class BundlePurchaseRefunds implements RefundablePurchases
{
    public function __construct(
        private LedgerRecorder $ledger,
        private ComponentCatalog $catalog,
        private ConnectionInterface $db,
    ) {}

    public function label(): string
    {
        return 'بسته راه‌حل';
    }

    public function paidBy(int $userId): array
    {
        return BundlePurchase::query()
            ->with('bundle')
            ->where('user_id', $userId)
            ->whereIn('status', [PurchaseStatus::Paid->value, PurchaseStatus::Refunded->value])
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (BundlePurchase $purchase): RefundablePurchase => new RefundablePurchase(
                uuid: $purchase->uuid,
                title: 'بسته «'.$purchase->bundle->title.'»',
                paid: $purchase->price(),
                refundable: $purchase->price(),
                paidAt: $purchase->paid_at,
                effect: 'فایل‌ها، دوره‌ها و ماه‌های حرفه‌ای همین بسته پس گرفته می‌شوند و سهم صاحبان اجزا برمی‌گردد.',
                refunded: $purchase->status === PurchaseStatus::Refunded,
            ))
            ->all();
    }

    public function refund(string $uuid, int $actorId, string $reason): Money
    {
        return $this->db->transaction(function () use ($uuid, $actorId, $reason): Money {
            $purchase = BundlePurchase::query()->where('uuid', $uuid)->lockForUpdate()->firstOrFail();

            if ($purchase->status !== PurchaseStatus::Paid || $purchase->price()->isZero()) {
                throw new InvalidArgumentException('فقط خرید پرداخت‌شده وجه برگشتی دارد.');
            }

            $purchase->load('lines');

            foreach ($purchase->lines as $line) {
                $source = $this->catalog->source($line->kind)
                    ?? throw new InvalidArgumentException('بخش «'.$line->title.'» در حال حاضر در دسترس نیست؛ بسته برنمی‌گردد.');

                $source->revoke($purchase->user_id, $line->ref);
            }

            $this->ledger->record(new LedgerTransactionRequest(
                kind: 'bundles.purchase_refunded',
                idempotencyKey: 'bundles.purchase_refunded:'.$purchase->uuid,
                entries: $this->entries($purchase),
                referenceType: BundlePurchase::class,
                referenceId: $purchase->uuid,
                memo: 'بازگشت وجه بسته راه‌حل: '.$reason,
                createdBy: $actorId,
            ));

            $purchase->forceFill(['status' => PurchaseStatus::Refunded])->save();

            return $purchase->price();
        });
    }

    /** @return list<LedgerEntryLine> برعکس ثبت خرید */
    private function entries(BundlePurchase $purchase): array
    {
        $entries = [new LedgerEntryLine(LedgerAccountRef::wallet($purchase->user_id), EntryDirection::Credit, $purchase->price())];

        /** @var array<int, Money> $owners */
        $owners = [];
        $platform = Money::zero();

        foreach ($purchase->lines as $line) {
            $platform = $platform->plus($line->platformAmount());

            if ($line->owner_user_id !== null && ! $line->ownerAmount()->isZero()) {
                $owners[$line->owner_user_id] = ($owners[$line->owner_user_id] ?? Money::zero())->plus($line->ownerAmount());
            }
        }

        foreach ($owners as $ownerId => $amount) {
            $entries[] = new LedgerEntryLine(LedgerAccountRef::vendorPayable($ownerId), EntryDirection::Debit, $amount);
        }

        if (! $platform->isZero()) {
            $entries[] = new LedgerEntryLine(new LedgerAccountRef(AccountType::PlatformRevenue), EntryDirection::Debit, $platform);
        }

        return $entries;
    }
}
