<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Http\Controllers;

use App\Models\User;
use App\Modules\Encyclopedia\Actions\SubmitArticleForReview;
use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use App\Modules\Encyclopedia\Domain\Enums\ArticleType;
use App\Modules\Encyclopedia\Writing\DraftText;
use App\Modules\Encyclopedia\Writing\SaveWriterDraft;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * «نوشته‌های من» نویسنده دانشنامه: پیش‌نویس، ارسال برای بازبینی، دیدن
 * یادداشت مدیر. انتشار فقط از پنل و فقط با مدیر است.
 */
final readonly class WriterArticleController
{
    public function __construct(private SaveWriterDraft $save) {}

    public function index(Request $request): View
    {
        return view('encyclopedia::writing.index', [
            'articles' => Article::query()
                ->where('author_id', $this->user($request)->getKey())
                ->latest('updated_at')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('encyclopedia::writing.form', ['article' => null, ...$this->formData(null)]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $article = $this->save->handle($this->user($request), null, $this->validated($request));
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['body' => $exception->getMessage()]);
        }

        return to_route('encyclopedia.writing.edit', $article->uuid)->with('status', 'پیش‌نویس ذخیره شد.');
    }

    public function edit(Request $request, string $uuid): View
    {
        $article = $this->find($request, $uuid);

        return view('encyclopedia::writing.form', ['article' => $article, ...$this->formData($article)]);
    }

    public function update(Request $request, string $uuid): RedirectResponse
    {
        $article = $this->find($request, $uuid);

        try {
            $this->save->handle($this->user($request), $article, $this->validated($request));
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['body' => $exception->getMessage()]);
        }

        return back()->with('status', 'پیش‌نویس ذخیره شد.');
    }

    public function submit(Request $request, string $uuid, SubmitArticleForReview $submit): RedirectResponse
    {
        $article = $this->find($request, $uuid);

        if (! SaveWriterDraft::canEdit($this->user($request), $article)) {
            return back()->withErrors(['body' => 'این نوشته پیش‌تر فرستاده شده است.']);
        }

        try {
            $submit->handle($article, (int) $this->user($request)->getKey());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['body' => $exception->getMessage()]);
        }

        return to_route('encyclopedia.writing.index')
            ->with('status', 'برای بازبینی فرستاده شد. پس از تأیید مدیر منتشر می‌شود و خبرتان می‌کنیم.');
    }

    /** @return array{title: string, type: string, summary: string, body: string, references: string|null} */
    private function validated(Request $request): array
    {
        /** @var array{title: string, type: string, summary: string, body: string, references: string|null} */
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(ArticleType::class)],
            'summary' => ['required', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:60000'],
            'references' => ['nullable', 'string', 'max:10000'],
        ], attributes: [
            'title' => 'عنوان',
            'type' => 'نوع محتوا',
            'summary' => 'خلاصه',
            'body' => 'متن',
            'references' => 'منابع',
        ]);
    }

    /** @return array<string, mixed> */
    private function formData(?Article $article): array
    {
        return [
            'types' => ArticleType::cases(),
            'body' => $article === null ? '' : DraftText::fromSections($article->sections),
            'references' => $article === null ? '' : DraftText::fromReferences($article->references),
            'editable' => $article === null || $article->status === ArticleStatus::Draft,
        ];
    }

    private function find(Request $request, string $uuid): Article
    {
        $article = Article::query()->where('uuid', $uuid)->first();

        // نوشته دیگران «۴۰۴» است، نه «۴۰۳».
        if ($article === null || $article->author_id !== $this->user($request)->getKey()) {
            throw new NotFoundHttpException('این نوشته پیدا نشد.');
        }

        return $article;
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        assert($user instanceof User);

        return $user;
    }
}
