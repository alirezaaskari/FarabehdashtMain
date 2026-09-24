<?php

declare(strict_types=1);

namespace App\Support\Linking;

use App\Contracts\InternalLinker;

/**
 * پیش‌فرض بدون موتور پیوند: متن همان‌طور که هست، بی‌هیچ ارجاعی.
 *
 * در `AppServiceProvider` بسته می‌شود و ماژول Linking جایش را می‌گیرد؛
 * مصرف‌کننده‌ها شاخه «اگر ماژول نبود» نمی‌نویسند.
 */
final readonly class NoLinks implements InternalLinker
{
    public function link(string $documentKey, array $paragraphs): array
    {
        return array_map(static fn (string $paragraph): array => [LinkSegment::text($paragraph)], $paragraphs);
    }

    public function mentionedIn(string $targetKey, int $limit): array
    {
        return [];
    }

    public function mentions(string $documentKey): array
    {
        return [];
    }
}
