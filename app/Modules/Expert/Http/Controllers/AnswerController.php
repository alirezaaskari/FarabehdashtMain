<?php

declare(strict_types=1);

namespace App\Modules\Expert\Http\Controllers;

use App\Models\User;
use App\Modules\Expert\Actions\SubmitAnswer;
use App\Modules\Expert\Domain\ExpertQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * فرستادن پاسخ مشاور. مسیر پشت `can:expert.answer` است و خود Action هم
 * دوباره می‌سنجد.
 */
final readonly class AnswerController
{
    public function store(Request $request, string $uuid, SubmitAnswer $submit): RedirectResponse
    {
        $limits = (array) config('expert.limits', []);

        $validated = $request->validate([
            'answer' => ['required', 'string', 'min:'.($limits['answer_min'] ?? 50), 'max:'.($limits['answer_max'] ?? 8000)],
        ]);

        $question = ExpertQuestion::query()->where('uuid', $uuid)->first()
            ?? throw new NotFoundHttpException('این پرسش پیدا نشد.');

        $user = $request->user();

        if (! $user instanceof User) {
            throw new NotFoundHttpException;
        }

        try {
            $submit->handle($user, $question, $validated['answer']);
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['answer' => $exception->getMessage()]);
        }

        return to_route('expert.show', $question->uuid)
            ->with('status', 'پاسخ شما فرستاده شد و پس از تأیید مدیر منتشر می‌شود.');
    }
}
