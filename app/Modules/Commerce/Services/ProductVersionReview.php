<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Services;

use App\Modules\Commerce\Domain\Enums\VersionReviewStatus;
use App\Modules\Commerce\Domain\Product;
use App\Modules\Commerce\Domain\ProductVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * نسخه‌های در انتظار تأیید یک محصول.
 *
 * محصول منتشرشده سر جایش می‌ماند و خریدار آخرین نسخه تأییدشده را می‌گیرد؛
 * نسخه تازه تا تصمیم مدیر به او نمی‌رسد. انتشار خود محصول همه را یک‌جا تأیید
 * می‌کند. نسخه ردشده برخلاف جلسه دوره پاک نمی‌شود: نسخه‌ها فقط افزودنی‌اند و
 * شماره نسخه ردشده دوباره قابل استفاده نیست.
 */
final readonly class ProductVersionReview
{
    public function hasPending(Product $product): bool
    {
        return $this->pending($product)->exists();
    }

    /** @return list<string> */
    public function pendingVersions(Product $product): array
    {
        return $this->pending($product)->oldest('id')->pluck('version')->values()->all();
    }

    public function approve(Product $product, ?Carbon $now = null): int
    {
        return $this->decide($product, VersionReviewStatus::Approved, null, $now);
    }

    public function reject(Product $product, string $note, ?Carbon $now = null): int
    {
        return $this->decide($product, VersionReviewStatus::Rejected, $note, $now);
    }

    private function decide(Product $product, VersionReviewStatus $status, ?string $note, ?Carbon $now): int
    {
        return $this->pending($product)->update([
            'review_status' => $status->value,
            'reviewed_at' => $now ?? Carbon::now(),
            'review_note' => $note,
        ]);
    }

    /** @return Builder<ProductVersion> */
    private function pending(Product $product): Builder
    {
        return ProductVersion::query()->where('product_id', $product->id)->pendingReview();
    }
}
