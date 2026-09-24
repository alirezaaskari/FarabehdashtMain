<?php

declare(strict_types=1);

namespace App\Modules\Linking\Domain;

/**
 * یک رخداد عبارت در متن اصلی: جایگاه و طول بر حسب نویسه، نه بایت.
 */
final readonly class PhraseMatch
{
    public function __construct(
        public int $start,
        public int $length,
        public string $targetKey,
        public string $phrase,
    ) {}

    public function end(): int
    {
        return $this->start + $this->length;
    }
}
