<?php

declare(strict_types=1);

namespace App\Modules\Expert\Actions;

use App\Modules\Expert\Domain\Enums\QuestionVisibility;
use App\Modules\Expert\Domain\Enums\ReviewStatus;
use App\Modules\Expert\Domain\ExpertAnswer;
use App\Modules\Expert\Domain\ExpertQuestion;
use App\Modules\Expert\Editorial\EditorialEntry;
use App\Modules\Expert\Editorial\EditorialQuestions;
use App\Modules\Expert\Events\EditorialQuestionsSynced;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use Ramsey\Uuid\Uuid;

/**
 * پرسش و پاسخ‌های نمونه تحریریه را با متن `EditorialQuestions` همگام می‌کند.
 *
 * منبع حقیقت کد است: ردیف تازه ساخته و متن تغییرکرده به‌روز می‌شود، پس
 * اصلاح یک عدد در کد با استقرار بعدی به سایت می‌رسد. شناسه هر ردیف از کلید
 * ثابتش ساخته می‌شود (UUID نسخه ۵) تا نشانی صفحه هیچ‌وقت عوض نشود.
 */
final readonly class SyncEditorialQuestions
{
    private const NAMESPACE = 'farabehdasht:expert:editorial:';

    public function __construct(
        private EditorialQuestions $content,
        private ConnectionInterface $db,
        private Dispatcher $events,
    ) {}

    /**
     * @return array{created: int, updated: int}
     */
    public function handle(): array
    {
        $created = 0;
        $updated = 0;
        $now = Carbon::now();

        $this->db->transaction(function () use (&$created, &$updated, $now): void {
            foreach ($this->content->entries() as $position => $entry) {
                // ترتیب فهرست عمومی همان ترتیب کد است: اولی تازه‌ترین.
                $publishedAt = $now->copy()->subMinutes($position);

                $question = $this->syncQuestion($entry, $publishedAt, $created, $updated);
                $this->syncAnswer($question, $entry, $publishedAt, $created, $updated);
            }
        });

        if ($created + $updated > 0) {
            $this->events->dispatch(new EditorialQuestionsSynced($created, $updated));
        }

        return ['created' => $created, 'updated' => $updated];
    }

    public static function questionUuid(string $key): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, self::NAMESPACE.$key)->toString();
    }

    public static function answerUuid(string $key): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, self::NAMESPACE.$key.'#answer')->toString();
    }

    private function syncQuestion(EditorialEntry $entry, Carbon $publishedAt, int &$created, int &$updated): ExpertQuestion
    {
        $question = ExpertQuestion::query()->firstOrNew(['uuid' => self::questionUuid($entry->key)]);

        $question->fill([
            'user_id' => null,
            'topic' => $entry->topic,
            'visibility' => QuestionVisibility::Public,
            'title' => $entry->title,
            'body' => $entry->question,
            'status' => ReviewStatus::Published,
        ]);

        $this->count($question, $created, $updated);

        if (! $question->exists) {
            $question->published_at = $publishedAt;
        }

        $question->save();

        return $question;
    }

    private function syncAnswer(ExpertQuestion $question, EditorialEntry $entry, Carbon $publishedAt, int &$created, int &$updated): void
    {
        $answer = ExpertAnswer::query()->firstOrNew(['uuid' => self::answerUuid($entry->key)]);

        $answer->fill([
            'question_id' => $question->id,
            'user_id' => null,
            'body' => $entry->answerBody(),
            'status' => ReviewStatus::Published,
        ]);

        $this->count($answer, $created, $updated);

        if (! $answer->exists) {
            $answer->published_at = $publishedAt;
        }

        $answer->save();
    }

    private function count(ExpertQuestion|ExpertAnswer $model, int &$created, int &$updated): void
    {
        if (! $model->exists) {
            $created++;
        } elseif ($model->isDirty()) {
            $updated++;
        }
    }
}
