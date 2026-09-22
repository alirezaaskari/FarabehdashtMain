<?php

declare(strict_types=1);

namespace App\Modules\Encyclopedia\Services;

/**
 * نمره سلامت یک محتوا، به‌علاوه دلیلش.
 *
 * نمره تنها یک عدد نیست: بدون فهرست «چه چیزی کم است»، مدیر نمی‌داند چه کار
 * کند و عدد فقط اضطراب می‌سازد.
 */
final readonly class HealthScore
{
    /**
     * @param  int  $score  ۰ تا ۱۰۰
     * @param  array<string, bool>  $criteria  کلید معیار => برآورده شده
     * @param  list<string>  $gaps  آنچه کم است، به زبان کاربر
     */
    public function __construct(
        public int $score,
        public array $criteria,
        public array $gaps,
    ) {}

    public function needsAttention(int $threshold): bool
    {
        return $this->score < $threshold;
    }

    public function tone(int $threshold): string
    {
        if ($this->score >= 90) {
            return 'primary';
        }

        return $this->score < $threshold ? 'danger' : 'caution';
    }
}
