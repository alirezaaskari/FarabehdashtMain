<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Http\Controllers;

use App\Modules\ExamPrep\Actions\RecordAnswer;
use App\Modules\ExamPrep\Actions\StartAttempt;
use App\Modules\ExamPrep\Domain\Enums\AttemptMode;
use App\Modules\ExamPrep\Domain\ExamPack;
use App\Modules\ExamPrep\Domain\PackTopic;
use App\Modules\ExamPrep\Domain\PrepAttempt;
use App\Modules\ExamPrep\Domain\PrepQuestion;
use App\Modules\ExamPrep\Services\Scorecard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * نمونه و تمرین: یک سؤال در هر صفحه با پاسخ فوری. آزمون: همه سؤال‌ها در
 * یک فرم با زمان‌سنج، پاسخ‌ها پس از ارسال در کارنامه.
 */
final readonly class AttemptController
{
    public function __construct(
        private StartAttempt $starter,
        private RecordAnswer $recorder,
        private Scorecard $scorecard,
    ) {}

    public function start(Request $request, ExamPack $pack): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', Rule::enum(AttemptMode::class)],
            'topic' => ['nullable', 'integer'],
        ]);

        $topic = isset($data['topic'])
            ? PackTopic::query()->where('exam_pack_id', $pack->id)->find((int) $data['topic'])
            : null;

        try {
            $attempt = $this->starter->handle($pack, $this->userId($request), AttemptMode::from($data['mode']), $topic);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['start' => $exception->getMessage()]);
        }

        return redirect()->route('exam_prep.attempt', $attempt);
    }

    public function show(Request $request, PrepAttempt $attempt): View|RedirectResponse
    {
        $this->authorizeOwner($request, $attempt);
        $attempt->load(['pack', 'answers']);

        $answered = $request->session()->get('answered');

        if ($attempt->mode->givesInstantFeedback()) {
            if (is_int($answered)) {
                return $this->feedback($attempt, $answered);
            }

            $next = $attempt->nextQuestionId();

            if ($attempt->isSubmitted() || $next === null) {
                return redirect()->route('exam_prep.result', $attempt);
            }

            return view('exam_prep::attempt.question', [
                'attempt' => $attempt,
                'question' => PrepQuestion::query()->with(['choices', 'topic'])->findOrFail($next),
                'position' => $attempt->answers->count() + 1,
            ]);
        }

        if ($attempt->isSubmitted()) {
            return redirect()->route('exam_prep.result', $attempt);
        }

        // آزمونی که زمانش (با فرصت اضافه) گذشته و فرمش هرگز نرسیده، بسته می‌شود.
        $grace = (int) config('exam_prep.grace_seconds', 30);

        if ($attempt->deadline_at !== null && now()->greaterThan($attempt->deadline_at->copy()->addSeconds($grace))) {
            $this->recorder->all($attempt, []);

            return redirect()->route('exam_prep.result', $attempt)
                ->with('status', 'زمان این آزمون پیش‌تر تمام شده بود؛ بدون پاسخ ثبت شد.');
        }

        $questions = PrepQuestion::query()->with('choices')->whereIn('id', $attempt->question_ids)->get()->keyBy('id');

        return view('exam_prep::attempt.exam', [
            'attempt' => $attempt,
            'questions' => collect($attempt->question_ids)->map(static fn (int $id) => $questions->get($id))->filter()->values(),
            'secondsLeft' => max(0, (int) now()->diffInSeconds($attempt->deadline_at, false)),
        ]);
    }

    public function answer(Request $request, PrepAttempt $attempt): RedirectResponse
    {
        $this->authorizeOwner($request, $attempt);

        $data = $request->validate([
            'question' => ['required', 'integer'],
            'choice' => ['required', 'integer'],
        ], ['choice.required' => 'یکی از گزینه‌ها را انتخاب کنید.']);

        try {
            $this->recorder->one($attempt, (int) $data['question'], (int) $data['choice']);
        } catch (InvalidArgumentException $exception) {
            return redirect()->route('exam_prep.attempt', $attempt)->withErrors(['answer' => $exception->getMessage()]);
        }

        return redirect()->route('exam_prep.attempt', $attempt)->with('answered', (int) $data['question']);
    }

    public function submit(Request $request, PrepAttempt $attempt): RedirectResponse
    {
        $this->authorizeOwner($request, $attempt);

        $choices = $request->input('answers', []);

        try {
            $attempt = $this->recorder->all($attempt, is_array($choices) ? $choices : []);
        } catch (InvalidArgumentException $exception) {
            return redirect()->route('exam_prep.attempt', $attempt)->withErrors(['answer' => $exception->getMessage()]);
        }

        return redirect()->route('exam_prep.result', $attempt)->with('status', $attempt->late
            ? 'پاسخ‌ها پس از پایان زمان رسید؛ نمره ثبت شد ولی «پس از زمان» علامت خورد.'
            : 'آزمون ثبت شد.');
    }

    public function result(Request $request, PrepAttempt $attempt): View|RedirectResponse
    {
        $this->authorizeOwner($request, $attempt);

        if (! $attempt->isSubmitted()) {
            return redirect()->route('exam_prep.attempt', $attempt);
        }

        $attempt->load(['pack', 'topic']);

        return view('exam_prep::attempt.result', [
            'attempt' => $attempt,
            'card' => $this->scorecard->for($attempt),
        ]);
    }

    /** پاسخ فوری سؤالی که همین حالا جواب داده شد. */
    private function feedback(PrepAttempt $attempt, int $questionId): View
    {
        $question = PrepQuestion::query()->with(['choices', 'topic'])->findOrFail($questionId);

        return view('exam_prep::attempt.feedback', [
            'attempt' => $attempt,
            'question' => $question,
            'answer' => $attempt->answers->firstWhere('question_id', $questionId),
            'finished' => $attempt->isSubmitted(),
            'position' => $attempt->answers->count(),
        ]);
    }

    private function authorizeOwner(Request $request, PrepAttempt $attempt): void
    {
        abort_unless($attempt->user_id === $this->userId($request), 404);
    }

    private function userId(Request $request): int
    {
        return (int) $request->user()?->getKey();
    }
}
