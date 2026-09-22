<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Actions;

use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Product;
use App\Modules\Commerce\Events\ProductRejected;
use Illuminate\Contracts\Events\Dispatcher;
use RuntimeException;

final readonly class RejectProduct
{
    public function __construct(
        private Dispatcher $events,
    ) {}

    public function handle(Product $product, int $actorId, string $note): Product
    {
        if ($product->status !== ProductStatus::InReview) {
            throw new RuntimeException('فقط محصول در انتظار بررسی رد می‌شود.');
        }

        if (trim($note) === '') {
            throw new RuntimeException('رد محصول بدون یادداشت برای فروشنده انجام نمی‌شود.');
        }

        $product->forceFill([
            'status' => ProductStatus::Rejected,
            'review_note' => $note,
            'reviewed_at' => now(),
            'reviewed_by' => $actorId,
        ])->save();

        $rejected = $product->refresh();

        $this->events->dispatch(new ProductRejected($rejected, $actorId, $note));

        return $rejected;
    }
}
