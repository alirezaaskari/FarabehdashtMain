<?php

declare(strict_types=1);

namespace App\Support\Audit;

use DateTimeInterface;

/** شرط‌های جست‌وجو در دفتر رویداد. هر فیلد null یعنی «مهم نیست». */
final readonly class AuditFilter
{
    public function __construct(
        public ?string $action = null,
        public ?int $actorId = null,
        public ?string $subjectType = null,
        public ?string $subjectId = null,
        public ?DateTimeInterface $from = null,
        public ?DateTimeInterface $until = null,
    ) {}
}
