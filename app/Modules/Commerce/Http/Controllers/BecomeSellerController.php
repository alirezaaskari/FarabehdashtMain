<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Http\Controllers;

use App\Modules\Commerce\Services\CommissionService;
use App\Modules\Commerce\Services\Payouts;
use Illuminate\Contracts\View\View;

/**
 * صفحه عمومی «فروشنده شوید» (بخش ۱۸-۶): چه چیزی، با چه سهمی و در چند قدم.
 *
 * درخواست نقش همان صف تأیید «نقش‌ها و پروفایل‌ها» است؛ این صفحه فقط راه را
 * نشان می‌دهد و عددها را از همان منبعی می‌خواند که فروش و تسویه می‌خوانند.
 */
final readonly class BecomeSellerController
{
    public function __construct(
        private CommissionService $commission,
        private Payouts $payouts,
    ) {}

    public function __invoke(): View
    {
        return view('commerce::become-seller', [
            'shopRate' => intdiv($this->commission->currentRateBp('shop'), 100),
            'courseRate' => intdiv($this->commission->currentRateBp('course'), 100),
            'minimum' => $this->payouts->minimum(),
        ]);
    }
}
