<?php

declare(strict_types=1);

namespace App\Modules\Linking\Services;

use App\Modules\Linking\Domain\LinkBlock;
use App\Modules\Linking\Domain\PhraseMatch;
use App\Support\Linking\LinkableDocument;
use App\Support\PersianText;

/**
 * تصمیم اینکه در یک سند کدام مقصدها پیوند بخورند.
 *
 * قاعده‌ها، به همین ترتیب:
 * - سند به خودش پیوند نمی‌دهد.
 * - قاعده مسدود مدیر برنده است.
 * - هر مقصد فقط یک بار، در اولین جایی که آمده.
 * - سقف پیوند متناسب با طول متن (DEC-31)؛ پیوندهای اول متن برنده‌اند.
 */
final readonly class LinkPlanner
{
    /**
     * @param  list<LinkBlock>  $blocks
     */
    public function __construct(
        private array $blocks,
        private int $perThousandWords,
        private int $minPerDocument,
    ) {}

    /**
     * @return list<PhraseMatch> مقصدهای برگزیده به ترتیب آمدن در متن
     */
    public function plan(LinkableDocument $document, PhraseMatcher $matcher): array
    {
        $cap = $this->capFor($document);
        $chosen = [];

        foreach ($document->paragraphs as $paragraph) {
            foreach ($matcher->find($paragraph) as $match) {
                if (count($chosen) >= $cap) {
                    return array_values($chosen);
                }

                if ($match->targetKey === $document->key
                    || isset($chosen[$match->targetKey])
                    || $this->blocked($document->key, $match)) {
                    continue;
                }

                $chosen[$match->targetKey] = $match;
            }
        }

        return array_values($chosen);
    }

    public function capFor(LinkableDocument $document): int
    {
        $words = 0;

        foreach ($document->paragraphs as $paragraph) {
            $words += count(PersianText::words($paragraph));
        }

        return max($this->minPerDocument, intdiv($words * $this->perThousandWords, 1000));
    }

    private function blocked(string $sourceKey, PhraseMatch $match): bool
    {
        foreach ($this->blocks as $block) {
            if ($block->blocks($sourceKey, $match->targetKey, $match->phrase)) {
                return true;
            }
        }

        return false;
    }
}
