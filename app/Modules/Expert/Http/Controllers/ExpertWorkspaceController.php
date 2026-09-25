<?php

declare(strict_types=1);

namespace App\Modules\Expert\Http\Controllers;

use App\Modules\Expert\Domain\Enums\ReviewStatus;
use App\Modules\Expert\Domain\ExpertAnswer;
use App\Modules\Expert\Domain\ExpertQuestion;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * دو صفحه میزکار: «پرسش‌های من» برای هر کاربر و «پرسش‌های باز» برای مشاور.
 */
final readonly class ExpertWorkspaceController
{
    public function mine(Request $request): View
    {
        return view('expert::mine', [
            'questions' => ExpertQuestion::query()
                ->where('user_id', (int) $request->user()?->getKey())
                ->withCount(['answers as published_answers_count' => static fn (Builder $query) => $query->where('status', ReviewStatus::Published)])
                ->latest()
                ->paginate((int) config('expert.per_page', 20)),
        ]);
    }

    /**
     * پرسش‌های تأییدشده‌ای که این مشاور هنوز پاسخشان نداده؛ اول Pro، بعد
     * قدیمی‌تر (DEC-40). پرسش خود مشاور در صفش نمی‌آید.
     */
    public function queue(Request $request): View
    {
        $userId = (int) $request->user()?->getKey();

        return view('expert::queue', [
            'questions' => ExpertQuestion::query()
                ->where('status', ReviewStatus::Published)
                ->whereNull('accepted_answer_id')
                ->where('user_id', '!=', $userId)
                ->whereDoesntHave('answers', static fn (Builder $query) => $query->where('user_id', $userId))
                ->inQueueOrder()
                ->paginate((int) config('expert.per_page', 20)),
            'answers' => ExpertAnswer::query()
                ->where('user_id', $userId)
                ->with('question')
                ->latest('updated_at')
                ->limit(10)
                ->get(),
        ]);
    }
}
