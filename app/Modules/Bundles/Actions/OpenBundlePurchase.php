<?php

declare(strict_types=1);

namespace App\Modules\Bundles\Actions;

use App\Contracts\SalesSwitch;
use App\Modules\Bundles\Domain\Bundle;
use App\Modules\Bundles\Domain\BundlePurchase;
use App\Modules\Bundles\Domain\BundlePurchaseLine;
use App\Modules\Bundles\Domain\Enums\PurchaseStatus;
use App\Modules\Bundles\Services\ComponentCatalog;
use App\Modules\Bundles\Services\PriceSplitter;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * خرید در انتظار پرداخت، با قیمت و سهم هر جزء همین لحظه.
 *
 * خریداری که بعضی اجزا را پیش‌تر دارد هم می‌تواند بسته را بخرد؛ صفحه بسته
 * پیش از خرید این را نشان می‌دهد و اعطای جزء تکراری بی‌اثر است.
 */
final readonly class OpenBundlePurchase
{
    public function __construct(
        private ComponentCatalog $catalog,
        private PriceSplitter $splitter,
        private SalesSwitch $sales,
        private ConnectionInterface $db,
    ) {}

    public function handle(Bundle $bundle, int $userId): BundlePurchase
    {
        if (! $bundle->isPublished() || ! $this->sales->isOpen(SalesSwitch::SOLUTION_BUNDLE)) {
            throw new InvalidArgumentException('فروش این بسته در حال حاضر فعال نیست.');
        }

        $components = $this->catalog->available($bundle)
            ?? throw new InvalidArgumentException('یکی از اجزای این بسته دیگر فروختنی نیست.');

        $lines = $this->splitter->split($bundle->price(), $components);

        return $this->db->transaction(function () use ($bundle, $userId, $lines): BundlePurchase {
            BundlePurchase::query()
                ->where('bundle_id', $bundle->id)
                ->where('user_id', $userId)
                ->where('status', PurchaseStatus::Pending)
                ->update(['status' => PurchaseStatus::Failed]);

            $purchase = BundlePurchase::query()->create([
                'uuid' => (string) Str::uuid7(),
                'bundle_id' => $bundle->id,
                'user_id' => $userId,
                'status' => PurchaseStatus::Pending,
                'price_toman' => $bundle->price_toman,
            ]);

            foreach ($lines as $line) {
                BundlePurchaseLine::query()->create([
                    'bundle_purchase_id' => $purchase->id,
                    'kind' => $line['component']->kind,
                    'ref' => $line['component']->ref,
                    'title' => $line['component']->title,
                    'list_price_toman' => $line['component']->listPrice->toman,
                    'allocated_toman' => $line['allocated']->toman,
                    'owner_user_id' => $line['owner']->isZero() ? null : $line['component']->ownerUserId,
                    'owner_amount_toman' => $line['owner']->toman,
                    'platform_amount_toman' => $line['platform']->toman,
                ]);
            }

            return $purchase;
        });
    }
}
