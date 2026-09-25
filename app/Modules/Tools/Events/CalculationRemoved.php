<?php

declare(strict_types=1);

namespace App\Modules\Tools\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Tools\Domain\Enums\CalculationRemoval;
use App\Support\Audit\AuditEntry;

/**
 * کاربر محاسبه‌ای را حذف کرد — پاک یا بایگانی.
 *
 * شناسه‌ها از پیش گرفته می‌شوند، چون ردیف پاک‌شده دیگر مدلی ندارد.
 */
final readonly class CalculationRemoved implements AuditableEvent
{
    public function __construct(
        public string $calculationUuid,
        public int $userId,
        public CalculationRemoval $outcome,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'tools.calculation_removed',
            subjectType: 'tool_calculation',
            subjectId: $this->calculationUuid,
            actorId: $this->userId,
            after: ['outcome' => $this->outcome->value],
        );
    }
}
