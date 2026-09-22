<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Actions;

use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Product;
use RuntimeException;

/**
 * خروج محصول از فروش.
 *
 * تصمیم فروشنده است، نه مدیر — برخلاف انتشار، بازنشسته‌کردن محصول خودش
 * نیازی به تأیید ندارد. خریداران قبلی دسترسی دانلودشان را نگه می‌دارند؛
 * این اکشن فقط `purchasable()` را خاموش می‌کند.
 */
final readonly class RetireProduct
{
    public function handle(Product $product): Product
    {
        if ($product->status !== ProductStatus::Published) {
            throw new RuntimeException('فقط محصول منتشرشده بازنشسته می‌شود.');
        }

        $product->forceFill(['status' => ProductStatus::Retired])->save();

        return $product->refresh();
    }
}
