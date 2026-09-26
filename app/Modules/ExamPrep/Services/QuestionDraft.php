<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Services;

use App\Modules\ExamPrep\Domain\Enums\Difficulty;
use InvalidArgumentException;

/**
 * سؤالی که هنوز ذخیره نشده، از فرم نویسنده یا ردیف CSV. هر دو مسیر از
 * همین قاعده‌ها می‌گذرند: متن، دو تا پنج گزینه، یک گزینه درست و نشانی
 * مرجعی که فقط به صفحه‌ای از خود سایت اشاره کند.
 */
final readonly class QuestionDraft
{
    public const MIN_CHOICES = 2;

    public const MAX_CHOICES = 5;

    /** @param  list<string>  $choices */
    private function __construct(
        public string $topic,
        public Difficulty $difficulty,
        public string $body,
        public array $choices,
        public int $correctIndex,
        public ?string $explanation,
        public ?string $referenceLabel,
        public ?string $referencePath,
        public bool $isSample,
    ) {}

    /**
     * @param  list<string|null>  $choices  گزینه‌ها به ترتیب؛ خانه خالی نادیده گرفته می‌شود
     * @param  int  $correct  شماره گزینه درست، از یک
     */
    public static function make(
        string $topic,
        string $difficulty,
        string $body,
        array $choices,
        int $correct,
        ?string $explanation = null,
        ?string $referenceLabel = null,
        ?string $referencePath = null,
        bool $isSample = false,
    ): self {
        $body = trim($body);
        $topic = trim($topic);

        if ($topic === '') {
            throw new InvalidArgumentException('موضوع سؤال مشخص نیست.');
        }

        if (mb_strlen($body) < 5) {
            throw new InvalidArgumentException('متن سؤال خالی یا خیلی کوتاه است.');
        }

        $level = Difficulty::fromInput($difficulty)
            ?? throw new InvalidArgumentException('سختی باید آسان، متوسط یا دشوار باشد.');

        // خانه‌های خالی حذف می‌شوند ولی شماره گزینه درست به ترتیب اصلی است.
        $kept = [];
        $correctIndex = null;

        foreach ($choices as $position => $choice) {
            $text = trim((string) $choice);

            if ($text === '') {
                continue;
            }

            if ($position === $correct - 1) {
                $correctIndex = count($kept);
            }

            $kept[] = $text;
        }

        if (count($kept) < self::MIN_CHOICES || count($kept) > self::MAX_CHOICES) {
            throw new InvalidArgumentException('هر سؤال دو تا پنج گزینه دارد.');
        }

        if (count(array_unique($kept)) !== count($kept)) {
            throw new InvalidArgumentException('دو گزینه یکسان‌اند.');
        }

        if ($correctIndex === null) {
            throw new InvalidArgumentException('شماره گزینه درست به گزینه‌ای پر اشاره نمی‌کند.');
        }

        $path = self::internalPath($referencePath);
        $label = trim((string) $referenceLabel);

        return new self(
            topic: $topic,
            difficulty: $level,
            body: $body,
            choices: $kept,
            correctIndex: $correctIndex,
            explanation: trim((string) $explanation) ?: null,
            referenceLabel: $path === null ? null : ($label !== '' ? $label : 'مطالعه بیشتر'),
            referencePath: $path,
            isSample: $isSample,
        );
    }

    /**
     * فقط مسیر داخلی سایت، مثل `/encyclopedia/noise`. نشانی کامل همین سایت
     * هم به مسیر برگردانده می‌شود؛ نشانی سایت دیگر پذیرفته نیست.
     */
    private static function internalPath(?string $input): ?string
    {
        $input = trim((string) $input);

        if ($input === '') {
            return null;
        }

        $host = parse_url((string) config('app.url'), PHP_URL_HOST);
        $parts = parse_url($input);

        if (isset($parts['host'])) {
            if ($parts['host'] !== $host) {
                throw new InvalidArgumentException('نشانی مرجع باید صفحه‌ای از خود فرابهداشت باشد.');
            }

            $input = ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '');
        }

        if (! str_starts_with($input, '/') || str_starts_with($input, '//') || mb_strlen($input) > 255) {
            throw new InvalidArgumentException('نشانی مرجع باید با «/» شروع شود، مثل /encyclopedia/noise.');
        }

        return $input;
    }
}
