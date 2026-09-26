<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Domain\Enums;

/**
 * سه جور دور: نمونه رایگان و تمرین با پاسخ فوری، آزمون با زمان‌سنج و
 * پاسخ در پایان.
 */
enum AttemptMode: string
{
    case Sample = 'sample';
    case Practice = 'practice';
    case Exam = 'exam';

    public function label(): string
    {
        return match ($this) {
            self::Sample => 'نمونه رایگان',
            self::Practice => 'تمرین',
            self::Exam => 'آزمون شبیه‌سازی‌شده',
        };
    }

    /** پاسخ هر سؤال همان لحظه نشان داده می‌شود؟ */
    public function givesInstantFeedback(): bool
    {
        return $this !== self::Exam;
    }
}
