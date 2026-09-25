<?php

declare(strict_types=1);

namespace App\Modules\Expert\Http\Controllers;

use App\Contracts\InternalLinker;
use App\Models\User;
use App\Modules\Expert\Actions\AcceptAnswer;
use App\Modules\Expert\Actions\AskQuestion;
use App\Modules\Expert\Domain\Enums\QuestionTopic;
use App\Modules\Expert\Domain\Enums\QuestionVisibility;
use App\Modules\Expert\Domain\Enums\ReviewStatus;
use App\Modules\Expert\Domain\ExpertAnswer;
use App\Modules\Expert\Domain\ExpertQuestion;
use App\Modules\Expert\Linking\QuestionDocuments;
use App\Modules\Expert\Services\QuestionAccess;
use App\Support\Linking\LinkSegment;
use App\Support\Seo\Schema;
use App\Support\Seo\SeoMeta;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * پرسش از متخصص: فهرست عمومی، پرسیدن، صفحه هر پرسش و انتخاب بهترین پاسخ.
 */
final readonly class QuestionController
{
    public function __construct(
        private QuestionAccess $access,
        private InternalLinker $linker,
    ) {}

    public function index(Request $request): View
    {
        $topic = QuestionTopic::tryFrom((string) $request->query('topic'));

        $questions = ExpertQuestion::query()
            ->listed()
            ->when($topic, static fn (Builder $query, QuestionTopic $topic) => $query->where('topic', $topic))
            ->withCount(['answers as published_answers_count' => static fn (Builder $query) => $query->where('status', ReviewStatus::Published)])
            ->latest('published_at')
            ->paginate((int) config('expert.per_page', 20))
            ->withQueryString();

        return view('expert::index', [
            'questions' => $questions,
            'topics' => QuestionTopic::cases(),
            'topic' => $topic,
        ]);
    }

    public function create(): View
    {
        return view('expert::create', [
            'topics' => QuestionTopic::cases(),
            'visibilities' => QuestionVisibility::cases(),
            'limits' => (array) config('expert.limits', []),
        ]);
    }

    public function store(Request $request, AskQuestion $ask): RedirectResponse
    {
        $limits = (array) config('expert.limits', []);

        $validated = $request->validate([
            'topic' => ['required', Rule::enum(QuestionTopic::class)],
            'visibility' => ['required', Rule::enum(QuestionVisibility::class)],
            'title' => ['required', 'string', 'min:'.($limits['title_min'] ?? 10), 'max:'.($limits['title_max'] ?? 200)],
            'body' => ['required', 'string', 'min:'.($limits['body_min'] ?? 30), 'max:'.($limits['body_max'] ?? 5000)],
        ]);

        $question = $ask->handle(
            $this->user($request),
            QuestionTopic::from($validated['topic']),
            QuestionVisibility::from($validated['visibility']),
            $validated['title'],
            $validated['body'],
        );

        return to_route('expert.show', $question->uuid)
            ->with('status', 'پرسش شما ثبت شد و پس از تأیید مدیر به دست مشاوران می‌رسد.');
    }

    public function show(Request $request, string $uuid): View
    {
        $viewer = $request->user();
        $viewer = $viewer instanceof User ? $viewer : null;
        $question = $this->find($uuid);

        if (! $this->access->canView($question, $viewer)) {
            throw new NotFoundHttpException('این پرسش پیدا نشد.');
        }

        $answers = $question->answers()
            ->published()
            ->with('answerer')
            ->orderByRaw('id = ? desc', [$question->accepted_answer_id ?? 0])
            ->oldest('published_at')
            ->get();

        $ownAnswer = $viewer === null ? null : $question->answers()->where('user_id', $viewer->getKey())->first();

        [$questionBody, $answerBodies] = $this->linkedBodies($question, $answers);

        return view('expert::show', [
            'question' => $question,
            'questionBody' => $questionBody,
            'answers' => $answers,
            'answerBodies' => $answerBodies,
            'ownAnswer' => $ownAnswer,
            'isAsker' => $viewer !== null && $question->user_id === $viewer->getKey(),
            'canAnswer' => $viewer !== null
                && $question->status === ReviewStatus::Published
                && $question->user_id !== $viewer->getKey()
                && $this->access->isAnswerer($viewer)
                && ($ownAnswer === null || $ownAnswer->status === ReviewStatus::Rejected),
            'limits' => (array) config('expert.limits', []),
            'seo' => $this->seo($question, $answers),
        ]);
    }

    public function accept(Request $request, string $uuid, string $answer, AcceptAnswer $accept): RedirectResponse
    {
        $question = $this->find($uuid);
        $chosen = $question->answers()->where('uuid', $answer)->first()
            ?? throw new NotFoundHttpException('این پاسخ پیدا نشد.');

        try {
            $accept->handle($this->user($request), $question, $chosen);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['accept' => $exception->getMessage()]);
        }

        return to_route('expert.show', $question->uuid)->with('status', 'بهترین پاسخ علامت خورد.');
    }

    private function find(string $uuid): ExpertQuestion
    {
        return ExpertQuestion::query()->where('uuid', $uuid)->first()
            ?? throw new NotFoundHttpException('این پرسش پیدا نشد.');
    }

    /**
     * بندهای پرسش و پاسخ‌ها. فقط پرسش عمومی از موتور پیوند می‌گذرد؛ متن
     * خصوصی در برنامه پیوند ذخیره نمی‌شود.
     *
     * @param  Collection<int, ExpertAnswer>  $answers
     * @return array{0: list<list<LinkSegment>>, 1: array<int, list<list<LinkSegment>>>}
     */
    private function linkedBodies(ExpertQuestion $question, Collection $answers): array
    {
        $paragraphs = [$question->paragraphs()];

        foreach ($answers as $answer) {
            $paragraphs[$answer->id] = $answer->paragraphs();
        }

        $flat = array_merge(...array_values($paragraphs));
        $linked = $question->isPublic()
            ? $this->linker->link(QuestionDocuments::key($question), $flat)
            : array_map(static fn (string $text): array => [LinkSegment::text($text)], $flat);

        $offset = 0;
        $bodies = [];

        foreach ($paragraphs as $key => $group) {
            $bodies[$key] = array_slice($linked, $offset, count($group));
            $offset += count($group);
        }

        $questionBody = $bodies[0];
        unset($bodies[0]);

        return [$questionBody, $bodies];
    }

    /** @param  Collection<int, ExpertAnswer>  $answers */
    private function seo(ExpertQuestion $question, Collection $answers): SeoMeta
    {
        $url = route('expert.show', $question->uuid);
        $meta = new SeoMeta(title: $question->title, description: Str::limit($question->body, 155), canonical: $url);

        if (! $question->isPublic()) {
            return $meta->noindexed();
        }

        // Google صفحه پرسش بی‌پاسخ را QAPage نمی‌پذیرد؛ تا پاسخ نیامده، فقط متا.
        if ($answers->isEmpty()) {
            return $meta;
        }

        return $meta->withSchema(Schema::qaPage(
            $question->title,
            $question->body,
            $url,
            $question->published_at ?? $question->created_at,
            $answers->map(static fn (ExpertAnswer $answer): array => [
                'text' => $answer->body,
                'url' => $url.'#answer-'.$answer->uuid,
                'author' => $answer->answererName(),
                'date' => $answer->published_at ?? $answer->created_at,
                'accepted' => $answer->id === $question->accepted_answer_id,
            ])->values()->all(),
        ));
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : throw new NotFoundHttpException;
    }
}
