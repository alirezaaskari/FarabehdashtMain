<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Http\Controllers;

use App\Modules\Commerce\Domain\Product;
use App\Modules\Commerce\Services\ProductAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * دانلود آخرین نسخه یک محصول.
 *
 * دو لایه محافظت: پیوند فقط با امضا و سقف زمانی معتبر است (میان‌افزار
 * `signed`)، و حتی با امضای معتبر، مالکیت خریدار **دوباره** همین لحظه
 * بررسی می‌شود — یک بازگشت کامل وجه بین ساخته‌شدن پیوند و کلیک روی آن
 * می‌تواند دسترسی را باطل کرده باشد.
 *
 * واترمارک محتوا (نشان کردن فایل با هویت خریدار) در این بخش ساخته نشده؛
 * دلیل در README ماژول آمده.
 */
final readonly class DownloadController
{
    public function __construct(private ProductAccess $access) {}

    public function __invoke(Request $request, Product $product): StreamedResponse
    {
        $userId = Auth::id();

        abort_if($userId === null, 403, 'برای دانلود باید وارد شوید.');
        abort_unless($this->access->userOwns((int) $userId, $product), 403, 'دسترسی دانلود این محصول را ندارید.');

        $version = $product->latestVersion();

        abort_if($version === null, 404, 'فایلی برای این محصول ثبت نشده است.');
        abort_unless(Storage::disk('local')->exists($version->file_path), 404, 'فایل روی سرور پیدا نشد.');

        $filename = $product->slug.'-'.$version->version.'.'.pathinfo($version->file_path, PATHINFO_EXTENSION);

        return Storage::disk('local')->download($version->file_path, $filename);
    }
}
