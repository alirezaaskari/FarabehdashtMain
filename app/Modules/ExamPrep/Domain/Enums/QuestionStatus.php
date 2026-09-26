<?php

declare(strict_types=1);

namespace App\Modules\ExamPrep\Domain\Enums;

enum QuestionStatus: string
{
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار بررسی',
            self::Published => 'منتشرشده',
            self::Rejected => 'برگشت برای اصلاح',
        };
    }

    /** نام لحن `<x-badge>`. */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'caution',
            self::Published => 'primary',
            self::Rejected => 'danger',
        };
    }
}
