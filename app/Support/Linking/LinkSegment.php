<?php

declare(strict_types=1);

namespace App\Support\Linking;

/**
 * تکه‌ای از یک بند: متن ساده، یا متنی که به صفحه‌ای پیوند دارد.
 *
 * HTML نیست؛ قالب هر تکه را escape‌شده چاپ می‌کند.
 */
final readonly class LinkSegment
{
    public function __construct(
        public string $text,
        public ?string $url = null,
        public ?string $title = null,
    ) {}

    public static function text(string $text): self
    {
        return new self($text);
    }

    public function isLink(): bool
    {
        return $this->url !== null;
    }
}
