<?php

declare(strict_types=1);

namespace App\Support\Revisions;

use DateTimeInterface;

/**
 * یک نسخه خوانده‌شده؛ نویسنده عمداً نیست تا صفحه عمومی داده شخصی نشان ندهد.
 */
final readonly class RevisionRecord
{
    /** @param  array<string, mixed>  $snapshot */
    public function __construct(
        public int $version,
        public array $snapshot,
        public ?string $reason,
        public DateTimeInterface $recordedAt,
    ) {}
}
