<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Http\Controllers;

use App\Modules\Chemicals\Domain\Enums\LimitAuthority;
use App\Modules\Chemicals\Domain\ExposureLimit;
use App\Modules\Chemicals\Domain\Substance;
use Illuminate\Contracts\View\View;

/**
 * «منابع و روش کار» بانک مواد: از کجا و چگونه، تا کاربر بتواند به عددها اعتماد کند.
 *
 * شمارش‌ها زنده‌اند و فقط از مواد منتشرشده؛ متن مرجع‌ها ثابت است و در قالب.
 */
final readonly class ChemicalSourcesController
{
    public function __invoke(): View
    {
        $counts = ExposureLimit::query()
            ->whereIn('substance_id', Substance::query()->published()->select('id'))
            ->selectRaw('authority, count(*) as total')
            ->groupBy('authority')
            ->pluck('total', 'authority');

        $limitsBy = [];

        foreach (LimitAuthority::cases() as $authority) {
            $limitsBy[$authority->value] = (int) ($counts[$authority->value] ?? 0);
        }

        return view('chemicals::sources', [
            'substances' => Substance::query()->published()->count(),
            'limitsBy' => $limitsBy,
        ]);
    }
}
