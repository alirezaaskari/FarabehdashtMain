<?php

declare(strict_types=1);

namespace App\Support;

/**
 * یکسان‌سازی متن فارسی برای مقایسه و جست‌وجو.
 *
 * یک کلمه در دیتابیس می‌تواند با «ي» و «ك» عربی نوشته شده باشد (متن کپی‌شده
 * از سند قدیمی) و کاربر با «ی» و «ک» فارسی جست‌وجو کند، یا برعکس. این کلاس
 * هر دو شکل را می‌سازد؛ ستون‌های ماژول‌ها دست نمی‌خورند.
 */
final readonly class PersianText
{
    private const ARABIC_TO_PERSIAN = ['ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک', 'ة' => 'ه', 'ۀ' => 'ه', 'أ' => 'ا', 'إ' => 'ا'];

    private const ZERO_WIDTH_NON_JOINER = "\u{200C}";

    /** شکل فارسی متن با ارقام لاتین و فاصله‌های یکسان. */
    public static function normalise(string $text): string
    {
        $text = strtr(PersianDigits::toLatin($text), self::ARABIC_TO_PERSIAN);

        // اعراب و کشیده در جست‌وجو معنا ندارند.
        $text = (string) preg_replace('/[\x{064B}-\x{065F}\x{0640}]/u', '', $text);

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /** همان متن با «ي» و «ك» عربی — شکل دیگری که ممکن است در دیتابیس باشد. */
    public static function arabicForm(string $text): string
    {
        return strtr(self::normalise($text), ['ی' => 'ي', 'ک' => 'ك']);
    }

    /**
     * کلمه‌های متن. نیم‌فاصله هم جداکننده است: «نیم فاصله» باید «نیم‌فاصله» را
     * پیدا کند.
     *
     * @return list<string>
     */
    public static function words(string $text): array
    {
        $text = str_replace(self::ZERO_WIDTH_NON_JOINER, ' ', self::normalise($text));

        return array_values(array_filter(explode(' ', $text), static fn (string $word): bool => $word !== ''));
    }
}
