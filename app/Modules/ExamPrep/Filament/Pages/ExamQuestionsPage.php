<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Filament\Pages;

use App\Modules\ExamPrep\Actions\ReviewQuestion;
use App\Modules\ExamPrep\Admin\PendingPrepQuestions;
use App\Modules\ExamPrep\Domain\Enums\QuestionStatus;
use App\Modules\ExamPrep\Domain\PrepChoice;
use App\Modules\ExamPrep\Domain\PrepQuestion;
use App\Support\Admin\NavigationGroup;
use App\Support\JalaliDate;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use RuntimeException;
use UnitEnum;

/** صف تأیید سؤال‌هایی که مدرسان برای بسته‌های آزمون نوشته‌اند. */
final class ExamQuestionsPage extends Page
{
    protected static ?string $slug = 'exam-questions';

    protected static ?int $navigationSort = 47;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Review;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected string $view = 'exam_prep::filament.pages.exam-questions';

    /** @var list<array{id: int, body: string, meta: string, choices: list<array{body: string, correct: bool}>, explanation: string|null, reference: string|null}> */
    public array $questions = [];

    /** @var array<int, string> */
    public array $notes = [];

    public static function getNavigationLabel(): string
    {
        return 'سؤال‌های آزمون';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = PrepQuestion::query()->where('status', QuestionStatus::Pending)->count();

        return $count > 0 ? (string) $count : null;
    }

    public function getTitle(): string
    {
        return 'سؤال‌های آزمون — صف تأیید';
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->can(PendingPrepQuestions::ABILITY) === true;
    }

    public function mount(): void
    {
        $this->load();
    }

    public function publish(int $id, ReviewQuestion $review): void
    {
        $this->decide(fn () => $review->publish($this->question($id), (int) Auth::id()), 'سؤال منتشر شد');
    }

    public function reject(int $id, ReviewQuestion $review): void
    {
        $this->decide(fn () => $review->reject($this->question($id), $this->notes[$id] ?? '', (int) Auth::id()), 'سؤال برگشت خورد');
    }

    private function decide(callable $action, string $done): void
    {
        try {
            $action();
        } catch (InvalidArgumentException|RuntimeException $exception) {
            Notification::make()->title('انجام نشد')->body($exception->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title($done)->success()->send();
        $this->load();
    }

    private function question(int $id): PrepQuestion
    {
        return PrepQuestion::query()->findOrFail($id);
    }

    private function load(): void
    {
        $this->notes = [];

        $this->questions = PrepQuestion::query()
            ->where('status', QuestionStatus::Pending)
            ->with(['pack', 'topic', 'choices', 'author:id,name'])
            ->oldest()
            ->get()
            ->map(static fn (PrepQuestion $question): array => [
                'id' => $question->id,
                'body' => $question->body,
                'meta' => implode(' · ', array_filter([
                    $question->pack->title,
                    $question->topic->title,
                    $question->difficulty->label(),
                    $question->is_sample ? 'نمونه رایگان' : null,
                    $question->author !== null ? ($question->author->name ?: 'مدرس').' (کاربر #'.$question->author_user_id.')' : null,
                    JalaliDate::long($question->created_at),
                ])),
                'choices' => $question->choices->sortBy('sort')->map(static fn (PrepChoice $choice): array => [
                    'body' => $choice->body,
                    'correct' => $choice->is_correct,
                ])->values()->all(),
                'explanation' => $question->explanation,
                'reference' => $question->reference_path,
            ])
            ->values()
            ->all();
    }
}
