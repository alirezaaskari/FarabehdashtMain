<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Http\Controllers;

use App\Modules\ExamPrep\Actions\AddQuestion;
use App\Modules\ExamPrep\Domain\Enums\Difficulty;
use App\Modules\ExamPrep\Domain\Enums\PackStatus;
use App\Modules\ExamPrep\Domain\ExamPack;
use App\Modules\ExamPrep\Domain\PackTopic;
use App\Modules\ExamPrep\Domain\PrepQuestion;
use App\Modules\ExamPrep\Services\QuestionDraft;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * نوشتن سؤال برای مدرسان تأییدشده. سؤال پس از تأیید مدیر منتشر می‌شود و
 * نویسنده نتیجه را با اعلان می‌گیرد.
 */
final readonly class QuestionWriterController
{
    public function __construct(private AddQuestion $add) {}

    public function index(Request $request): View
    {
        return view('exam_prep::writer.index', [
            'questions' => PrepQuestion::query()
                ->where('author_user_id', (int) $request->user()?->getKey())
                ->with(['pack', 'topic'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('exam_prep::writer.create', [
            'packs' => ExamPack::query()->where('status', '!=', PackStatus::Retired)->with('topics')->orderBy('title')->get(),
            'difficulties' => Difficulty::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'topic' => ['required', 'integer'],
            'difficulty' => ['required', Rule::enum(Difficulty::class)],
            'body' => ['required', 'string', 'max:4000'],
            'choices' => ['required', 'array', 'max:5'],
            'choices.*' => ['nullable', 'string', 'max:1000'],
            'correct' => ['required', 'integer', 'between:1,5'],
            'explanation' => ['nullable', 'string', 'max:4000'],
            'reference_label' => ['nullable', 'string', 'max:120'],
            'reference_path' => ['nullable', 'string', 'max:500'],
        ], ['correct.required' => 'گزینه درست را مشخص کنید.']);

        $topic = PackTopic::query()->with('pack')->findOrFail((int) $data['topic']);

        try {
            $draft = QuestionDraft::make(
                topic: $topic->title,
                difficulty: $data['difficulty'],
                body: $data['body'],
                choices: array_values($data['choices']),
                correct: (int) $data['correct'],
                explanation: $data['explanation'] ?? null,
                referenceLabel: $data['reference_label'] ?? null,
                referencePath: $data['reference_path'] ?? null,
            );

            $this->add->handle($topic->pack, $draft, (int) $request->user()?->getKey(), publish: false);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['question' => $exception->getMessage()]);
        }

        return redirect()->route('exam_prep.writer.index')
            ->with('status', 'سؤال ثبت شد و پس از تأیید مدیر در بسته منتشر می‌شود.');
    }
}
