<?php

declare(strict_types=1);

namespace App\Modules\Expert\Filament\Pages;

use App\Modules\Expert\Actions\ReviewAnswer;
use App\Modules\Expert\Actions\ReviewQuestion;
use App\Modules\Expert\Admin\PendingExpertItems;
use App\Modules\Expert\Domain\Enums\ReviewStatus;
use App\Modules\Expert\Domain\ExpertAnswer;
use App\Modules\Expert\Domain\ExpertQuestion;
use App\Support\Admin\NavigationGroup;
use App\Support\JalaliDate;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use UnitEnum;

/**
 * صف تأیید پرسش از متخصص: پرسش‌ها و پاسخ‌های در انتظار.
 *
 * دکمه‌ها همان Actionها را صدا می‌زنند تا رویداد، اعلان و دفتر رویداد دور
 * زده نشود. نام پرسش‌کننده این‌جا هم نیست (DEC-41)؛ برای تصمیم لازم نیست.
 */
final class ExpertReviewPage extends Page
{
    protected static ?string $slug = 'expert-review';

    protected static ?int $navigationSort = 46;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Review;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected string $view = 'expert::filament.pages.expert-review';

    /** @var list<array{id: int, title: string, body: string, meta: string, priority: bool, url: string}> */
    public array $questions = [];

    /** @var list<array{id: int, question: string, body: string, meta: string, url: string}> */
    public array $answers = [];

    /** @var array<string, string> */
    public array $notes = [];

    public static function getNavigationLabel(): string
    {
        return 'پرسش از متخصص';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ExpertQuestion::query()->where('status', ReviewStatus::Pending)->count()
            + ExpertAnswer::query()->where('status', ReviewStatus::Pending)->count();

        return $count > 0 ? (string) $count : null;
    }

    public function getTitle(): string
    {
        return 'پرسش از متخصص — صف تأیید';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(PendingExpertItems::ABILITY) === true;
    }

    public function mount(): void
    {
        $this->load();
    }

    public function publishQuestion(int $id, ReviewQuestion $review): void
    {
        $this->decide(fn () => $review->publish($this->question($id), $this->adminId()), 'پرسش تأیید شد');
    }

    public function rejectQuestion(int $id, ReviewQuestion $review): void
    {
        $this->decide(fn () => $review->reject($this->question($id), $this->adminId(), $this->notes['q'.$id] ?? ''), 'پرسش رد شد');
    }

    public function publishAnswer(int $id, ReviewAnswer $review): void
    {
        $this->decide(fn () => $review->publish($this->answer($id), $this->adminId()), 'پاسخ منتشر شد');
    }

    public function rejectAnswer(int $id, ReviewAnswer $review): void
    {
        $this->decide(fn () => $review->reject($this->answer($id), $this->adminId(), $this->notes['a'.$id] ?? ''), 'پاسخ برای اصلاح برگشت');
    }

    private function decide(callable $action, string $done): void
    {
        try {
            $action();
        } catch (RuntimeException $exception) {
            Notification::make()->title('انجام نشد')->body($exception->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title($done)->success()->send();
        $this->load();
    }

    private function question(int $id): ExpertQuestion
    {
        return ExpertQuestion::query()->findOrFail($id);
    }

    private function answer(int $id): ExpertAnswer
    {
        return ExpertAnswer::query()->with('question')->findOrFail($id);
    }

    private function adminId(): int
    {
        return (int) Auth::id();
    }

    private function load(): void
    {
        $this->notes = [];

        $this->questions = ExpertQuestion::query()
            ->where('status', ReviewStatus::Pending)
            ->inQueueOrder()
            ->get()
            ->map(static fn (ExpertQuestion $question): array => [
                'id' => $question->id,
                'title' => $question->title,
                'body' => $question->body,
                'meta' => implode(' · ', [
                    $question->topic->label(),
                    $question->visibility->label(),
                    JalaliDate::long($question->created_at),
                ]),
                'priority' => $question->priority,
                'url' => route('expert.show', $question->uuid),
            ])
            ->values()
            ->all();

        $this->answers = ExpertAnswer::query()
            ->where('status', ReviewStatus::Pending)
            ->with(['question', 'answerer'])
            ->oldest('updated_at')
            ->get()
            ->map(static fn (ExpertAnswer $answer): array => [
                'id' => $answer->id,
                'question' => $answer->question->title,
                'body' => $answer->body,
                'meta' => $answer->answererName().' (کاربر #'.$answer->user_id.') · '.JalaliDate::long($answer->updated_at),
                'url' => route('expert.show', $answer->question->uuid),
            ])
            ->values()
            ->all();
    }
}
