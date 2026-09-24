<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain;

/**
 * شناسه رهگیری گزارش: `FBH-XXXX-XXXX`.
 *
 * هشت نویسه تصادفی از الفبای Crockford Base32 — بدون I، L، O و U، که پشت
 * تلفن یا روی کاغذ چاپی با ۱ و ۰ اشتباه خوانده می‌شوند. ترتیبی نیست، پس با
 * شمردن نمی‌شود گزارش دیگران را پیدا کرد؛ ۳۲ به توان ۸ (بیش از یک تریلیون)
 * حالت، همراه محدودیت نرخ صفحه تأیید، حدس‌زدن را بی‌فایده می‌کند.
 */
final readonly class TrackingCode
{
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    private const PREFIX = 'FBH';

    private function __construct(public string $value) {}

    public static function generate(): self
    {
        $chars = '';

        for ($i = 0; $i < 8; $i++) {
            $chars .= self::ALPHABET[random_int(0, 31)];
        }

        return self::fromChars($chars);
    }

    /**
     * آنچه کاربر تایپ کرده، یا null اگر شکل شناسه نباشد.
     *
     * فاصله، خط تیره، حروف کوچک و ارقام فارسی پذیرفته می‌شوند و O و I و L به
     * رقم هم‌شکلشان برمی‌گردند — همان قاعده Crockford.
     */
    public static function parse(string $input): ?self
    {
        $latin = strtr($input, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9']);

        $clean = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $latin));

        if (str_starts_with($clean, self::PREFIX)) {
            $clean = substr($clean, strlen(self::PREFIX));
        }

        $clean = strtr($clean, ['O' => '0', 'I' => '1', 'L' => '1']);

        if (strlen($clean) !== 8 || strspn($clean, self::ALPHABET) !== 8) {
            return null;
        }

        return self::fromChars($clean);
    }

    private static function fromChars(string $chars): self
    {
        return new self(self::PREFIX.'-'.substr($chars, 0, 4).'-'.substr($chars, 4, 4));
    }
}
