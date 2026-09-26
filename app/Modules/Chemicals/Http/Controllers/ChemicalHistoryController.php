<?php

declare(strict_types=1);

namespace App\Modules\Chemicals\Http\Controllers;

use App\Modules\Chemicals\Domain\Substance;
use App\Modules\Chemicals\Services\SubstanceHistory;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * تاریخچه تغییرات یک ماده (بخش ۱۸-۱۱): چه عددی، کِی و چرا عوض شد.
 *
 * کارشناسی که حد مواجهه را در گزارش آورده باید بتواند ببیند عدد از آن روز
 * عوض شده یا نه. فقط ماده منتشرشده؛ پیش‌نویس ۴۰۴، مثل صفحه خود ماده.
 */
final readonly class ChemicalHistoryController
{
    public function __construct(private SubstanceHistory $history) {}

    public function __invoke(string $slug): View
    {
        $substance = Substance::query()->published()->where('slug', $slug)->first()
            ?? throw new NotFoundHttpException('این ماده پیدا نشد.');

        return view('chemicals::history', [
            'substance' => $substance,
            'entries' => $this->history->for($substance),
        ]);
    }
}
