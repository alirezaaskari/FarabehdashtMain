<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Actions;

use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Product;
use App\Modules\Commerce\Events\ProductVersionsApproved;
use App\Modules\Commerce\Services\ProductVersionReview;
use Illuminate\Contracts\Events\Dispatcher;
use RuntimeException;

/** تأیید نسخه‌های تازه یک محصول منتشرشده؛ از این پس خریدار آن‌ها را دانلود می‌کند. */
final readonly class ApproveProductVersions
{
    public function __construct(
        private ProductVersionReview $review,
        private Dispatcher $events,
    ) {}

    public function handle(Product $product, int $actorId): void
    {
        if ($product->status !== ProductStatus::Published || ! $this->review->hasPending($product)) {
            throw new RuntimeException('این محصول نسخه در انتظار تأییدی ندارد.');
        }

        $versions = $this->review->pendingVersions($product);
        $this->review->approve($product);

        $this->events->dispatch(new ProductVersionsApproved($product, $actorId, $versions));
    }
}
