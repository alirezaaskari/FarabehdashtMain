<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Domain\Enums;

enum Difficulty: string
{
    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';

    public function label(): string
    {
        return match ($this) {
            self::Easy => 'آسان',
            self::Medium => 'متوسط',
            self::Hard => 'دشوار',
        };
    }

    /** نام فارسی یا انگلیسی سختی، همان‌طور که در CSV نوشته می‌شود. */
    public static function fromInput(string $input): ?self
    {
        return match (trim(mb_strtolower($input))) {
            'easy', 'آسان', '1', '۱' => self::Easy,
            'medium', 'متوسط', '2', '۲', '' => self::Medium,
            'hard', 'دشوار', 'سخت', '3', '۳' => self::Hard,
            default => null,
        };
    }
}
