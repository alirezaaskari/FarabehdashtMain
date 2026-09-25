<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Actions;

use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Product;
use App\Modules\Commerce\Events\ProductVersionsRejected;
use App\Modules\Commerce\Services\ProductVersionReview;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;
use RuntimeException;

/**
 * رد نسخه‌های تازه یک محصول منتشرشده. محصول منتشر می‌ماند و خریدار همان نسخه
 * تأییدشده قبلی را می‌گیرد؛ دلیل برای فروشنده فرستاده می‌شود.
 */
final readonly class RejectProductVersions
{
    public function __construct(
        private ProductVersionReview $review,
        private Dispatcher $events,
    ) {}

    public function handle(Product $product, int $actorId, string $note): void
    {
        if (trim($note) === '') {
            throw new InvalidArgumentException('رد بدون یادداشت ممکن نیست؛ فروشنده باید بداند چه چیزی را اصلاح کند.');
        }

        if ($product->status !== ProductStatus::Published || ! $this->review->hasPending($product)) {
            throw new RuntimeException('این محصول نسخه در انتظار تأییدی ندارد.');
        }

        $versions = $this->review->pendingVersions($product);
        $this->review->reject($product, trim($note));

        $this->events->dispatch(new ProductVersionsRejected($product, $actorId, trim($note), $versions));
    }
}
