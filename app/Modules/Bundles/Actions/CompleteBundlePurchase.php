<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Actions;

use App\Contracts\LedgerRecorder;
use App\Modules\Bundles\Domain\BundlePurchase;
use App\Modules\Bundles\Domain\Enums\PurchaseStatus;
use App\Modules\Bundles\Events\BundlePurchased;
use App\Modules\Bundles\Services\ComponentCatalog;
use App\Support\Ledger\AccountType;
use App\Support\Ledger\EntryDirection;
use App\Support\Ledger\LedgerAccountRef;
use App\Support\Ledger\LedgerEntryLine;
use App\Support\Ledger\LedgerTransactionRequest;
use App\Support\Money;
use App\Support\Payments\PaymentSource;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use RuntimeException;

/**
 * یک سفارش برای کل بسته: بدهکار خزانه یا کیف پول خریدار؛ بستانکار بدهی به
 * صاحب هر جزء (فروشنده یا مدرس) به اندازه سهمش و درآمد پلتفرم برای بقیه.
 * سپس هر جزء از راه ماژول صاحبش به خریدار داده می‌شود.
 *
 * کلید یکتای دفتر کل بازگشت تکراری درگاه را بی‌اثر می‌کند و همه در یک
 * تراکنش است: یا پول و همه اجزا، یا هیچ‌کدام.
 */
final readonly class CompleteBundlePurchase
{
    public function __construct(
        private LedgerRecorder $ledger,
        private ComponentCatalog $catalog,
        private ConnectionInterface $db,
        private Dispatcher $events,
    ) {}

    public function handle(BundlePurchase $purchase, ?string $gatewayRefId, PaymentSource $source = PaymentSource::Gateway): BundlePurchase
    {
        if ($purchase->status !== PurchaseStatus::Pending) {
            throw new RuntimeException('فقط خرید در انتظار پرداخت تکمیل می‌شود.');
        }

        $purchase->loadMissing('lines');

        $this->db->transaction(function () use ($purchase, $gatewayRefId, $source): void {
            $this->ledger->record(new LedgerTransactionRequest(
                kind: 'bundles.purchase_paid',
                idempotencyKey: 'bundles.purchase_paid:'.$purchase->uuid,
                entries: $this->entries($purchase, $source),
                referenceType: BundlePurchase::class,
                referenceId: $purchase->uuid,
                memo: 'بسته راه‌حل '.$purchase->uuid,
            ));

            $purchase->forceFill([
                'status' => PurchaseStatus::Paid,
                'payment_source' => $source,
                'gateway_ref_id' => $gatewayRefId,
                'paid_at' => now(),
            ])->save();

            foreach ($purchase->lines as $line) {
                $componentSource = $this->catalog->source($line->kind)
                    ?? throw new RuntimeException('بخش «'.$line->title.'» در حال حاضر در دسترس نیست.');

                $componentSource->grant($purchase->user_id, $line->ref, $purchase->uuid);
            }
        });

        $this->events->dispatch(new BundlePurchased($purchase));

        return $purchase->refresh();
    }

    /** @return list<LedgerEntryLine> */
    private function entries(BundlePurchase $purchase, PaymentSource $source): array
    {
        $entries = [new LedgerEntryLine($source->debitAccount($purchase->user_id), EntryDirection::Debit, $purchase->price())];

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
            $entries[] = new LedgerEntryLine(LedgerAccountRef::vendorPayable($ownerId), EntryDirection::Credit, $amount);
        }

        if (! $platform->isZero()) {
            $entries[] = new LedgerEntryLine(new LedgerAccountRef(AccountType::PlatformRevenue), EntryDirection::Credit, $platform);
        }

        return $entries;
    }
}
