<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Http\Controllers;

use App\Modules\ExamPrep\Domain\Enums\QuestionStatus;
use App\Modules\ExamPrep\Domain\ExamPack;
use App\Modules\ExamPrep\Domain\PrepAttempt;
use App\Modules\ExamPrep\Services\PackAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final readonly class PackController
{
    public function __construct(private PackAccess $access) {}

    public function index(): View
    {
        return view('exam_prep::index', [
            'packs' => ExamPack::query()
                ->published()
                ->withCount('publishedQuestions')
                ->orderBy('title')
                ->get(),
        ]);
    }

    /** بسته بازنشسته فقط برای خریدارش باز می‌ماند. */
    public function show(Request $request, ExamPack $pack): View
    {
        $userId = (int) $request->user()?->getKey();
        $owns = $userId > 0 && $this->access->owns($userId, $pack);

        abort_unless($pack->isPublished() || $owns, 404);

        $pack->load(['topics' => static fn ($query) => $query
            ->withCount(['questions as published_count' => static fn (Builder $q) => $q->where('status', QuestionStatus::Published)])
            ->orderBy('sort')]);

        return view('exam_prep::show', [
            'pack' => $pack,
            'owns' => $owns,
            'onSale' => $this->access->isOnSale($pack),
            'sampleCount' => min(
                $pack->publishedQuestions()->where('is_sample', true)->count(),
                (int) config('exam_prep.sample_size', 10),
            ),
            'questionCount' => $pack->publishedQuestions()->count(),
            'attempts' => $userId > 0
                ? PrepAttempt::query()->where('user_id', $userId)->where('exam_pack_id', $pack->id)
                    ->whereNotNull('submitted_at')->latest('submitted_at')->limit(5)->get()
                : collect(),
        ]);
    }
}
