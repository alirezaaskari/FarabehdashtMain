<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Actions;

use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Enums\VersionReviewStatus;
use App\Modules\Commerce\Domain\Product;
use App\Modules\Commerce\Domain\ProductVersion;
use App\Modules\Commerce\Events\ProductPublished;
use App\Modules\Commerce\Services\ProductVersionReview;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * انتشار یک محصول — تنها دری که وضعیت به «منتشرشده» می‌رسد.
 *
 * قاعده‌ها دوباره این‌جا بررسی می‌شوند، نه فقط در {@see SubmitProductForReview}:
 * بین ارسال برای بررسی و تصمیم مدیر ممکن است فروشنده قیمت را صفر کرده یا
 * نسخه‌اش را حذف کرده باشد (اگر روزی حذف نسخه اضافه شود).
 */
final readonly class PublishProduct
{
    public function __construct(
        private Dispatcher $events,
        private ProductVersionReview $versions,
    ) {}

    public function handle(Product $product, ?int $actorId = null, ?Carbon $now = null): Product
    {
        $this->guard($product);

        $now ??= Carbon::now();

        $product->forceFill([
            'status' => ProductStatus::Published,
            'reviewed_at' => $now,
            'reviewed_by' => $actorId,
        ])->save();

        // تصمیم انتشار همان تصمیم درباره فایل‌هایی است که مدیر همراه محصول دید.
        $this->versions->approve($product, $now);

        $published = $product->refresh();

        $this->events->dispatch(new ProductPublished($published, $actorId));

        return $published;
    }

    private function guard(Product $product): void
    {
        if ($product->status !== ProductStatus::InReview) {
            throw new RuntimeException('فقط محصول در انتظار بررسی منتشر می‌شود.');
        }

        if ($product->price_toman <= 0) {
            throw new RuntimeException('محصول بدون قیمت معتبر منتشر نمی‌شود.');
        }

        if ($product->versions->every(static fn (ProductVersion $version): bool => $version->review_status === VersionReviewStatus::Rejected)) {
            throw new RuntimeException('محصول بدون هیچ فایلی منتشر نمی‌شود.');
        }
    }
}
