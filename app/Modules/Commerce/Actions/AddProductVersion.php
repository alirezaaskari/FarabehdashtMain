<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Actions;

use App\Modules\Commerce\Domain\Enums\ProductStatus;
use App\Modules\Commerce\Domain\Product;
use App\Modules\Commerce\Domain\ProductVersion;
use App\Modules\Commerce\Http\Controllers\DownloadController;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use RuntimeException;

/**
 * ثبت نسخه تازه یک محصول.
 *
 * فایل روی دیسک `local` (خارج از ریشه وب) ذخیره می‌شود؛ تنها راه رسیدن به
 * آن {@see DownloadController} است که مالکیت خریدار را بررسی می‌کند.
 *
 * نسخه تازه همیشه «در انتظار تأیید» ثبت می‌شود؛ روی محصول منتشرشده، خریدار
 * تا تأیید مدیر همان نسخه قبلی را می‌گیرد.
 */
final readonly class AddProductVersion
{
    public function handle(Product $product, string $version, ?string $changelog, UploadedFile $file): ProductVersion
    {
        if ($product->status === ProductStatus::Retired) {
            throw new RuntimeException('محصول بازنشسته‌شده نسخه تازه نمی‌پذیرد.');
        }

        if ($product->versions()->where('version', $version)->exists()) {
            throw new InvalidArgumentException("نسخه «{$version}» قبلاً برای این محصول ثبت شده است.");
        }

        $path = $file->storeAs(
            "products/{$product->id}",
            $version.'.'.($file->getClientOriginalExtension() ?: 'bin'),
            'local',
        );

        if ($path === false) {
            throw new RuntimeException('ذخیره فایل محصول ناموفق بود.');
        }

        $created = ProductVersion::query()->create([
            'product_id' => $product->id,
            'version' => $version,
            'changelog' => $changelog,
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()) ?: '',
        ]);

        // صف تأیید قدیمی‌ترین معطلی را اول می‌آورد و زمانش را از محصول می‌خواند.
        if ($product->status === ProductStatus::Published) {
            $product->touch();
        }

        return $created;
    }
}
