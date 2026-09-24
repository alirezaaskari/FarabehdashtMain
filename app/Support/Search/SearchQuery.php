<?php

declare(strict_types=1);

namespace App\Support\Search;

use App\Support\PersianDigits;
use App\Support\PersianText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * عبارت جست‌وجو، یکسان‌شده یک‌بار برای همه ماژول‌ها.
 *
 * هر کلمه باید در دست‌کم یکی از ستون‌ها بیاید (AND بین کلمه‌ها، OR بین
 * ستون‌ها و شکل‌های نوشتاری). ترتیب کلمه‌ها مهم نیست: «سنج صدا» و «صدا سنج»
 * یک نتیجه دارند.
 */
final readonly class SearchQuery
{
    /** کوتاه‌تر از این، تقریباً همه‌چیز را پیدا می‌کند و فقط بار دیتابیس است. */
    public const MIN_LENGTH = 2;

    /** بیشترین کلمه‌ای که به شرط تبدیل می‌شود؛ جلوی پرس‌وجوی سنگین عمدی را می‌گیرد. */
    private const MAX_WORDS = 6;

    /** @param  list<string>  $words */
    private function __construct(
        public string $raw,
        public string $text,
        private array $words,
    ) {}

    public static function from(string $raw): self
    {
        $raw = mb_substr(trim($raw), 0, 120);

        return new self($raw, PersianText::normalise($raw), array_slice(PersianText::words($raw), 0, self::MAX_WORDS));
    }

    public function isSearchable(): bool
    {
        return mb_strlen($this->text) >= self::MIN_LENGTH && $this->words !== [];
    }

    /**
     * شرط LIKE روی ستون‌های داده‌شده.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  list<string>  $columns
     * @return Builder<TModel>
     */
    public function constrain(Builder $query, array $columns): Builder
    {
        foreach ($this->words as $word) {
            $patterns = array_map(
                static fn (string $form): string => '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $form).'%',
                self::forms($word),
            );

            $query->where(function (Builder $any) use ($columns, $patterns): void {
                foreach ($columns as $column) {
                    foreach ($patterns as $pattern) {
                        $any->orWhere($column, 'like', $pattern);
                    }
                }
            });
        }

        return $query;
    }

    /**
     * شکل‌های نوشتاری یک کلمه: فارسی، عربی و با ارقام فارسی.
     *
     * @return list<string>
     */
    private static function forms(string $word): array
    {
        return array_values(array_unique([
            $word,
            PersianText::arabicForm($word),
            PersianDigits::from($word),
        ]));
    }
}
