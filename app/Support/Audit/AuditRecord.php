<?php

declare(strict_types=1);

namespace App\Support\Audit;

use DateTimeInterface;

/**
 * یک ردیف خوانده‌شده از دفتر رویداد.
 *
 * چرا DTO و نه خود مدل: مدل `AuditLog` دارایی ماژول Core است و هیچ ماژول
 * دیگری آن را import نمی‌کند (قاعده ۱). پنل مدیریت ردیف‌ها را از راه قرارداد
 * `AuditTrailReader` می‌گیرد و با همین شکل ساده نمایش می‌دهد.
 */
final readonly class AuditRecord
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public int $id,
        public string $action,
        public DateTimeInterface $createdAt,
        public ?string $subjectType = null,
        public ?string $subjectId = null,
        public ?int $actorId = null,
        public ?string $actorName = null,
        public array $before = [],
        public array $after = [],
        public array $context = [],
        public ?string $ip = null,
    ) {}
}
