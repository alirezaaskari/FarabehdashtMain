<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Http\Controllers;

use App\Models\User;
use App\Modules\Commerce\Domain\Product;
use App\Modules\Commerce\Services\PdfStamp;
use App\Modules\Commerce\Services\ProductAccess;
use App\Support\JalaliDate;
use App\Support\Mobile;
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
 * فایل PDF با نشان خریدار (موبایل پوشیده و تاریخ) دانلود می‌شود (بخش ۱۸-۱۱)؛
 * قالب‌های دیگر و PDF که نشان نمی‌پذیرد همان فایل اصلی‌اند.
 */
final readonly class DownloadController
{
    public function __construct(
        private ProductAccess $access,
        private PdfStamp $stamp,
    ) {}

    public function __invoke(Request $request, Product $product): StreamedResponse
    {
        $userId = Auth::id();

        abort_if($userId === null, 403, 'برای دانلود باید وارد شوید.');
        abort_unless($this->access->userOwns((int) $userId, $product), 403, 'دسترسی دانلود این محصول را ندارید.');

        $version = $product->latestVersion();

        abort_if($version === null, 404, 'فایلی برای این محصول ثبت نشده است.');
        abort_unless(Storage::disk('local')->exists($version->file_path), 404, 'فایل روی سرور پیدا نشد.');

        $filename = $product->slug.'-'.$version->version.'.'.pathinfo($version->file_path, PATHINFO_EXTENSION);
        $stamped = $this->stamp->stamp(Storage::disk('local')->path($version->file_path), $this->buyer($request), JalaliDate::short(now()));

        if ($stamped !== null) {
            return response()->streamDownload(static function () use ($stamped): void {
                echo $stamped;
            }, $filename, ['Content-Type' => 'application/pdf']);
        }

        return Storage::disk('local')->download($version->file_path, $filename);
    }

    private function buyer(Request $request): string
    {
        $user = $request->user();

        return ($user instanceof User ? Mobile::tryFromInput($user->mobile)?->masked() : null) ?? '';
    }
}
