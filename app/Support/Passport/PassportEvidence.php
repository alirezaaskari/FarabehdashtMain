<?php

declare(strict_types=1);

namespace App\Support\Passport;

use Illuminate\Support\Carbon;

/**
 * یک سطر «ثبت‌شده در فرابهداشت» در گذرنامه مهارتی.
 *
 * `tags` کلیدهای خود منبع‌اند (مثل `formula:noise-dose` یا
 * `expert-topic:noise`)؛ گذرنامه آن‌ها را با جدول خودش به مهارت شغلی
 * نگاشت می‌کند و منبع هیچ‌وقت مهارت شغلی را نمی‌شناسد. `score` درصد نمره
 * برای کارنامه آزمون است تا آستانه (DEC-70) را خود گذرنامه اعمال کند.
 */
final readonly class PassportEvidence
{
    /** @param  list<string>  $tags */
    public function __construct(
        public string $title,
        public Carbon $earnedAt,
        public ?string $detail = null,
        public int $count = 1,
        public array $tags = [],
        public ?int $score = null,
        public ?string $url = null,
    ) {}
}
