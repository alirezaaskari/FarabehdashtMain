<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Actions;

use App\Modules\ExamPrep\Domain\Enums\PackStatus;
use App\Modules\ExamPrep\Domain\ExamPack;
use App\Modules\ExamPrep\Domain\PackTopic;
use App\Modules\ExamPrep\Domain\PrepQuestion;
use App\Modules\ExamPrep\Events\PackSaved;
use App\Modules\ExamPrep\Services\NoPromises;
use App\Support\Money;
use App\Support\PersianDigits;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * ساخت یا ویرایش بسته از پنل. موضوع‌ها یک خط در میان نوشته می‌شوند؛ موضوعی
 * که از فهرست برداشته شود فقط وقتی حذف می‌شود که سؤالی نداشته باشد.
 */
final readonly class SavePack
{
    public function __construct(
        private NoPromises $noPromises,
        private ConnectionInterface $db,
        private Dispatcher $events,
    ) {}

    /**
     * @param  array{title: string, slug: string, exam_name: string, description: string, price: string, exam_question_count: string|int, exam_minutes: string|int, topics: string}  $input
     */
    public function handle(array $input, int $actorId, ?ExamPack $pack = null): ExamPack
    {
        $title = trim($input['title']);
        $examName = trim($input['exam_name']);
        $description = trim($input['description']);
        $slug = Str::lower(trim($input['slug']));
        $price = Money::fromInput($input['price']);
        $questionCount = (int) PersianDigits::toLatin((string) $input['exam_question_count']);
        $minutes = (int) PersianDigits::toLatin((string) $input['exam_minutes']);
        $topics = array_values(array_unique(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $input['topics']) ?: []))));

        if (mb_strlen($title) < 3 || mb_strlen($examName) < 3 || mb_strlen($description) < 10) {
            throw new InvalidArgumentException('عنوان، نام آزمون و معرفی بسته را کامل بنویسید.');
        }

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1) {
            throw new InvalidArgumentException('نشانی بسته فقط حروف کوچک لاتین، عدد و خط تیره است؛ مثل moh-employment.');
        }

        if (ExamPack::query()->where('slug', $slug)->when($pack, fn ($q) => $q->whereKeyNot($pack->id))->exists()) {
            throw new InvalidArgumentException('این نشانی را بسته دیگری دارد.');
        }

        if ($price->isZero()) {
            throw new InvalidArgumentException('بسته آزمون رایگان نیست؛ قیمت بگذارید. نمونه رایگان جداگانه هست.');
        }

        if ($questionCount < 5 || $questionCount > 300 || $minutes < 5 || $minutes > 360) {
            throw new InvalidArgumentException('آزمون شبیه‌سازی بین ۵ تا ۳۰۰ سؤال و ۵ تا ۳۶۰ دقیقه است.');
        }

        if ($topics === []) {
            throw new InvalidArgumentException('دست‌کم یک موضوع بنویسید؛ هر موضوع در یک خط.');
        }

        $this->noPromises->assert($title, $examName, $description);

        $priceBefore = $pack?->price_toman;

        $pack = $this->db->transaction(function () use ($pack, $title, $examName, $description, $slug, $price, $questionCount, $minutes, $topics): ExamPack {
            $attributes = [
                'title' => $title,
                'slug' => $slug,
                'exam_name' => $examName,
                'description' => $description,
                'price_toman' => $price->toman,
                'exam_question_count' => $questionCount,
                'exam_minutes' => $minutes,
            ];

            if ($pack === null) {
                $pack = ExamPack::query()->create([
                    'uuid' => (string) Str::uuid7(),
                    'status' => PackStatus::Draft,
                    ...$attributes,
                ]);
            } else {
                $pack->update($attributes);
            }

            $this->syncTopics($pack, $topics);

            return $pack;
        });

        $this->events->dispatch(new PackSaved($pack, $priceBefore, $actorId));

        return $pack;
    }

    /** @param  list<string>  $titles */
    private function syncTopics(ExamPack $pack, array $titles): void
    {
        $existing = PackTopic::query()->where('exam_pack_id', $pack->id)->get()->keyBy('title');

        foreach ($titles as $sort => $title) {
            $topic = $existing->get($title) ?? new PackTopic(['exam_pack_id' => $pack->id, 'title' => $title]);
            $topic->sort = $sort;
            $topic->save();
        }

        foreach ($existing as $title => $topic) {
            if (! in_array($title, $titles, true) && ! PrepQuestion::query()->where('topic_id', $topic->id)->exists()) {
                $topic->delete();
            }
        }
    }
}
