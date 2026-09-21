<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Actions;

use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Product;
use RuntimeException;

/**
 * فرستادن محصول برای بررسی مدیر.
 *
 * فقط پیش‌نویس یا محصول رد‌شده قابل ارسال است — محصول منتشرشده یا در حال
 * بررسی، دوباره فرستاده نمی‌شود.
 */
final readonly class SubmitProductForReview
{
    public function handle(Product $product): Product
    {
        if (! in_array($product->status, [ProductStatus::Draft, ProductStatus::Rejected], true)) {
            throw new RuntimeException('فقط محصول پیش‌نویس یا رد‌شده برای بررسی فرستاده می‌شود.');
        }

        if ($product->price_toman <= 0) {
            throw new RuntimeException('محصول بدون قیمت معتبر برای بررسی فرستاده نمی‌شود.');
        }

        if ($product->versions->isEmpty()) {
            throw new RuntimeException('محصولی بدون هیچ فایلی، ارزش بررسی ندارد؛ اول یک نسخه اضافه کنید.');
        }

        $product->forceFill(['status' => ProductStatus::InReview, 'review_note' => null])->save();

        return $product->refresh();
    }
}
