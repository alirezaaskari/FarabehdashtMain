<?php

declare(strict_types=1);

namespace App\Modules\Tools\Events;

use App\Contracts\AuditableEvent;
use App\Modules\Tools\Domain\Tool;
use App\Support\Audit\AuditEntry;

/**
 * مدیر نسخه فرمول یک ابزار را عوض کرد.
 *
 * از این لحظه محاسبه‌های تازه با نسخه دیگری اجرا می‌شوند. محاسبه‌های قبلی
 * دست‌نخورده می‌مانند و با نسخه خودشان بازتولید می‌شوند — ولی اینکه چه زمانی
 * و با اجازه چه کسی این تغییر رخ داده باید در دفتر بماند.
 */
final readonly class FormulaVersionPinned implements AuditableEvent
{
    public function __construct(
        public string $slug,
        public ?string $previousVersion,
        public ?string $version,
        public int $affectedCalculations,
        public ?int $actorId,
    ) {}

    public function auditEntry(): AuditEntry
    {
        return new AuditEntry(
            action: 'tools.formula_version_pinned',
            subjectType: Tool::class,
            subjectId: $this->slug,
            actorId: $this->actorId,
            before: ['pinned_version' => $this->previousVersion],
            after: ['pinned_version' => $this->version],
            context: ['affected_calculations' => $this->affectedCalculations],
        );
    }
}
