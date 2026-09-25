<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Filament\Resources\Articles\Pages;

use App\Modules\Encyclopedia\Actions\PublishArticle;
use App\Modules\Encyclopedia\Actions\ReturnArticleToWriter;
use App\Modules\Encyclopedia\Actions\SaveArticle;
use App\Modules\Encyclopedia\Actions\SubmitArticleForReview;
use App\Modules\Encyclopedia\Domain\Article;
use App\Modules\Encyclopedia\Domain\ArticleReference;
use App\Modules\Encyclopedia\Domain\ArticleSection;
use App\Modules\Encyclopedia\Domain\Enums\ArticleStatus;
use App\Modules\Encyclopedia\Filament\Resources\Articles\ArticleResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use RuntimeException;

final class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $article = $this->article()->load(['sections', 'references']);

        $data['reviewed_at'] = $article->reviewed_at?->toDateString();
        $data['sections'] = $article->sections->map(static fn (ArticleSection $section): array => [
            'heading' => $section->heading,
            'body' => $section->body,
            'note' => $section->note,
            'tool_slug' => $section->tool_slug,
        ])->all();
        $data['references'] = $article->references->map(static fn (ArticleReference $reference): array => [
            'title' => $reference->title,
            'publisher' => $reference->publisher,
            'edition' => $reference->edition,
            'year' => $reference->year,
            'url' => $reference->url,
        ])->all();

        return $data;
    }

    /** @param  array<string, mixed>  $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        assert($record instanceof Article);

        return app(SaveArticle::class)->handle($record, $data, $this->actorId());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label('دیدن در سایت')
                ->color('gray')
                ->url(fn (): string => route('encyclopedia.show', $this->article()->slug), shouldOpenInNewTab: true)
                ->visible(fn (): bool => $this->article()->status === ArticleStatus::Published && Route::has('encyclopedia.show')),

            Action::make('submit')
                ->label('ارسال برای بازبینی')
                ->color('gray')
                ->visible(fn (): bool => $this->article()->status === ArticleStatus::Draft)
                ->action(fn (SubmitArticleForReview $submit) => $this->attempt(
                    fn () => $submit->handle($this->article(), $this->actorId()),
                    'برای بازبینی فرستاده شد',
                )),

            // پیش‌نویس نویسنده که هنوز آماده نیست، با یادداشت به خودش برمی‌گردد.
            Action::make('return')
                ->label('بازگرداندن به نویسنده')
                ->color('warning')
                ->visible(fn (): bool => $this->article()->status === ArticleStatus::InReview
                    && $this->article()->author_id !== null
                    && $this->article()->author_id !== $this->actorId())
                ->schema([
                    Textarea::make('note')
                        ->label('چه چیزی باید اصلاح شود؟')
                        ->helperText('نویسنده همین متن را در اعلان و بالای فرم ویرایش می‌بیند.')
                        ->required()
                        ->rows(4),
                ])
                ->action(fn (array $data, ReturnArticleToWriter $return) => $this->attempt(
                    fn () => $return->handle($this->article(), (string) ($data['note'] ?? ''), $this->actorId()),
                    'به نویسنده برگشت',
                )),

            Action::make('publish')
                ->label('انتشار')
                ->requiresConfirmation()
                ->modalDescription('نسخه ذخیره‌شده منتشر می‌شود؛ اگر تغییری ذخیره نکرده‌اید، اول ذخیره کنید.')
                ->visible(fn (): bool => $this->article()->status !== ArticleStatus::Published)
                ->action(fn (PublishArticle $publish) => $this->attempt(
                    fn () => $publish->handle($this->article(), $this->actorId()),
                    'منتشر شد',
                )),
        ];
    }

    /** @param  callable(): Article  $action */
    private function attempt(callable $action, string $success): void
    {
        try {
            $this->record = $action();

            Notification::make()->title($success)->success()->send();
        } catch (RuntimeException $exception) {
            Notification::make()->title('انجام نشد')->body($exception->getMessage())->danger()->send();
        }

        $this->fillForm();
    }

    private function article(): Article
    {
        $record = $this->getRecord();
        assert($record instanceof Article);

        return $record;
    }

    private function actorId(): ?int
    {
        $id = Auth::id();

        return is_int($id) ? $id : null;
    }
}
