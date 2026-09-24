<?php

declare(strict_types=1);

namespace App\Support\Linking;

/**
 * صفحه‌ای که می‌تواند مقصد پیوند خودکار باشد.
 *
 * کلید به شکل «ماژول:شناسه» است (`chemicals:benzene`) و در همه سایت یکتاست؛
 * سندی که کلیدش با مقصد یکی باشد، هرگز به خودش پیوند نمی‌دهد.
 */
final readonly class LinkTarget
{
    /**
     * @param  list<string>  $phrases  عبارت‌هایی که در متن به این صفحه پیوند می‌خورند
     */
    public function __construct(
        public string $key,
        public string $title,
        public string $url,
        public array $phrases,
    ) {}

    /**
     * عبارت‌های یک عنوان: خود عنوان، و اگر زیرعنوانی با خط تیره دارد، بخش
     * پیش از آن. «اندازه‌گیری صدا در محیط کار — روش و تجهیزات» در متن همیشه
     * کوتاه‌شده می‌آید.
     *
     * @return list<string>
     */
    public static function titlePhrases(string $title): array
    {
        $head = trim((string) preg_split('/\s[—–]\s/u', $title, 2)[0]);

        return array_values(array_unique(array_filter([$title, $head], static fn (string $p): bool => $p !== '')));
    }
}
