<?php

declare(strict_types=1);

namespace App\Modules\Expert\Editorial;

use App\Modules\Expert\Domain\Enums\QuestionTopic;

/**
 * یک پرسش و پاسخ تحریریه.
 *
 * `key` شناسه ثابت است و نشانی صفحه از آن ساخته می‌شود؛ پس از انتشار عوضش
 * نکنید. منابع جدا نگه داشته می‌شوند و آخر پاسخ می‌آیند.
 */
final readonly class EditorialEntry
{
    /**
     * @param  list<string>  $answer  بندهای پاسخ
     * @param  list<string>  $sources
     */
    public function __construct(
        public string $key,
        public QuestionTopic $topic,
        public string $title,
        public string $question,
        public array $answer,
        public array $sources,
    ) {}

    public function answerBody(): string
    {
        return implode("\n\n", [...$this->answer, 'منابع: '.implode('؛ ', $this->sources).'.']);
    }
}
