<?php

declare(strict_types=1);

namespace App\Modules\Linking\Services;

use App\Modules\Linking\Domain\PhraseMatch;
use App\Support\PersianDigits;

/**
 * پیداکردن عبارت‌های مقصد در متن فارسی.
 *
 * تطبیق روی شکل یکسان‌شده انجام می‌شود (ی و ک عربی، اعراب و کشیده، ارقام
 * فارسی، نیم‌فاصله، حروف بزرگ لاتین) ولی جایگاه‌ها به متن اصلی برمی‌گردند،
 * تا پیوند دقیقاً روی همان کلمه‌هایی بنشیند که نویسنده نوشته.
 *
 * سه قاعده که از خود تطبیق می‌آیند:
 * - مرز کلمه: «صوت» درون «صوتی» یا «صوت‌سنج» پیدا نمی‌شود؛ ولی «نیم فاصله»
 *   با فاصله، «نیم‌فاصله» با نیم‌فاصله را پیدا می‌کند.
 * - پسوندهای جدانوشته («‌ها»، «‌های»، «‌ی») و «ی» اضافه پس از ا و و
 *   («صدای») جزو همان کلمه‌اند و پیوند را می‌گیرند.
 * - طولانی‌ترین برنده است: «تراز فشار صوت» پیش از «صوت».
 * - عبارتی که به دو مقصد اشاره کند مبهم است و کنار گذاشته می‌شود.
 */
final readonly class PhraseMatcher
{
    /** شمار عبارت‌های هر الگو؛ الگوی خیلی بلند از سقف PCRE می‌گذرد. */
    private const PER_PATTERN = 250;

    private const ZWNJ = "\u{200C}";

    /**
     * @param  array<string, string>  $phrases  عبارت یکسان‌شده ← کلید مقصد
     * @param  list<string>  $patterns
     */
    private function __construct(
        private array $phrases,
        private array $patterns,
    ) {}

    /**
     * @param  array<string, list<string>>  $phrasesByTarget  کلید مقصد ← عبارت‌ها
     */
    public static function for(array $phrasesByTarget, int $minLength = 3): self
    {
        $phrases = [];
        $ambiguous = [];

        foreach ($phrasesByTarget as $targetKey => $candidates) {
            foreach ($candidates as $candidate) {
                $phrase = self::normalise($candidate);

                if (mb_strlen($phrase) < $minLength || isset($ambiguous[$phrase])) {
                    continue;
                }

                if (isset($phrases[$phrase]) && $phrases[$phrase] !== $targetKey) {
                    $ambiguous[$phrase] = true;
                    unset($phrases[$phrase]);

                    continue;
                }

                $phrases[$phrase] = $targetKey;
            }
        }

        $sorted = array_map('strval', array_keys($phrases));
        usort($sorted, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        $patterns = array_map(
            static fn (array $chunk): string => '/(?<![\p{L}\p{N}\p{M}\x{200C}])(?<phrase>'
                .implode('|', array_map(
                    static fn (string $p): string => str_replace(' ', '[ \x{200C}]', preg_quote($p, '/')),
                    $chunk,
                ))
                .')(?:(?<=[او])ی)?(?:\x{200C}(?:های|ها|ی))?(?![\p{L}\p{N}\p{M}\x{200C}])/u',
            array_chunk($sorted, self::PER_PATTERN),
        );

        return new self($phrases, $patterns);
    }

    /** شکل یکسان‌شده یک عبارت یا متن، همان که تطبیق روی آن انجام می‌شود. */
    public static function normalise(string $text): string
    {
        $folded = str_replace(self::ZWNJ, ' ', implode('', array_column(self::fold($text), 0)));

        return trim((string) preg_replace('/ {2,}/', ' ', $folded));
    }

    public function isEmpty(): bool
    {
        return $this->phrases === [];
    }

    /**
     * رخدادهای بی‌هم‌پوشانی عبارت‌ها در متن، به ترتیب جایگاه.
     *
     * @return list<PhraseMatch>
     */
    public function find(string $text): array
    {
        if ($this->phrases === []) {
            return [];
        }

        $folded = self::fold($text);
        $normalised = implode('', array_column($folded, 0));

        $candidates = [];

        foreach ($this->patterns as $pattern) {
            preg_match_all($pattern, $normalised, $matches, PREG_OFFSET_CAPTURE);

            foreach ($matches[0] as $index => [$whole, $byteOffset]) {
                $phrase = $matches['phrase'][$index][0];
                $start = mb_strlen(substr($normalised, 0, $byteOffset));
                $end = $start + mb_strlen($whole);

                $originalStart = $folded[$start][1];
                $originalEnd = $folded[$end - 1][1] + 1;

                $key = str_replace(self::ZWNJ, ' ', $phrase);

                $candidates[] = new PhraseMatch($originalStart, $originalEnd - $originalStart, $this->phrases[$key], $key);
            }
        }

        // چند الگو ممکن است روی یک جا با هم پیدا کنند؛ زودتر و بلندتر برنده است.
        usort($candidates, static fn (PhraseMatch $a, PhraseMatch $b): int => [$a->start, -$a->length] <=> [$b->start, -$b->length]);

        $kept = [];
        $cursor = 0;

        foreach ($candidates as $candidate) {
            if ($candidate->start >= $cursor) {
                $kept[] = $candidate;
                $cursor = $candidate->end();
            }
        }

        return $kept;
    }

    /**
     * متن به نویسه‌های یکسان‌شده، هر کدام با جایگاهش در متن اصلی.
     *
     * نویسه‌ای که معنا ندارد (اعراب، کشیده) حذف می‌شود و فاصله‌های پشت‌سرهم
     * یکی می‌شوند؛ برای همین طول دو متن فرق دارد و نگاشت لازم است.
     *
     * @return list<array{0: string, 1: int}>
     */
    private static function fold(string $text): array
    {
        $folded = [];
        $index = 0;

        foreach (mb_str_split($text) as $char) {
            $mapped = self::foldChar($char);

            if ($mapped !== '' && ! ($mapped === ' ' && ($folded === [] || end($folded)[0] === ' '))) {
                $folded[] = [$mapped, $index];
            }

            $index++;
        }

        return $folded;
    }

    private static function foldChar(string $char): string
    {
        if ($char === self::ZWNJ) {
            return $char;
        }

        if (preg_match('/\s/u', $char) === 1) {
            return ' ';
        }

        if (preg_match('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', $char) === 1) {
            return '';
        }

        return mb_strtolower(strtr(PersianDigits::toLatin($char), [
            'ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک', 'ة' => 'ه', 'ۀ' => 'ه', 'أ' => 'ا', 'إ' => 'ا',
        ]));
    }
}
