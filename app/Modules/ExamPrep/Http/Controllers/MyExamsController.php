<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Http\Controllers;

use App\Modules\ExamPrep\Domain\PackPurchase;
use App\Modules\ExamPrep\Domain\PrepAttempt;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** «آزمون‌های من» در میزکار: بسته‌های خریده و دورهای اخیر. */
final readonly class MyExamsController
{
    public function index(Request $request): View
    {
        $userId = (int) $request->user()?->getKey();

        return view('exam_prep::mine', [
            'purchases' => PackPurchase::query()->paid()->where('user_id', $userId)->with('pack')->latest('paid_at')->get(),
            'attempts' => PrepAttempt::query()->where('user_id', $userId)->with('pack')->latest()->limit(20)->get(),
        ]);
    }
}
