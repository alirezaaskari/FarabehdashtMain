<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Http\Controllers;

use App\Modules\Commerce\Services\BuyerPurchases;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * «خریدهای من» در میزکار: همه فایل‌های خریده‌شده با پیوند دانلود تازه.
 *
 * بیرون از کلید فروش فایل است: خاموش‌بودن ویترین دسترسی خریدار را نمی‌بندد.
 */
final readonly class MyPurchasesController
{
    /** پیوند دانلود کوتاه‌عمر است؛ صفحه هر بار پیوند تازه می‌سازد. */
    private const LINK_MINUTES = 15;

    public function __construct(private BuyerPurchases $purchases) {}

    public function __invoke(Request $request): View
    {
        return view('commerce::purchases', [
            'items' => $this->purchases->for((int) $request->user()?->getKey()),
            'linkExpiry' => now()->addMinutes(self::LINK_MINUTES),
        ]);
    }
}
