<?php

declare(strict_types=1);

namespace App\Modules\Linking\Services;

use App\Contracts\InternalLinker;
use App\Modules\Linking\Domain\InternalLink;
use App\Support\Linking\LinkRef;
use App\Support\Linking\LinkSegment;

/**
 * نمایش پیوندهایی که بازسازی آخر ثبت کرده.
 *
 * فقط عبارت‌های ثبت‌شده برای همین سند دوباره در متن جست‌وجو می‌شوند — چند
 * عبارت، نه همه عبارت‌های سایت. اگر متن بعد از بازسازی عوض شده و عبارت
 * دیگر نیست، پیوند بی‌صدا نمی‌آید تا بازسازی بعدی.
 */
final readonly class StoredLinker implements InternalLinker
{
    public function link(string $documentKey, array $paragraphs): array
    {
        $links = InternalLink::query()->where('source_key', $documentKey)->get()->keyBy('target_key');

        if ($links->isEmpty()) {
            return array_map(static fn (string $paragraph): array => [LinkSegment::text($paragraph)], $paragraphs);
        }

        $matcher = PhraseMatcher::for(
            $links->map(static fn (InternalLink $link): array => [$link->phrase])->all(),
            minLength: 1,
        );

        $placed = [];
        $result = [];

        foreach ($paragraphs as $paragraph) {
            $segments = [];
            $cursor = 0;

            foreach ($matcher->find($paragraph) as $match) {
                if (isset($placed[$match->targetKey])) {
                    continue;
                }

                $placed[$match->targetKey] = true;
                $link = $links[$match->targetKey];

                if ($match->start > $cursor) {
                    $segments[] = LinkSegment::text(mb_substr($paragraph, $cursor, $match->start - $cursor));
                }

                $segments[] = new LinkSegment(mb_substr($paragraph, $match->start, $match->length), $link->target_url, $link->target_title);
                $cursor = $match->end();
            }

            if ($cursor < mb_strlen($paragraph)) {
                $segments[] = LinkSegment::text(mb_substr($paragraph, $cursor));
            }

            $result[] = $segments;
        }

        return $result;
    }

    public function mentionedIn(string $targetKey, int $limit): array
    {
        return InternalLink::query()
            ->where('target_key', $targetKey)
            ->orderBy('source_title')
            ->limit($limit)
            ->get()
            ->map(static fn (InternalLink $link): LinkRef => new LinkRef($link->source_key, $link->source_title, $link->source_url))
            ->values()
            ->all();
    }

    public function mentions(string $documentKey): array
    {
        return InternalLink::query()
            ->where('source_key', $documentKey)
            ->orderBy('position')
            ->get()
            ->map(static fn (InternalLink $link): LinkRef => new LinkRef($link->target_key, $link->target_title, $link->target_url))
            ->values()
            ->all();
    }
}
